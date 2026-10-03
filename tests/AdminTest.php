<?php

namespace App\Tests;

use App\Entity\Competence;
use App\Entity\Projet;
use App\Entity\Utilisateur;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class AdminTest extends WebTestCase
{
    /** Compte de test enregistré en base (créé au premier besoin), connecté sans passer par le formulaire */
    private static function loginAdmin(KernelBrowser $client): void
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

    public function testUnCompteCreeDansLAdminPeutSeConnecter(): void
    {
        $client = static::createClient();
        self::loginAdmin($client);
        $client->request('GET', '/admin/utilisateur/new');
        $client->submitForm('Créer', [
            'Utilisateur[identifiant]' => 'redacteur',
            'Utilisateur[nouveauMotDePasse][first]' => 'un-mot-de-passe-solide',
            'Utilisateur[nouveauMotDePasse][second]' => 'un-mot-de-passe-solide',
        ]);
        self::assertResponseRedirects();

        // Enregistré sous forme d'empreinte, jamais en clair
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $compte = $entityManager->getRepository(Utilisateur::class)->findOneBy(['identifiant' => 'redacteur']);
        self::assertNotSame('un-mot-de-passe-solide', $compte->getPassword());

        // Connexion par le vrai formulaire avec ce mot de passe
        $client->request('GET', '/logout');
        $client->request('GET', '/login');
        $client->submitForm('Se connecter', ['_username' => 'redacteur', '_password' => 'un-mot-de-passe-solide']);
        self::assertResponseRedirects('/admin');

        $entityManager->remove($entityManager->getRepository(Utilisateur::class)->findOneBy(['identifiant' => 'redacteur']));
        $entityManager->flush();
    }

    public function testLeTableauDeBordResumeLActivite(): void
    {
        $client = static::createClient();
        self::loginAdmin($client);
        $client->request('GET', '/admin');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Tableau de bord');
        self::assertSelectorTextContains('.card', 'demande(s) de contact ce mois-ci');
    }

    public function testLAdministrationEstFermeeAuxVisiteurs(): void
    {
        $client = static::createClient();
        $client->request('GET', '/admin/competence');

        self::assertResponseRedirects('/login');
    }

    public function testLaPageDeConnexionSAffiche(): void
    {
        static::createClient()->request('GET', '/login');

        self::assertResponseIsSuccessful();
        self::assertSelectorExists('input[name="_csrf_token"]');
    }

    public static function listes(): iterable
    {
        foreach (['categorie-competence', 'competence', 'projet', 'passion', 'etape-parcours', 'utilisateur', 'demande-contact', 'journal', 'cv-profil', 'experience', 'cv-competence', 'langue', 'centre-interet'] as $liste) {
            yield $liste => [$liste];
        }
    }

    #[DataProvider('listes')]
    public function testLAdministrateurVoitChaqueListe(string $liste): void
    {
        $client = static::createClient();
        self::loginAdmin($client);
        $client->request('GET', '/admin/'.$liste);

        self::assertResponseIsSuccessful();
    }

    public function testUneCategorieUtiliseeNePeutPasEtreSupprimee(): void
    {
        $client = static::createClient();
        self::loginAdmin($client);
        $crawler = $client->request('GET', '/admin/categorie-competence');
        $token = $crawler->filter('#action-confirmation-form input[name="token"], form input[name="token"]')->attr('value');

        $client->request('POST', '/admin/categorie-competence/1/delete', ['token' => $token]);

        self::assertResponseStatusCodeSame(302); // message d'erreur et retour à la liste, pas d'erreur 500
        $client->followRedirect();
        self::assertSelectorTextContains('.alert-danger', 'contient encore');
        self::assertSelectorTextContains('body', 'Front-End'); // toujours là
    }

    /** Texte détaillé et liens d'un projet : modifiables, et une adresse invalide est refusée */
    public function testLaPageDUnProjetSeRemplitDepuisLAdmin(): void
    {
        $client = static::createClient();
        self::loginAdmin($client);
        $projet = self::getContainer()->get(EntityManagerInterface::class)
            ->getRepository(Projet::class)->findOneBy(['slug' => 'pendu']);
        $url = '/admin/projet/'.$projet->getId().'/edit';

        $client->request('GET', $url);
        $client->submitForm('Sauvegarder les modifications', ['Projet[depot]' => 'pas une adresse']);
        self::assertResponseStatusCodeSame(422);

        $client->request('GET', $url);
        $client->submitForm('Sauvegarder les modifications', ['Projet[details]' => "Premier paragraphe.\n\nSecond paragraphe."]);
        self::assertResponseRedirects();
        $page = $client->request('GET', '/projects/pendu');
        self::assertCount(2, $page->filter('.projet-texte p'));

        // Remis en état pour les autres tests
        $client->request('GET', $url);
        $client->submitForm('Sauvegarder les modifications', ['Projet[details]' => '']);
    }

    public function testUneModificationEstEnregistreeEtValidee(): void
    {
        $client = static::createClient();
        self::loginAdmin($client);
        $competence = self::getContainer()->get(EntityManagerInterface::class)
            ->getRepository(Competence::class)->findOneBy(['nom' => 'Docker']);

        // Nom vide : refusé, rien n'est enregistré
        $client->request('GET', '/admin/competence/'.$competence->getId().'/edit');
        $client->submitForm('Sauvegarder les modifications', ['Competence[nom]' => '']);
        self::assertResponseStatusCodeSame(422);

        // Nom valide : enregistré (puis remis en état pour les autres tests)
        $client->request('GET', '/admin/competence/'.$competence->getId().'/edit');
        $client->submitForm('Sauvegarder les modifications', ['Competence[nom]' => 'Docker / Compose']);
        self::assertResponseRedirects();
        $client->request('GET', '/admin/competence/'.$competence->getId().'/edit');
        $client->submitForm('Sauvegarder les modifications', ['Competence[nom]' => 'Docker']);
        self::assertResponseRedirects();
    }
}
