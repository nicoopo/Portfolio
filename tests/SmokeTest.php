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

    public function testTelechargementCvPdf(): void
    {
        $client = static::createClient();
        $client->request('GET', '/CV/download?theme=light');

        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('Content-Type', 'application/pdf');
    }
}
