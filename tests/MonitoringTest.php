<?php

namespace App\Tests;

use App\Controller\MonitoringController;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\RateLimiter\Storage\InMemoryStorage;

final class MonitoringTest extends TestCase
{
    private const DSN = 'http://clepublique@web:8000/2';
    // Le navigateur annonce un autre DSN : il doit être ignoré
    private const ENVELOPPE = '{"dsn":"https://autre@exemple.test/9"}'."\n".'{"type":"event"}'."\n".'{"message":"boom"}';

    private static function controleur(MockHttpClient $client, string $dsn = self::DSN, int $limite = 30): MonitoringController
    {
        $limiteur = new RateLimiterFactory(['id' => 'erreurs_js', 'policy' => 'sliding_window', 'limit' => $limite, 'interval' => '1 minute'], new InMemoryStorage());

        return new MonitoringController($client, $limiteur, $dsn);
    }

    private static function requete(string $contenu = self::ENVELOPPE, string $site = 'same-origin'): Request
    {
        return Request::create('/monitoring', 'POST', server: ['HTTP_SEC_FETCH_SITE' => $site], content: $contenu);
    }

    /** L'enveloppe part telle quelle vers le projet de SENTRY_DSN, quel que soit le DSN annoncé par le navigateur */
    public function testLEnveloppeEstRelayeeAuProjetDuServeur(): void
    {
        $envois = [];
        $client = new MockHttpClient(function (string $methode, string $url, array $options) use (&$envois) {
            $envois[] = [$methode, $url, $options];

            return new MockResponse('{}');
        });

        self::assertSame(204, self::controleur($client)(self::requete())->getStatusCode());
        self::assertCount(1, $envois);
        [$methode, $url, $options] = $envois[0];
        self::assertSame('POST', $methode);
        self::assertSame('http://web:8000/api/2/envelope/', $url);
        self::assertContains('X-Sentry-Auth: Sentry sentry_version=7, sentry_key=clepublique', $options['headers']);
        self::assertSame(self::ENVELOPPE, $options['body']);
    }

    public function testSansDsnRienNEstEnvoye(): void
    {
        $client = new MockHttpClient(fn () => self::fail('aucun envoi attendu'));

        self::assertSame(204, self::controleur($client, dsn: '')(self::requete())->getStatusCode());
    }

    public function testLesRequetesRefuseesNeSontPasRelayees(): void
    {
        $client = new MockHttpClient(fn () => self::fail('aucun envoi attendu'));
        $controleur = self::controleur($client);

        self::assertSame(403, $controleur(self::requete(site: 'cross-site'))->getStatusCode());
        self::assertSame(400, $controleur(self::requete(''))->getStatusCode());
        self::assertSame(400, $controleur(self::requete(str_repeat('x', 200_001)))->getStatusCode());
    }

    public function testUnFlotDEnvoisEstLimite(): void
    {
        $controleur = self::controleur(new MockHttpClient(new MockResponse('{}')), limite: 2);

        $controleur(self::requete());
        $controleur(self::requete());
        self::assertSame(429, $controleur(self::requete())->getStatusCode());
    }

    /** GlitchTip arrêté : le visiteur ne voit rien */
    public function testUnePanneDeGlitchtipNeCasseRien(): void
    {
        $client = new MockHttpClient(new MockResponse('', ['error' => 'Connexion refusée']));

        self::assertSame(204, self::controleur($client)(self::requete())->getStatusCode());
    }
}
