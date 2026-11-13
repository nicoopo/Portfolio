<?php

namespace App\Tests;

use App\Entity\Journal;
use App\EventListener\LimiteMcpListener;
use App\Security\BlocIp;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\RateLimiter\Storage\InMemoryStorage;

/**
 * Limites anti-abus : en IPv6, c'est tout le bloc /64 qui compte (une box change d'adresse à volonté dans le sien).
 */
final class LimitesIpTest extends WebTestCase
{
    public function testLaCleEstLeBloc64EnIpv6EtLAdresseEntiereEnIpv4(): void
    {
        $cle = fn (string $ip) => BlocIp::de(Request::create('/', server: ['REMOTE_ADDR' => $ip]));

        self::assertSame($cle('2a01:e0a:c06:3b90::1'), $cle('2a01:e0a:c06:3b90:dead:beef:1:2'));
        self::assertNotSame($cle('2a01:e0a:c06:3b90::1'), $cle('2a01:e0a:c06:3b91::1'));
        self::assertNotSame($cle('203.0.113.1'), $cle('203.0.113.2'));
    }

    /** Serveur MCP : au-delà de la limite, 429 en JSON-RPC avec Retry-After ; ni le préflight CORS ni les autres pages ne comptent */
    public function testLeServeurMcpEstLimite(): void
    {
        $listener = new LimiteMcpListener(new RateLimiterFactory(['id' => 'mcp', 'policy' => 'fixed_window', 'limit' => 1, 'interval' => '1 minute'], new InMemoryStorage()));
        $appel = function (string $chemin, string $methode = 'POST') use ($listener): ?Response {
            $event = new RequestEvent($this->createStub(HttpKernelInterface::class), Request::create($chemin, $methode), HttpKernelInterface::MAIN_REQUEST);
            $listener($event);

            return $event->getResponse();
        };

        self::assertNull($appel('/mcp'));
        self::assertNull($appel('/mcp', 'OPTIONS'));
        self::assertNull($appel('/contact'));
        $refus = $appel('/mcp');
        self::assertSame(Response::HTTP_TOO_MANY_REQUESTS, $refus?->getStatusCode());
        self::assertGreaterThan(0, (int) $refus->headers->get('Retry-After'));
        self::assertSame(-32000, json_decode($refus->getContent(), true)['error']['code']);
    }

    /** Arrivées du globe : un script atteint sa limite par IP sans entamer la limite commune ; une autre IP passe encore */
    public function testLesArriveesSontLimiteesParIp(): void
    {
        $client = static::createClient();
        $client->disableReboot();
        self::getContainer()->set('limiter.arrivees_ip', new RateLimiterFactory(['id' => 'arrivees_ip', 'policy' => 'fixed_window', 'limit' => 1, 'interval' => '1 hour'], new InMemoryStorage()));
        $envoyer = function (string $ip) use ($client): int {
            $client->request('POST', '/arrivees', server: ['REMOTE_ADDR' => $ip, 'CONTENT_TYPE' => 'application/json'], content: json_encode(['fuseau' => 'Europe/Paris']));

            return $client->getResponse()->getStatusCode();
        };

        self::assertSame(Response::HTTP_NO_CONTENT, $envoyer('2001:db8:5:6::1'));
        self::assertSame(Response::HTTP_TOO_MANY_REQUESTS, $envoyer('2001:db8:5:6::2')); // même bloc /64
        self::assertSame(Response::HTTP_NO_CONTENT, $envoyer('203.0.113.7'));
    }

    /** Un robot (champ piège rempli) est limité comme tout le monde : un flot ne remplit pas le journal */
    public function testLeChampPiegeNEchappePasALaLimite(): void
    {
        $client = static::createClient();
        $client->disableReboot(); // garde le limiteur remplacé d'une requête à l'autre
        self::getContainer()->set('limiter.contact', new RateLimiterFactory(['id' => 'contact', 'policy' => 'fixed_window', 'limit' => 1, 'interval' => '1 hour'], new InMemoryStorage()));
        $journal = static::getContainer()->get(EntityManagerInterface::class)->getRepository(Journal::class);
        $avant = $journal->count(['type' => Journal::SPAM_BLOQUE]);

        for ($i = 0; $i < 3; ++$i) {
            $client->request('GET', '/contact');
            $client->submitForm('Envoyer', ['contact[nom]' => 'Robot', 'contact[email]' => 'robot@example.com', 'contact[message]' => 'Achetez mes pilules.', 'contact[website]' => 'spam']);
        }

        self::assertSame($avant + 1, $journal->count(['type' => Journal::SPAM_BLOQUE]));
    }

    /** Changer d'adresse dans son /64 ne redonne pas d'essais : le 6e est refusé avant même de vérifier le mot de passe */
    public function testLaConnexionEstLimiteeParBloc64(): void
    {
        $client = static::createClient();
        $identifiant = 'pirate-'.bin2hex(random_bytes(4)); // nouvel identifiant : l'état des limiteurs survit entre deux lancements
        $essai = bin2hex(random_bytes(8)); // forcément faux ; tiré au hasard, pas écrit en dur (le scanner de secrets le prendrait pour un vrai)

        for ($i = 1; $i <= 6; ++$i) {
            $client->request('GET', '/login', server: ['REMOTE_ADDR' => '2001:db8:1:2::'.$i]);
            $client->submitForm('Se connecter', ['_username' => $identifiant, '_password' => $essai], serverParameters: ['REMOTE_ADDR' => '2001:db8:1:2::'.$i]);
        }

        $dernier = static::getContainer()->get(EntityManagerInterface::class)->getRepository(Journal::class)
            ->findOneBy(['utilisateur' => $identifiant], ['id' => 'DESC']);
        self::assertStringContainsString('Too many failed login attempts', $dernier->getMessage());
    }
}
