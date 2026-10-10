<?php

namespace App\Tests;

use App\Entity\Article;
use App\Entity\Candidature;
use App\Entity\Competence;
use App\Entity\Journal;
use App\Entity\LienRecruteur;
use App\Entity\Maintenant;
use App\Entity\MessageLivreOr;
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

        // Graphiques : une connexion refusée aujourd'hui compte dans le dernier jour de l'activité
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $entityManager->persist(new Journal(Journal::CONNEXION_REFUSEE, 'Test du graphique', null, null));
        $entityManager->flush();
        $crawler = $client->request('GET', '/admin');
        self::assertCount(3, $crawler->filter('canvas[data-graphique]'));
        $activite = json_decode($crawler->filter('canvas[data-graphique]')->first()->attr('data-graphique'), true);
        $refusees = array_column($activite['series'], 'data', 'label')['Connexions refusées'];
        self::assertCount(30, $refusees);
        self::assertGreaterThanOrEqual(1, end($refusees));
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
        foreach (['categorie-competence', 'competence', 'projet', 'passion', 'etape-parcours', 'utilisateur', 'demande-contact', 'journal', 'cv-profil', 'experience', 'cv-competence', 'langue', 'centre-interet', 'article', 'maintenant', 'lien-recruteur', 'message-livre-or', 'candidature'] as $liste) {
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

    /** Article créé dans l'admin sans date de publication : brouillon, invisible sur le site */
    public function testUnArticleSansDateEstUnBrouillon(): void
    {
        $client = static::createClient();
        self::loginAdmin($client);
        $client->request('GET', '/admin/article/new');
        $client->submitForm('Créer', [
            'Article[titre]' => 'Mon brouillon',
            'Article[slug]' => 'mon-brouillon',
            'Article[resume]' => 'En cours d’écriture.',
            'Article[contenu]' => "## Plan\n\n- idée",
        ]);
        self::assertResponseRedirects();

        $client->request('GET', '/articles/mon-brouillon');
        self::assertResponseStatusCodeSame(404);

        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $entityManager->remove($entityManager->getRepository(Article::class)->findOneBy(['slug' => 'mon-brouillon']));
        $entityManager->flush();
    }

    /** Page /now : modifiée dans l'admin, rendue depuis le Markdown, datée du dernier enregistrement */
    public function testLaPageNowSeModifieDepuisLAdmin(): void
    {
        $client = static::createClient();
        self::loginAdmin($client);
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $maintenant = $entityManager->getRepository(Maintenant::class)->findOneBy([]);
        $avant = $maintenant->getContenu();
        $url = '/admin/maintenant/'.$maintenant->getId().'/edit';

        $client->request('GET', $url);
        $client->submitForm('Sauvegarder les modifications', ['Maintenant[contenu]' => "## J’apprends\n\nLes **tests** fonctionnels."]);
        self::assertResponseRedirects();
        $client->request('GET', '/now');
        self::assertSelectorTextContains('.article-texte h2', 'J’apprends');
        self::assertSelectorTextContains('.article-texte strong', 'tests');
        self::assertSelectorExists('time[datetime="'.date('Y-m-d').'"]');

        // Remis en état pour les autres tests
        $client->request('GET', $url);
        $client->submitForm('Sauvegarder les modifications', ['Maintenant[contenu]' => $avant]);
    }

    /** Lien recruteur : créé dans l'admin, l'accueil salue l'entreprise ; visite comptée (pas celles de l'admin), même après la redirection de langue */
    public function testUnLienRecruteurPersonnaliseLAccueil(): void
    {
        $client = static::createClient();
        self::loginAdmin($client);
        $client->request('GET', '/admin/lien-recruteur/new');
        $client->submitForm('Créer', ['LienRecruteur[entreprise]' => 'Acme', 'LienRecruteur[poste]' => 'développeur Symfony en alternance']);
        self::assertResponseRedirects();
        // Relu à chaque fois : le noyau redémarre entre deux requêtes
        $relire = fn () => self::getContainer()->get(EntityManagerInterface::class)->getRepository(LienRecruteur::class)->findOneBy(['entreprise' => 'Acme']);
        $code = $relire()->getCode();
        self::assertMatchesRegularExpression('/^[0-9a-f]{10}$/', $code);

        // L'admin connecté ne compte pas
        $client->request('GET', '/?pour='.$code, server: ['HTTP_ACCEPT_LANGUAGE' => 'fr-FR']);
        self::assertSelectorTextContains('.recruteur-bonjour', 'Bonjour l’équipe de Acme !');
        self::assertSame(0, $relire()->getVisites());

        $client->request('GET', '/logout');
        $client->getCookieJar()->clear();

        // Robot d'aperçu de lien (LinkedIn, Slack…) : salué, mais ni visite ni alerte
        $client->request('GET', '/?pour='.$code, server: ['HTTP_ACCEPT_LANGUAGE' => 'fr-FR', 'HTTP_USER_AGENT' => 'LinkedInBot/1.0']);
        self::assertSelectorTextContains('.recruteur-bonjour', 'Bonjour l’équipe de Acme !');
        self::assertSame(0, $relire()->getVisites());
        $client->getCookieJar()->clear(); // cookie « langue » posé : sans ça, plus de redirection vers /en/

        // Visiteur au navigateur anglais : redirigé vers /en/ avec son lien, salué en anglais, visite comptée
        $client->request('GET', '/?pour='.$code, server: ['HTTP_ACCEPT_LANGUAGE' => 'en-GB']);
        self::assertResponseRedirects('http://localhost/en/?pour='.$code);
        $client->followRedirect();
        self::assertSelectorTextContains('.recruteur-bonjour', 'Hello to the Acme team!');
        self::assertSelectorTextContains('.recruteur', 'développeur Symfony en alternance');
        self::assertSame(1, $relire()->getVisites());
        self::assertNotNull($relire()->getPremiereVisite());

        // Code inconnu : accueil normal
        $client->request('GET', '/en/?pour=inconnu');
        self::assertSelectorNotExists('.recruteur');

        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $entityManager->remove($entityManager->getRepository(LienRecruteur::class)->findOneBy(['code' => $code]));
        $entityManager->flush();
    }

    /** Frise des projets : un projet daté dans l'admin y apparaît, à son année, avec ses technos nouvelles ; le filtre connaît ses technos */
    public function testUnProjetDateApparaitDansLaFrise(): void
    {
        $client = static::createClient();
        self::loginAdmin($client);
        $id = self::getContainer()->get(EntityManagerInterface::class)->getRepository(Projet::class)->findOneBy(['slug' => 'pendu'])->getId();

        $client->request('GET', '/admin/projet/'.$id.'/edit');
        $client->submitForm('Sauvegarder les modifications', ['Projet[annee]' => '1999']);
        self::assertResponseStatusCodeSame(422);

        $client->request('GET', '/admin/projet/'.$id.'/edit');
        $client->submitForm('Sauvegarder les modifications', ['Projet[annee]' => '2001']);
        $crawler = $client->request('GET', '/projects/frise');
        self::assertSame('2001', $crawler->filter('.frise-projets-annee h2')->first()->text()); // le plus ancien d'abord
        $carte = $crawler->filter('.frise-projet a[href="/projects/pendu"]')->closest('.frise-projet');
        self::assertGreaterThan(0, $carte->filter('.frise-projet-nouveau')->count()); // premier projet de la frise : tout est nouveau
        self::assertContains('Java', $crawler->filter('.frise-filtres button')->extract(['_text']));

        $client->request('GET', '/admin/projet/'.$id.'/edit');
        $client->submitForm('Sauvegarder les modifications', ['Projet[annee]' => '']);
    }

    /** Livre d'or : un message envoyé attend la modération ; approuvé dans l'admin, il devient une étoile. Le robot du champ piège n'enregistre rien */
    public function testUnMessageDuLivreDOrApparaitApresModeration(): void
    {
        $client = static::createClient(server: ['HTTP_ACCEPT_LANGUAGE' => 'fr-FR']);
        $depot = fn () => self::getContainer()->get(EntityManagerInterface::class)->getRepository(MessageLivreOr::class);
        $avant = $depot()->count([]);

        $client->request('GET', '/livre-d-or');
        $client->submitForm('Envoyer mon étoile', ['livre_or[prenom]' => 'Robot', 'livre_or[message]' => 'Achetez mes pilules', 'livre_or[website]' => 'spam.example']);
        self::assertResponseRedirects();
        self::assertSame($avant, $depot()->count([]));

        $client->request('GET', '/livre-d-or');
        $client->submitForm('Envoyer mon étoile', ['livre_or[prenom]' => 'Camille', 'livre_or[message]' => 'Superbe univers, bravo !']);
        $client->followRedirect();
        self::assertSelectorTextContains('.contact-flash--success', 'Merci');
        self::assertSelectorTextNotContains('body', 'Superbe univers, bravo !'); // pas encore approuvé

        $message = $depot()->findOneBy(['prenom' => 'Camille']);
        self::assertSame('fr', $message->getLangue());
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $entityManager->getRepository(MessageLivreOr::class)->find($message->getId())->setApprouve(true);
        $entityManager->flush();

        $client->request('GET', '/livre-d-or');
        self::assertSelectorTextContains('.livre-or-etoile', 'Superbe univers, bravo !');
        self::assertSelectorTextContains('.livre-or-etoile', 'Camille');

        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $entityManager->remove($entityManager->getRepository(MessageLivreOr::class)->find($message->getId()));
        $entityManager->flush();
    }

    /** Candidature saisie dans l'admin : relance calculée, comptée sur le tableau de bord une fois la date passée */
    public function testUneCandidatureSeSuitDansLAdmin(): void
    {
        $client = static::createClient();
        self::loginAdmin($client);
        $client->request('GET', '/admin/candidature/new');
        $client->submitForm('Créer', [
            'Candidature[entreprise]' => 'Initech',
            'Candidature[poste]' => 'Développeur PHP',
            'Candidature[statut]' => 'envoyee',
            'Candidature[envoyeeLe]' => (new \DateTimeImmutable('-10 days'))->format('Y-m-d'),
        ]);
        self::assertResponseRedirects();

        $client->request('GET', '/admin/candidature');
        self::assertSelectorTextContains('body', 'Initech');
        self::assertSelectorTextContains('body', '⚠');
        $client->request('GET', '/admin');
        self::assertSelectorTextContains('body', 'candidature(s) à relancer');

        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $entityManager->remove($entityManager->getRepository(Candidature::class)->findOneBy(['entreprise' => 'Initech']));
        $entityManager->flush();
    }

    /** Statistiques : une page vue par un vrai navigateur venant de LinkedIn est comptée ; robots et administrateur ne le sont pas */
    public function testLesVisitesSontCompteesSansRobotsNiAdmin(): void
    {
        $client = static::createClient(server: ['HTTP_ACCEPT_LANGUAGE' => 'fr-FR']);
        $connection = self::getContainer()->get(\Doctrine\DBAL\Connection::class);
        $nombre = fn (string $source) => (int) $connection->fetchOne("SELECT COALESCE(SUM(nombre), 0) FROM visite_jour WHERE chemin = '/projects/pendu' AND langue = 'de' AND source = ?", [$source]);
        $avant = $nombre('linkedin.com');

        $client->request('GET', '/de/projects/pendu', server: ['HTTP_USER_AGENT' => 'Mozilla/5.0 Firefox/140', 'HTTP_REFERER' => 'https://www.linkedin.com/feed/']);
        self::assertSame($avant + 1, $nombre('linkedin.com'));

        $client->request('GET', '/de/projects/pendu', server: ['HTTP_USER_AGENT' => 'Mozilla/5.0 (compatible; Googlebot/2.1)', 'HTTP_REFERER' => 'https://www.linkedin.com/feed/']);
        self::loginAdmin($client);
        $client->request('GET', '/de/projects/pendu', server: ['HTTP_USER_AGENT' => 'Mozilla/5.0 Firefox/140', 'HTTP_REFERER' => 'https://www.linkedin.com/feed/']);
        self::assertSame($avant + 1, $nombre('linkedin.com'));

        $client->request('GET', '/admin');
        self::assertSelectorTextContains('body', 'Pages vues sur 30 jours');
        self::assertSelectorTextContains('body', 'linkedin.com');
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

        // Traduction : affichée dans sa langue ; vidée, la page retombe sur le français
        $client->request('GET', $url);
        $client->submitForm('Sauvegarder les modifications', ['Projet[traductions][it][titre]' => 'Impiccato modificato']);
        $client->request('GET', '/it/projects/pendu');
        self::assertSelectorTextContains('h1', 'Impiccato modificato');
        $client->request('GET', $url);
        $client->submitForm('Sauvegarder les modifications', ['Projet[traductions][it][titre]' => '']);
        $client->request('GET', '/it/projects/pendu');
        self::assertSelectorTextContains('h1', 'Jeu du pendu');

        // Remis en état pour les autres tests
        $client->request('GET', $url);
        $client->submitForm('Sauvegarder les modifications', ['Projet[details]' => '', 'Projet[traductions][it][titre]' => 'L\'impiccato']);
    }

    /** Image envoyée depuis l'admin : rangée dans public/uploads/projets, affichée à la place de celle du dépôt */
    public function testUneImageDeProjetSEnvoieDepuisLAdmin(): void
    {
        $client = static::createClient();
        self::loginAdmin($client);
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $url = '/admin/projet/'.$entityManager->getRepository(Projet::class)->findOneBy(['slug' => 'pendu'])->getId().'/edit';
        $dossier = self::getContainer()->getParameter('kernel.project_dir').'/public/uploads/projets';

        // Un faux PNG (du texte) est refusé
        // (le client de test envoie le fichier sous son propre nom : on lui donne celui qu'aurait choisi l'utilisateur)
        $temporaire = sys_get_temp_dir().'/'.uniqid('upload-', true);
        mkdir($temporaire);
        file_put_contents($faux = $temporaire.'/faux.png', 'pas une image');
        $client->request('GET', $url);
        $client->submitForm('Sauvegarder les modifications', ['Projet[imageEnvoyee][file]' => $faux]);
        self::assertResponseStatusCodeSame(422);

        // Un vrai PNG trop large est accepté, renommé d'après son contenu, réduit à 1000 px et converti en WebP
        imagepng(imagecreatetruecolor(1500, 300), $png = $temporaire.'/Ma Capture.png');
        $client->request('GET', $url);
        $client->submitForm('Sauvegarder les modifications', ['Projet[imageEnvoyee][file]' => $png]);
        self::assertResponseRedirects();

        $projet = $entityManager->getRepository(Projet::class)->findOneBy(['slug' => 'pendu']);
        $entityManager->refresh($projet);
        self::assertMatchesRegularExpression('/^ma-capture-[0-9a-f]{40}\.webp$/', $projet->getImageEnvoyee());
        $taille = getimagesize($dossier.'/'.$projet->getImageEnvoyee());
        self::assertSame([1000, 200, 'image/webp'], [$taille[0], $taille[1], $taille['mime']]);
        $client->request('GET', '/projects/pendu');
        self::assertSelectorExists('.projet-image img[src="/uploads/projets/'.$projet->getImageEnvoyee().'"]');

        // Remis en état pour les autres tests
        unlink($dossier.'/'.$projet->getImageEnvoyee());
        array_map('unlink', [$faux, $png]);
        rmdir($temporaire);
        $entityManager->refresh($projet);
        $projet->setImageEnvoyee(null);
        $entityManager->flush();
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
