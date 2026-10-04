<?php

namespace App\Tests;

use App\Entity\Competence;
use App\Entity\Journal;
use App\Entity\Utilisateur;
use App\Repository\JournalRepository;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class JournalTest extends WebTestCase
{
    private static function derniere(): Journal
    {
        return self::getContainer()->get(JournalRepository::class)->findOneBy([], ['id' => 'DESC']);
    }

    public function testUneConnexionRefuseeEstJournalisee(): void
    {
        $client = static::createClient();
        $client->request('GET', '/login');
        $client->submitForm('Se connecter', ['_username' => 'intrus', '_password' => 'mauvais-mot-de-passe']);

        $entree = self::derniere();
        self::assertSame(Journal::CONNEXION_REFUSEE, $entree->getType());
        self::assertSame('intrus', $entree->getUtilisateur()); // identifiant tenté
        self::assertNotNull($entree->getIp());
    }

    public function testUneModificationDansLAdminEstJournalisee(): void
    {
        $client = static::createClient();
        self::connecterAdmin($client);
        $competence = self::getContainer()->get(EntityManagerInterface::class)
            ->getRepository(Competence::class)->findOneBy(['nom' => 'Docker']);

        $client->request('GET', '/admin/competence/'.$competence->getId().'/edit');
        $client->submitForm('Sauvegarder les modifications', ['Competence[position]' => $competence->getPosition()]);

        $entree = self::derniere();
        self::assertSame(Journal::MODIFICATION, $entree->getType());
        self::assertSame('Modification — Compétence « Docker »', $entree->getMessage());
        self::assertSame('admin-test', $entree->getUtilisateur());
    }

    public function testLeFormulaireDeContactEstJournalise(): void
    {
        $client = static::createClient();
        $message = ['contact[nom]' => 'Ada', 'contact[email]' => 'ada@example.com', 'contact[message]' => 'Un message assez long.'];

        $client->request('GET', '/contact');
        $client->submitForm('Envoyer', $message);
        self::assertSame(Journal::CONTACT_ENVOYE, self::derniere()->getType());

        $client->request('GET', '/contact');
        $client->submitForm('Envoyer', $message + ['contact[website]' => 'spam']);
        self::assertSame(Journal::SPAM_BLOQUE, self::derniere()->getType());
    }

    public function testLaPurgeSupprimeLesEntreesAnciennes(): void
    {
        self::bootKernel();
        $connexion = self::getContainer()->get(Connection::class);
        $connexion->executeStatement("INSERT INTO journal (date, type, message) VALUES (NOW() - INTERVAL '13 months', 'connexion', 'ancienne')");
        $connexion->executeStatement("INSERT INTO journal (date, type, message) VALUES (NOW() - INTERVAL '1 month', 'connexion', 'récente')");

        self::getContainer()->get(JournalRepository::class)->purgerAvant(new \DateTimeImmutable('-12 months'));

        $messages = $connexion->fetchFirstColumn("SELECT message FROM journal WHERE message IN ('ancienne', 'récente')");
        self::assertSame(['récente'], $messages);
        $connexion->executeStatement("DELETE FROM journal WHERE message = 'récente'");
    }

    private static function connecterAdmin(KernelBrowser $client): void
    {
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $admin = $entityManager->getRepository(Utilisateur::class)->findOneBy(['identifiant' => 'admin-test']);
        if (!$admin) {
            $admin = (new Utilisateur())->setIdentifiant('admin-test')->setMotDePasse('aucun-mot-de-passe');
            $entityManager->persist($admin);
            $entityManager->flush();
        }
        $client->loginUser($admin);
    }
}
