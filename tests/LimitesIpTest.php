<?php

namespace App\Tests;

use App\Entity\Journal;
use App\Security\BlocIp;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;

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
