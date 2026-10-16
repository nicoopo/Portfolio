<?php

namespace App\Tests;

use App\Entity\DemandeContact;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Console\Tester\CommandTester;

final class ContactTest extends WebTestCase
{
    private const MESSAGE = [
        'contact[nom]' => 'Ada Lovelace',
        'contact[email]' => 'ada@example.com',
        'contact[message]' => 'Bonjour, une alternance vous intéresse ?',
    ];

    public function testLeMessageEstEnvoye(): void
    {
        $client = static::createClient();
        $client->request('GET', '/contact');
        $client->submitForm('Envoyer', self::MESSAGE);

        self::assertEmailCount(1);
        $email = self::getMailerMessage();
        self::assertEmailAddressContains($email, 'reply-to', 'ada@example.com');
        self::assertEmailTextBodyContains($email, 'une alternance vous intéresse');
        self::assertResponseRedirects('/contact#formulaire');

        $client->followRedirect();
        self::assertSelectorTextContains('.contact-flash--success', 'Merci');

        // Conservé en base, marqué envoyé
        $demande = self::demandes()->findOneBy([], ['id' => 'DESC']);
        self::assertSame('ada@example.com', $demande->getEmail());
        self::assertSame(DemandeContact::STATUT_ENVOYE, $demande->getStatut());
        self::assertSame('fr', $demande->getLangue());
    }

    public function testUnRobotNEnvoieRien(): void
    {
        $client = static::createClient();
        $avant = self::demandes()->count([]);
        $client->request('GET', '/contact');
        $client->submitForm('Envoyer', self::MESSAGE + ['contact[website]' => 'https://spam.example']);

        self::assertEmailCount(0);
        self::assertSame($avant, self::demandes()->count([])); // rien enregistré
        self::assertResponseRedirects('/contact#formulaire'); // même réponse qu'un envoi réussi
    }

    private static function demandes(): EntityRepository
    {
        return self::getContainer()->get(EntityManagerInterface::class)->getRepository(DemandeContact::class);
    }

    public function testUnMessageInvalideEstRefuse(): void
    {
        $client = static::createClient();
        $client->request('GET', '/contact');
        $client->submitForm('Envoyer', ['contact[email]' => 'pas-un-email', 'contact[message]' => 'court'] + self::MESSAGE);

        self::assertEmailCount(0);
        self::assertResponseStatusCodeSame(422);
        self::assertSelectorExists('.contact-form ul li');
    }

    /** app:contact:purge : seules les demandes de plus de 12 mois disparaissent */
    public function testLaPurgeSupprimeLesDemandesAnciennes(): void
    {
        $kernel = self::bootKernel();
        $connexion = self::getContainer()->get(Connection::class);
        $inserer = "INSERT INTO demande_contact (nom, email, message, langue, statut, recu_le) VALUES (?, 'x@example.com', 'm', 'fr', 'envoye', NOW() - INTERVAL '%s')";
        $connexion->executeStatement(\sprintf($inserer, '13 months'), ['purge-ancienne']);
        $connexion->executeStatement(\sprintf($inserer, '1 month'), ['purge-recente']);

        $commande = new CommandTester((new Application($kernel))->find('app:contact:purge'));
        $commande->execute([]);

        $commande->assertCommandIsSuccessful();
        $noms = $connexion->fetchFirstColumn("SELECT nom FROM demande_contact WHERE nom LIKE 'purge-%'");
        self::assertSame(['purge-recente'], $noms);
        $connexion->executeStatement("DELETE FROM demande_contact WHERE nom = 'purge-recente'");
    }
}
