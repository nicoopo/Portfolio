<?php

namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class CspTest extends WebTestCase
{
    private const EN_TETE = 'Content-Security-Policy';

    /** Les scripts de la page portent le nonce annoncé dans l'en-tête, et il change à chaque requête */
    public function testLesScriptsPortentLeNonceDeLaRequete(): void
    {
        $client = static::createClient();
        $nonces = [];
        foreach (['/', '/CV'] as $url) {
            $crawler = $client->request('GET', $url);
            self::assertMatchesRegularExpression("/script-src 'self' 'nonce-([^']+)'/", $client->getResponse()->headers->get(self::EN_TETE));
            preg_match("/'nonce-([^']+)'/", $client->getResponse()->headers->get(self::EN_TETE), $nonce);

            $scripts = $crawler->filter('script');
            self::assertGreaterThan(0, $scripts->count());
            foreach ($scripts as $script) {
                self::assertSame($nonce[1], $script->getAttribute('nonce'), $url.' : '.substr($script->textContent, 0, 60));
            }
            $nonces[] = $nonce[1];
        }

        self::assertNotSame($nonces[0], $nonces[1]);
    }

    public function testLesRapportsDeViolationSontAcceptes(): void
    {
        $client = static::createClient();
        $client->request('POST', '/csp-report', server: ['CONTENT_TYPE' => 'application/csp-report'], content: json_encode(['csp-report' => [
            'document-uri' => 'https://nicolascataluna.fr/',
            'violated-directive' => 'script-src-elem',
            'blocked-uri' => 'https://exemple.test/x.js',
        ]]));

        self::assertResponseStatusCodeSame(204);
    }
}
