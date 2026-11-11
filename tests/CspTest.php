<?php

namespace App\Tests;

use App\EventListener\ContentSecurityPolicyListener;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

final class CspTest extends WebTestCase
{
    private const EN_TETE = 'Content-Security-Policy';

    /** Les scripts de la page portent le nonce annoncé dans l'en-tête, et il change à chaque requête */
    public function testLesScriptsPortentLeNonceDeLaRequete(): void
    {
        $client = static::createClient(server: ['HTTP_ACCEPT_LANGUAGE' => 'fr-FR']); // sinon / redirige vers /en/
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

    /** Dev : le script injecté par UX Inspector reçoit le nonce de l'en-tête ; une page sans lui n'est pas touchée */
    public function testLeScriptDeLInspecteurRecoitLeNonce(): void
    {
        $csp = new ContentSecurityPolicyListener();
        $evenement = fn (string $html) => new ResponseEvent(static::createKernel(), new Request(), HttpKernelInterface::MAIN_REQUEST, new Response($html));

        $avec = $evenement('<body><script type="module" src="/_ux/inspector.js"></script></body>');
        $csp($avec);
        $csp->nonceInspecteur($avec);
        preg_match("/'nonce-([^']+)'/", $avec->getResponse()->headers->get(self::EN_TETE), $nonce);
        self::assertStringContainsString('<script type="module" nonce="'.$nonce[1].'" src="/_ux/inspector.js">', $avec->getResponse()->getContent());

        $sans = $evenement($html = '<body><script src="/ailleurs.js"></script></body>');
        $csp->nonceInspecteur($sans);
        self::assertSame($html, $sans->getResponse()->getContent());
    }
}
