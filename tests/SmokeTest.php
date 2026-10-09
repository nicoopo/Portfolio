<?php

namespace App\Tests;

use App\Entity\Article;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class SmokeTest extends WebTestCase
{
    /** Sans ça, Request::create() envoie « en-us » et / redirige vers /en/ (LangueNavigateurListener) */
    private const NAVIGATEUR_FRANCAIS = ['HTTP_ACCEPT_LANGUAGE' => 'fr-FR'];

    public static function pages(): iterable
    {
        foreach (['/', '/projects', '/projects/portfolio', '/articles', '/now', '/competences', '/CV', '/contact', '/univers', '/cerveau', '/mentions-legales', '/confidentialite'] as $url) {
            yield $url => [$url];
            foreach (['en', 'es', 'de', 'it', 'pt'] as $langue) {
                yield "/$langue$url" => ["/$langue$url"];
            }
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
        self::assertSame('http://localhost/competences', $crawler->filter('#langues a[hreflang="fr"]')->attr('href'));
    }

    /** Autres langues : interface traduite, contenu de la base traduit, menu et hreflang vers les six langues */
    public function testLesAutresLanguesSontTraduites(): void
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/es/competences');
        self::assertSelectorExists('html[lang="es"]');
        self::assertSelectorTextContains('h1', 'Mis competencias');
        self::assertAnySelectorTextContains('.skills-category h2', 'Redes / Infra'); // contenu de la base
        self::assertCount(6, $crawler->filter('#langues a'));
        self::assertSame('true', $crawler->filter('#langues a[hreflang="es"]')->attr('aria-current'));
        self::assertCount(7, $crawler->filter('link[rel="alternate"][hreflang]')); // six langues + x-default

        $client->request('GET', '/de/projects/pendu');
        self::assertSelectorTextContains('h1', 'Galgenmännchen');

        $client->request('GET', '/pt/mentions-legales');
        self::assertSelectorTextContains('h1', 'Informação legal');
    }

    #[DataProvider('pages')]
    public function testPageRepond(string $url): void
    {
        static::createClient(server: self::NAVIGATEUR_FRANCAIS)->request('GET', $url);

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
        $crawler = static::createClient(server: self::NAVIGATEUR_FRANCAIS)->request('GET', $url);

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

    /** Blog : article publié rendu depuis le Markdown (HTML brut échappé) ; brouillon et article programmé invisibles */
    public function testSeulsLesArticlesPubliesSontVisibles(): void
    {
        $client = static::createClient(server: self::NAVIGATEUR_FRANCAIS);
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $article = fn (string $slug, ?string $publieLe) => (new Article())->setSlug($slug)->setTitre("Titre $slug")->setResume('Résumé')
            ->setContenu("# Partie\n\nDu **gras**, <script>alert(1)</script> et [un lien](javascript:alert(1)).")
            ->setPublieLe($publieLe ? new \DateTimeImmutable($publieLe) : null);
        $articles = [$article('test-publie', '-1 day')->setTraductions(['en' => ['titre' => 'Published test']]), $article('test-brouillon', null), $article('test-programme', '+1 day')];
        array_map($entityManager->persist(...), $articles);
        $entityManager->flush();

        try {
            $liste = $client->request('GET', '/articles')->filter('.projet-carte-titre a')->extract(['href']);
            self::assertContains('/articles/test-publie', $liste);
            self::assertNotContains('/articles/test-brouillon', $liste);
            self::assertNotContains('/articles/test-programme', $liste);

            $page = $client->request('GET', '/articles/test-publie');
            self::assertSelectorCount(1, 'h1');
            self::assertSelectorTextContains('.article-texte h2', 'Partie');
            self::assertSelectorTextContains('.article-texte strong', 'gras');
            self::assertCount(0, $page->filter('.article-texte script'));
            self::assertStringContainsString('<script>', $page->filter('.article-texte')->text());
            self::assertCount(0, $page->filter('.article-texte a[href^="javascript"]'));

            // Traduction du titre ; contenu non traduit : le français s'affiche
            $client->request('GET', '/en/articles/test-publie');
            self::assertSelectorTextContains('h1', 'Published test');
            self::assertSelectorTextContains('.article-texte strong', 'gras');

            foreach (['test-brouillon', 'test-programme'] as $slug) {
                $client->request('GET', "/articles/$slug");
                self::assertResponseStatusCodeSame(404);
            }

            // Flux RSS dans la langue de l'adresse, brouillon exclu ; annoncé dans l'en-tête des pages
            $client->request('GET', '/en/articles/rss.xml');
            self::assertResponseHeaderSame('Content-Type', 'application/rss+xml; charset=UTF-8');
            $flux = simplexml_load_string($client->getResponse()->getContent());
            $items = array_map('strval', $flux->xpath('//item/link'));
            self::assertContains('http://localhost/en/articles/test-publie', $items);
            self::assertNotContains('http://localhost/en/articles/test-brouillon', $items);
            self::assertSame('Published test', (string) $flux->xpath('//item[link="http://localhost/en/articles/test-publie"]/title')[0]);
            self::assertSame('/en/articles/rss.xml', $client->request('GET', '/en/articles')->filter('link[type="application/rss+xml"]')->attr('href'));

            // Palette de commandes : article publié cherchable, pas le brouillon
            $client->request('GET', '/recherche.json');
            $urls = array_column(json_decode($client->getResponse()->getContent(), true), 'url');
            self::assertContains('/articles/test-publie', $urls);
            self::assertNotContains('/articles/test-brouillon', $urls);

            $client->request('GET', '/sitemap.xml');
            self::assertStringContainsString('http://localhost/es/articles/test-publie', $client->getResponse()->getContent());
            self::assertStringNotContainsString('test-brouillon', $client->getResponse()->getContent());
        } finally {
            foreach ($articles as $a) {
                $entityManager->remove($entityManager->find(Article::class, $a->getId()));
            }
            $entityManager->flush();
        }
    }

    /** Palette de commandes (Ctrl+K) : bouton et fenêtre sur chaque page ; pages, compétences et projets cherchables, dans la langue de l'adresse */
    public function testLaPaletteDeCommandesCherchePartout(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/en/projects');
        self::assertSelectorExists('#paletteOuvrir[aria-keyshortcuts]');
        self::assertSame('/en/recherche.json', $crawler->filter('dialog#palette')->attr('data-url'));
        self::assertSame('Command', json_decode($crawler->filter('dialog#palette')->attr('data-textes'), true)['commande']);

        $client->request('GET', '/en/recherche.json');
        self::assertResponseHeaderSame('Content-Type', 'application/json');
        $entrees = json_decode($client->getResponse()->getContent(), true);
        self::assertContains(['type' => 'page', 'titre' => 'Skills', 'url' => '/en/competences'], $entrees);
        $parUrl = array_column($entrees, null, 'url');
        self::assertSame('projet', $parUrl['/en/projects/pendu']['type']);
        self::assertNotEmpty(array_filter($entrees, fn (array $e) => 'competence' === $e['type'] && str_starts_with($e['url'], '/en/cerveau#')));
    }

    /** Visite guidée : bouton sur l'accueil, cinq étapes vers des pages qui existent, textes traduits */
    public function testLaVisiteGuideeMeneADesPagesQuiExistent(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/en/');
        self::assertSelectorExists('button[data-visite-demarrer]');
        $etapes = json_decode($crawler->filter('#visite')->attr('data-etapes'), true);
        self::assertCount(5, $etapes);
        self::assertStringStartsWith('Welcome!', $etapes[0]['texte']);

        foreach ($etapes as $etape) {
            self::assertStringStartsWith('/en/', $etape['url']);
            $client->request('GET', $etape['url']);
            self::assertResponseIsSuccessful($etape['url']);
        }
    }

    /** Statistiques des easter eggs : +1 par découverte envoyée depuis le site, affiché dans le carnet ; le reste est refusé */
    public function testLesDecouvertesSontComptees(): void
    {
        $client = static::createClient(server: self::NAVIGATEUR_FRANCAIS);
        $connection = self::getContainer()->get(Connection::class);
        $nombre = fn () => (int) $connection->fetchOne("SELECT nombre FROM decouverte WHERE id = 'terminal'");
        $avant = $nombre();
        $envoyer = fn (string $id, array $server = []) => $client->request('POST', '/decouvertes', server: $server + ['CONTENT_TYPE' => 'application/json'], content: json_encode(['id' => $id]));

        $envoyer('terminal', ['HTTP_SEC_FETCH_SITE' => 'same-origin']);
        self::assertResponseStatusCodeSame(204);
        self::assertSame($avant + 1, $nombre());
        $client->request('GET', '/');
        self::assertSelectorTextContains('[data-decouverte="terminal"] .decouverte-stat', 1 === $avant + 1 ? 'Trouvée par un visiteur' : 'Trouvée par '.($avant + 1).' visiteurs');

        $envoyer('inconnue');
        self::assertResponseStatusCodeSame(400);
        $envoyer('terminal', ['HTTP_SEC_FETCH_SITE' => 'cross-site']);
        self::assertResponseStatusCodeSame(403);
        $client->request('GET', '/decouvertes');
        self::assertResponseStatusCodeSame(405);
        self::assertSame($avant + 1, $nombre());

        $connection->executeStatement("UPDATE decouverte SET nombre = :n WHERE id = 'terminal'", ['n' => $avant]);
    }

    public function testTelechargementCvPdf(): void
    {
        $client = static::createClient();
        $client->request('GET', '/CV/download?theme=light');

        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('Content-Type', 'application/pdf');
    }

    /** Adresse inconnue : 404 aux couleurs du site, dans la langue de l'adresse */
    public function testLaPage404EstTraduite(): void
    {
        $client = static::createClient(['debug' => false]);

        $client->request('GET', '/nexiste-pas');
        self::assertResponseStatusCodeSame(404);
        self::assertSelectorTextContains('h1', 'Page introuvable');
        self::assertSelectorExists('html[lang="fr"] .erreur-liens a[href="/cerveau"]');

        $client->request('GET', '/en/does-not-exist');
        self::assertResponseStatusCodeSame(404);
        self::assertSelectorTextContains('h1', 'Page not found');
        self::assertSelectorExists('html[lang="en"] .erreur-liens a[href="/en/cerveau"]');

        $client->request('GET', '/it/non-esiste');
        self::assertResponseStatusCodeSame(404);
        self::assertSelectorTextContains('h1', 'Pagina non trovata');
        self::assertSelectorExists('html[lang="it"] .erreur-liens a[href="/it/cerveau"]');
    }

    /** La lettre de motivation suit la langue de la page (visionneuse et téléchargement) */
    public function testLaLettreDeMotivationSuitLaLangue(): void
    {
        $client = static::createClient();

        $fr = $client->request('GET', '/univers');
        self::assertStringContainsString('lettre de motivation', $fr->filter('a[download^="Lettre_Motivation"]')->attr('href'));

        $en = $client->request('GET', '/en/univers');
        self::assertStringContainsString('cover-letter', $en->filter('a[download="Cover_Letter_Nicolas_Cataluna.pdf"]')->attr('href'));
        self::assertStringContainsString('cover-letter', $en->filter('button[data-pdf-title-param="My cover letter"]')->attr('data-pdf-url-param'));

        // Pas de lettre dans les autres langues : la version anglaise
        $es = $client->request('GET', '/es/univers');
        self::assertStringContainsString('cover-letter', $es->filter('a[download="Cover_Letter_Nicolas_Cataluna.pdf"]')->attr('href'));
    }

    /** Première visite sur / : langue du navigateur ; ensuite le cookie « langue » fige le choix */
    public function testLaPremiereVisiteSuitLaLangueDuNavigateur(): void
    {
        $client = static::createClient();

        $client->request('GET', '/', server: ['HTTP_ACCEPT_LANGUAGE' => 'de-DE,de;q=0.9,en;q=0.8']);
        self::assertResponseRedirects('http://localhost/de/');
        self::assertResponseNotHasCookie('langue');

        $client->followRedirect();
        self::assertResponseHasCookie('langue');
        self::assertSame('de', $client->getCookieJar()->get('langue')->getValue());

        // Clic sur « Français » : le cookie existe, plus de redirection
        $client->request('GET', '/', server: ['HTTP_ACCEPT_LANGUAGE' => 'de-DE']);
        self::assertResponseIsSuccessful();
        self::assertSelectorExists('html[lang="fr"]');
    }

    public function testSansLangueConnueOnResteEnFrancais(): void
    {
        $client = static::createClient();

        $client->request('GET', '/', server: ['HTTP_ACCEPT_LANGUAGE' => '']); // robot : pas d'Accept-Language
        self::assertResponseIsSuccessful();

        $client->getCookieJar()->clear();
        $client->request('GET', '/', server: ['HTTP_ACCEPT_LANGUAGE' => 'ja-JP']);
        self::assertResponseIsSuccessful();
        self::assertSelectorExists('html[lang="fr"]');
    }
}
