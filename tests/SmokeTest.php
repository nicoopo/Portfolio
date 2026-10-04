<?php

namespace App\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class SmokeTest extends WebTestCase
{
    public static function pages(): iterable
    {
        foreach (['/', '/projects', '/projects/portfolio', '/competences', '/CV', '/contact', '/univers', '/cerveau'] as $url) {
            yield $url => [$url];
            yield '/en'.$url => ['/en'.$url];
        }
    }

    /** Page anglaise : lang="en", texte traduit, et lien vers la même page en français */
    public function testLaVersionAnglaiseEstTraduite(): void
    {
        $crawler = static::createClient()->request('GET', '/en/competences');

        self::assertSelectorExists('html[lang="en"]');
        self::assertSelectorTextContains('h1', 'My Skills');
        self::assertAnySelectorTextContains('.skills-category h2', 'Networks / Infra'); // contenu de la base
        self::assertSame('http://localhost/competences', $crawler->filter('link[hreflang="fr"]')->attr('href'));
        self::assertSame('http://localhost/competences', $crawler->filter('a.lang-switch')->attr('href'));
    }

    #[DataProvider('pages')]
    public function testPageRepond(string $url): void
    {
        static::createClient()->request('GET', $url);

        self::assertResponseIsSuccessful();
        self::assertSelectorCount(1, 'h1'); // un seul titre principal par page (SEO, lecteurs d'écran)
    }

    public function testCompetencesRenvoientAuCerveau(): void
    {
        $crawler = static::createClient()->request('GET', '/competences');

        self::assertGreaterThan(0, $crawler->filter('a.skill-card[href^="/cerveau#"]')->count());
    }

    /** Chaque carte de la liste mène à la page de son projet ; texte long, liens et compétences y sont */
    public function testChaqueProjetASaPage(): void
    {
        $client = static::createClient();
        $liens = $client->request('GET', '/projects')->filter('.projet-carte-titre a')->extract(['href']);
        self::assertContains('/projects/portfolio', $liens);

        foreach ($liens as $lien) {
            $client->request('GET', $lien);
            self::assertResponseIsSuccessful($lien);
        }

        $page = $client->request('GET', '/projects/portfolio');
        self::assertGreaterThan(1, $page->filter('.projet-texte p')->count());
        self::assertSelectorExists('a[href="https://github.com/nicoopo/Portfolio"]');
        self::assertSelectorExists('.projet-competences a[href^="/cerveau#"]');
        self::assertStringContainsString('/portfolio-cerveau', $page->filter('meta[property="og:image"]')->attr('content'));

        $client->request('GET', '/projects/inconnu');
        self::assertResponseStatusCodeSame(404);
    }

    /** Chaque souvenir du cerveau renvoie à une étape qui existe dans la frise de /univers */
    public function testLesSouvenirsRenvoientALaFrise(): void
    {
        $client = static::createClient();
        $souvenirs = json_decode($client->request('GET', '/cerveau')->filter('[data-brain-souvenirs-value]')->attr('data-brain-souvenirs-value'), true);
        $frise = $client->request('GET', '/univers');

        self::assertNotEmpty($souvenirs);
        foreach ($souvenirs as $souvenir) {
            self::assertStringStartsWith('/univers#', $souvenir['url']);
            self::assertCount(1, $frise->filter('.frise-etape'.strstr($souvenir['url'], '#')), $souvenir['url']);
        }
    }

    #[DataProvider('pages')]
    public function testPageAUnApercuDePartage(string $url): void
    {
        $crawler = static::createClient()->request('GET', $url);

        self::assertNotEmpty($crawler->filter('meta[name="description"]')->attr('content'));
        self::assertStringStartsWith('http', $crawler->filter('meta[property="og:image"]')->attr('content'));
    }

    /** Le sitemap liste toutes les pages publiques, dans les deux langues */
    public function testLeSitemapListeToutesLesPages(): void
    {
        $client = static::createClient();
        $client->request('GET', '/sitemap.xml');

        self::assertResponseHeaderSame('Content-Type', 'application/xml; charset=UTF-8');
        $sitemap = simplexml_load_string($client->getResponse()->getContent());
        $urls = array_map('strval', $sitemap->xpath('//*[local-name()="loc"]'));
        foreach (self::pages() as [$url]) {
            self::assertContains('http://localhost'.$url, $urls);
        }
    }

    public function testTelechargementCvPdf(): void
    {
        $client = static::createClient();
        $client->request('GET', '/CV/download?theme=light');

        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('Content-Type', 'application/pdf');
    }
}
