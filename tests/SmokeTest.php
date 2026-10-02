<?php

namespace App\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class SmokeTest extends WebTestCase
{
    public static function pages(): iterable
    {
        foreach (['/', '/projects', '/competences', '/CV', '/contact', '/univers', '/cerveau'] as $url) {
            yield $url => [$url];
        }
    }

    #[DataProvider('pages')]
    public function testPageRepond(string $url): void
    {
        static::createClient()->request('GET', $url);

        self::assertResponseIsSuccessful();
    }

    public function testCompetencesRenvoientAuCerveau(): void
    {
        $crawler = static::createClient()->request('GET', '/competences');

        self::assertGreaterThan(0, $crawler->filter('a.skill-card[href^="/cerveau#"]')->count());
    }

    #[DataProvider('pages')]
    public function testPageAUnApercuDePartage(string $url): void
    {
        $crawler = static::createClient()->request('GET', $url);

        self::assertNotEmpty($crawler->filter('meta[name="description"]')->attr('content'));
        self::assertStringStartsWith('http', $crawler->filter('meta[property="og:image"]')->attr('content'));
    }

    public function testTelechargementCvPdf(): void
    {
        $client = static::createClient();
        $client->request('GET', '/CV/download?theme=light');

        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('Content-Type', 'application/pdf');
    }
}
