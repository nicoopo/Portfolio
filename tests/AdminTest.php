<?php

namespace App\Tests;

use App\Entity\Competence;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class AdminTest extends WebTestCase
{
    /** L'administrateur tel que défini dans security.yaml (un utilisateur fabriqué à la main serait déconnecté au rechargement) */
    private static function loginAdmin(KernelBrowser $client): void
    {
        $client->loginUser(self::getContainer()->get('security.user.provider.concrete.admin')->loadUserByIdentifier('admin'));
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
        foreach (['categorie-competence', 'competence', 'projet', 'passion', 'etape-parcours'] as $liste) {
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
