<?php

namespace App\Tests;

use App\Entity\Article;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class SmokeTest extends WebTestCase
{
    /** Sans ça, Request::create() envoie « en-us » et / redirige vers /en/ (LangueNavigateurListener) */
    private const NAVIGATEUR_FRANCAIS = ['HTTP_ACCEPT_LANGUAGE' => 'fr-FR'];

    public static function pages(): iterable
    {
        foreach (['/', '/projects', '/projects/portfolio', '/projects/frise', '/articles', '/now', '/uses', '/stats', '/livre-d-or', '/terminal', '/competences', '/competences/comparer', '/CV', '/contact', '/univers', '/cerveau', '/mentions-legales', '/confidentialite'] as $url) {
            yield $url => [$url];
            foreach (['en', 'es', 'de', 'it', 'pt'] as $langue) {
                yield "/$langue$url" => ["/$langue$url"];
            }
        }
    }

    /** Page anglaise : lang="en", texte traduit, et lien vers la même page en français */
    public function testLaVersionAnglaiseEstTraduite(): void
    {
        $crawler = static::createClient()->request('GET', '/en/competences');

        self::assertSelectorExists('html[lang="en"]');
        self::assertSelectorTextContains('h1', 'My Skills');
        self::assertAnySelectorTextContains('.skills-category h2', 'Networks / Infra'); // contenu de la base
        self::assertSame('http://localhost/competences', $crawler->filter('link[hreflang="fr"]')->attr('href'));
        self::assertSame('http://localhost/competences', $crawler->filter('#langues a[hreflang="fr"]')->attr('href'));
    }

    /** Autres langues : interface traduite, contenu de la base traduit, menu et hreflang vers les six langues */
    public function testLesAutresLanguesSontTraduites(): void
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/es/competences');
        self::assertSelectorExists('html[lang="es"]');
        self::assertSelectorTextContains('h1', 'Mis competencias');
        self::assertAnySelectorTextContains('.skills-category h2', 'Redes / Infra'); // contenu de la base
        self::assertCount(6, $crawler->filter('#langues a'));
        self::assertSame('true', $crawler->filter('#langues a[hreflang="es"]')->attr('aria-current'));
        self::assertCount(7, $crawler->filter('link[rel="alternate"][hreflang]')); // six langues + x-default

        $client->request('GET', '/de/projects/pendu');
        self::assertSelectorTextContains('h1', 'Galgenmännchen');

        $client->request('GET', '/pt/mentions-legales');
        self::assertSelectorTextContains('h1', 'Informação legal');
    }

    #[DataProvider('pages')]
    public function testPageRepond(string $url): void
    {
        static::createClient(server: self::NAVIGATEUR_FRANCAIS)->request('GET', $url);

        self::assertResponseIsSuccessful();
        self::assertSelectorCount(1, 'h1'); // un seul titre principal par page (SEO, lecteurs d'écran)
    }

    public function testCompetencesRenvoientAuCerveau(): void
    {
        $crawler = static::createClient()->request('GET', '/competences');

        self::assertGreaterThan(0, $crawler->filter('.skill-card a.skill-nom[href^="/cerveau#"]')->count());
        // Constellations : une étoile par compétence, même lien que sa carte ; les projets de chaque compétence sont liés
        self::assertSame($crawler->filter('.skill-card')->count(), $crawler->filter('svg.ciel-competences a.etoile')->count());
        self::assertSame($crawler->filter('.skill-card a.skill-nom')->extract(['href']), $crawler->filter('svg.ciel-competences a.etoile')->extract(['href']));
        self::assertGreaterThan(0, $crawler->filter('.skill-projets a[href^="/projects/"]')->count());
        // Système solaire : une planète par compétence, avec le même lien vers le cerveau que sa carte
        $systeme = json_decode($crawler->filter('.systeme-solaire')->attr('data-systeme-categories-value'), true);
        $planetes = array_merge(...array_column($systeme, 'competences'));
        self::assertSame($crawler->filter('.skill-card a.skill-nom')->extract(['href']), array_column($planetes, 'cerveau'));
    }

    /** Chaque carte de la liste mène à la page de son projet ; texte long, liens et compétences y sont */
    public function testChaqueProjetASaPage(): void
    {
        $client = static::createClient();
        $liens = $client->request('GET', '/projects')->filter('.projet-carte-titre a')->extract(['href']);
        self::assertContains('/projects/portfolio', $liens);

        foreach ($liens as $lien) {
            $client->request('GET', $lien);
            self::assertResponseIsSuccessful($lien);
        }

        $page = $client->request('GET', '/projects/portfolio');
        self::assertGreaterThan(1, $page->filter('.projet-texte p')->count());
        self::assertSelectorExists('a[href="https://github.com/nicoopo/Portfolio"]');
        self::assertSelectorExists('.projet-competences a[href^="/cerveau#"]');
        self::assertSame('http://localhost/partage/projet/portfolio.png', $page->filter('meta[property="og:image"]')->attr('content'));

        $client->request('GET', '/projects/inconnu');
        self::assertResponseStatusCodeSame(404);
    }

    /** Chaque souvenir du cerveau renvoie à une étape qui existe dans la frise de /univers */
    public function testLesSouvenirsRenvoientALaFrise(): void
    {
        $client = static::createClient();
        $souvenirs = json_decode($client->request('GET', '/cerveau')->filter('[data-brain-souvenirs-value]')->attr('data-brain-souvenirs-value'), true);
        $frise = $client->request('GET', '/univers');

        self::assertNotEmpty($souvenirs);
        foreach ($souvenirs as $souvenir) {
            self::assertStringStartsWith('/univers#', $souvenir['url']);
            self::assertCount(1, $frise->filter('.frise-etape'.strstr($souvenir['url'], '#')), $souvenir['url']);
        }
    }

    #[DataProvider('pages')]
    public function testPageAUnApercuDePartage(string $url): void
    {
        $crawler = static::createClient(server: self::NAVIGATEUR_FRANCAIS)->request('GET', $url);

        self::assertNotEmpty($crawler->filter('meta[name="description"]')->attr('content'));
        self::assertStringStartsWith('http', $crawler->filter('meta[property="og:image"]')->attr('content'));
    }

    /** Le sitemap liste toutes les pages publiques, dans les deux langues */
    public function testLeSitemapListeToutesLesPages(): void
    {
        $client = static::createClient();
        $client->request('GET', '/sitemap.xml');

        self::assertResponseHeaderSame('Content-Type', 'application/xml; charset=UTF-8');
        $sitemap = simplexml_load_string($client->getResponse()->getContent());
        $urls = array_map('strval', $sitemap->xpath('//*[local-name()="loc"]'));
        foreach (self::pages() as [$url]) {
            self::assertContains('http://localhost'.$url, $urls);
        }
    }

    /** Blog : article publié rendu depuis le Markdown (HTML brut échappé) ; brouillon et article programmé invisibles */
    public function testSeulsLesArticlesPubliesSontVisibles(): void
    {
        $client = static::createClient(server: self::NAVIGATEUR_FRANCAIS);
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $article = fn (string $slug, ?string $publieLe) => (new Article())->setSlug($slug)->setTitre("Titre $slug")->setResume('Résumé')
            ->setContenu("# Partie\n\nDu **gras**, <script>alert(1)</script> et [un lien](javascript:alert(1)).")
            ->setPublieLe($publieLe ? new \DateTimeImmutable($publieLe) : null);
        $articles = [$article('test-publie', '-1 day')->setTraductions(['en' => ['titre' => 'Published test']]), $article('test-brouillon', null), $article('test-programme', '+1 day')];
        array_map($entityManager->persist(...), $articles);
        $entityManager->flush();

        try {
            $liste = $client->request('GET', '/articles')->filter('.projet-carte-titre a')->extract(['href']);
            self::assertContains('/articles/test-publie', $liste);
            self::assertNotContains('/articles/test-brouillon', $liste);
            self::assertNotContains('/articles/test-programme', $liste);

            $page = $client->request('GET', '/articles/test-publie');
            self::assertSelectorCount(1, 'h1');
            self::assertSelectorTextContains('.article-texte h2', 'Partie');
            self::assertSelectorTextContains('.article-texte strong', 'gras');
            self::assertCount(0, $page->filter('.article-texte script'));
            self::assertStringContainsString('<script>', $page->filter('.article-texte')->text());
            self::assertCount(0, $page->filter('.article-texte a[href^="javascript"]'));

            // Traduction du titre ; contenu non traduit : le français s'affiche
            $client->request('GET', '/en/articles/test-publie');
            self::assertSelectorTextContains('h1', 'Published test');
            self::assertSelectorTextContains('.article-texte strong', 'gras');

            foreach (['test-brouillon', 'test-programme'] as $slug) {
                $client->request('GET', "/articles/$slug");
                self::assertResponseStatusCodeSame(404);
            }

            // Flux RSS dans la langue de l'adresse, brouillon exclu ; annoncé dans l'en-tête des pages
            $client->request('GET', '/en/articles/rss.xml');
            self::assertResponseHeaderSame('Content-Type', 'application/rss+xml; charset=UTF-8');
            $flux = simplexml_load_string($client->getResponse()->getContent());
            $items = array_map('strval', $flux->xpath('//item/link'));
            self::assertContains('http://localhost/en/articles/test-publie', $items);
            self::assertNotContains('http://localhost/en/articles/test-brouillon', $items);
            self::assertSame('Published test', (string) $flux->xpath('//item[link="http://localhost/en/articles/test-publie"]/title')[0]);
            self::assertSame('/en/articles/rss.xml', $client->request('GET', '/en/articles')->filter('link[type="application/rss+xml"]')->attr('href'));

            // Palette de commandes : article publié cherchable, pas le brouillon
            $client->request('GET', '/recherche.json');
            $urls = array_column(json_decode($client->getResponse()->getContent(), true), 'url');
            self::assertContains('/articles/test-publie', $urls);
            self::assertNotContains('/articles/test-brouillon', $urls);

            $client->request('GET', '/sitemap.xml');
            self::assertStringContainsString('http://localhost/es/articles/test-publie', $client->getResponse()->getContent());
            self::assertStringNotContainsString('test-brouillon', $client->getResponse()->getContent());
        } finally {
            foreach ($articles as $a) {
                $entityManager->remove($entityManager->find(Article::class, $a->getId()));
            }
            $entityManager->flush();
        }
    }

    /** /stats : totaux de visite_jour sur 30 jours, pages les plus vues ; jamais les provenances */
    public function testLesStatistiquesPubliquesMontrentLesTotaux(): void
    {
        $client = static::createClient(server: self::NAVIGATEUR_FRANCAIS);
        $connection = self::getContainer()->get(Connection::class);
        $connection->executeStatement("DELETE FROM visite_jour");
        $connection->executeStatement("INSERT INTO visite_jour (jour, chemin, langue, source, nombre) VALUES
            (CURRENT_DATE, '/projects', 'fr', 'entreprise-secrete.example', 7),
            (CURRENT_DATE - 3, '/cerveau', 'en', 'direct', 3),
            (CURRENT_DATE - 40, '/ancienne', 'fr', 'direct', 100)");

        try {
            $page = $client->request('GET', '/stats');
            self::assertResponseIsSuccessful();
            self::assertSelectorTextContains('.stats-chiffres', '10 pages vues ces 30 derniers jours');
            self::assertSelectorTextContains('.stats-chiffres', '110 pages vues en tout');
            self::assertCount(30, $page->filter('.stats-jours li'));
            self::assertSame(['/projects', '/cerveau'], $page->filter('.stats-listes a')->each(fn ($lien) => $lien->text()));
            self::assertSame('/en/cerveau', $client->request('GET', '/en/stats')->filter('.stats-listes a')->eq(1)->attr('href'));
            self::assertStringNotContainsString('entreprise-secrete', $client->getResponse()->getContent());
        } finally {
            $connection->executeStatement("DELETE FROM visite_jour");
        }
    }

    /** Page d'un article : temps de lecture, sommaire (3 titres et plus) vers les ids des titres, article précédent et suivant */
    public function testArticleSommaireEtVoisins(): void
    {
        $client = static::createClient(server: self::NAVIGATEUR_FRANCAIS);
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        // Il y a 20 ans : plus anciens que tout vrai article, donc voisins l'un de l'autre
        $ancien = (new Article())->setSlug('test-ancien')->setTitre('Ancien')->setResume('Résumé')->setPublieLe(new \DateTimeImmutable('-20 years'))
            ->setContenu("## Bug n°1 : la page\n\nTexte.\n\n## Deuxième partie\n\nTexte.\n\n# Troisième `partie`\n\nTexte.");
        $recent = (new Article())->setSlug('test-recent')->setTitre('Récent')->setResume('Résumé')->setPublieLe(new \DateTimeImmutable('-20 years +1 day'))
            ->setContenu("## Une seule partie\n\nTexte.");
        array_map($entityManager->persist(...), [$ancien, $recent]);
        $entityManager->flush();

        try {
            $page = $client->request('GET', '/articles/test-ancien');
            self::assertSelectorTextContains('.projet-groupe', '1 min de lecture');
            $liens = $page->filter('.article-sommaire a');
            self::assertSame(['Bug n°1 : la page', 'Deuxième partie', 'Troisième partie'], $liens->each(fn ($lien) => $lien->text()));
            foreach ($liens->extract(['href']) as $href) {
                self::assertCount(1, $page->filter('.article-texte h2'.$href), $href);
            }
            self::assertSame('/articles/test-recent', $page->filter('.article-voisins a[rel="next"]')->attr('href'));
            self::assertCount(0, $page->filter('.article-voisins a[rel="prev"]'));

            $page = $client->request('GET', '/articles/test-recent');
            self::assertCount(0, $page->filter('.article-sommaire'));
            self::assertSame('/articles/test-ancien', $page->filter('.article-voisins a[rel="prev"]')->attr('href'));
        } finally {
            foreach ([$ancien, $recent] as $a) {
                $entityManager->remove($entityManager->find(Article::class, $a->getId()));
            }
            $entityManager->flush();
        }
    }

    /** Palette de commandes (Ctrl+K) : bouton et fenêtre sur chaque page ; pages, compétences et projets cherchables, dans la langue de l'adresse */
    public function testLaPaletteDeCommandesCherchePartout(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/en/projects');
        self::assertSelectorExists('#paletteOuvrir[aria-keyshortcuts]');
        self::assertSame('/en/recherche.json', $crawler->filter('dialog#palette')->attr('data-url'));
        self::assertSame('Command', json_decode($crawler->filter('dialog#palette')->attr('data-textes'), true)['commande']);

        $client->request('GET', '/en/recherche.json');
        self::assertResponseHeaderSame('Content-Type', 'application/json');
        $entrees = json_decode($client->getResponse()->getContent(), true);
        self::assertContains(['type' => 'page', 'titre' => 'Skills', 'url' => '/en/competences'], $entrees);
        $parUrl = array_column($entrees, null, 'url');
        self::assertSame('projet', $parUrl['/en/projects/pendu']['type']);
        self::assertNotEmpty(array_filter($entrees, fn (array $e) => 'competence' === $e['type'] && str_starts_with($e['url'], '/en/cerveau#')));
    }

    /** Visite guidée : bouton sur l'accueil, cinq étapes vers des pages qui existent, textes traduits */
    public function testLaVisiteGuideeMeneADesPagesQuiExistent(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/en/');
        self::assertSelectorExists('button[data-visite-demarrer]');
        $etapes = json_decode($crawler->filter('#visite')->attr('data-etapes'), true);
        self::assertCount(5, $etapes);
        self::assertStringStartsWith('Welcome!', $etapes[0]['texte']);

        foreach ($etapes as $etape) {
            self::assertStringStartsWith('/en/', $etape['url']);
            $client->request('GET', $etape['url']);
            self::assertResponseIsSuccessful($etape['url']);
        }
    }

    /** Statistiques des easter eggs : +1 par découverte envoyée depuis le site, affiché dans le carnet ; le reste est refusé */
    public function testLesDecouvertesSontComptees(): void
    {
        $client = static::createClient(server: self::NAVIGATEUR_FRANCAIS);
        $connection = self::getContainer()->get(Connection::class);
        $nombre = fn () => (int) $connection->fetchOne("SELECT nombre FROM decouverte WHERE id = 'terminal'");
        $avant = $nombre();
        $envoyer = fn (string $id, array $server = []) => $client->request('POST', '/decouvertes', server: $server + ['CONTENT_TYPE' => 'application/json'], content: json_encode(['id' => $id]));

        $envoyer('terminal', ['HTTP_SEC_FETCH_SITE' => 'same-origin']);
        self::assertResponseStatusCodeSame(204);
        self::assertSame($avant + 1, $nombre());
        $client->request('GET', '/');
        self::assertSelectorTextContains('[data-decouverte="terminal"] .decouverte-stat', 1 === $avant + 1 ? 'Trouvée par un visiteur' : 'Trouvée par '.($avant + 1).' visiteurs');

        $envoyer('inconnue');
        self::assertResponseStatusCodeSame(400);
        $envoyer('terminal', ['HTTP_SEC_FETCH_SITE' => 'cross-site']);
        self::assertResponseStatusCodeSame(403);
        $client->request('GET', '/decouvertes');
        self::assertResponseStatusCodeSame(405);
        self::assertSame($avant + 1, $nombre());

        $connection->executeStatement("UPDATE decouverte SET nombre = :n WHERE id = 'terminal'", ['n' => $avant]);
    }

    /**
     * Globe des visites : une arrivée = un fuseau horaire vérifié, ramené à son nom actuel, placé par PHP, et le lieu
     * arrondi de l'IP (base d'essai de MaxMind) ; rien d'autre n'est gardé, pas l'IP. Robots, fuseaux inconnus ou sans lieu, autres sites : refusés ou ignorés.
     */
    public function testLesArriveesSontPlaceesSurLeGlobe(): void
    {
        $client = static::createClient(server: self::NAVIGATEUR_FRANCAIS);
        $connection = self::getContainer()->get(Connection::class);
        $depuis = (int) $connection->fetchOne('SELECT COALESCE(MAX(id), 0) FROM arrivee');
        $fuseaux = fn () => $connection->fetchFirstColumn('SELECT fuseau FROM arrivee WHERE id > :depuis ORDER BY id', ['depuis' => $depuis]);
        $envoyer = fn (string $fuseau, array $server = []) => $client->request('POST', '/arrivees', server: $server + ['CONTENT_TYPE' => 'application/json'], content: json_encode(['fuseau' => $fuseau]));

        $envoyer('Europe/Paris');
        self::assertResponseStatusCodeSame(204);
        $envoyer('Asia/Calcutta'); // ancien nom, encore donné par certains navigateurs
        self::assertResponseStatusCodeSame(204);
        self::assertSame(['Europe/Paris', 'Asia/Kolkata'], $fuseaux());
        $envoyer('Europe/London', ['REMOTE_ADDR' => '81.2.69.142']); // Londres dans la base d'essai
        self::assertResponseStatusCodeSame(204);
        self::assertSame([51.5, -0.1], array_map('floatval', $connection->fetchNumeric('SELECT ip_lat, ip_lon FROM arrivee ORDER BY id DESC LIMIT 1')));

        foreach (['Pas/Un_Fuseau', 'UTC', ''] as $fuseau) {
            $envoyer($fuseau);
            self::assertResponseStatusCodeSame(400, $fuseau);
        }
        $envoyer('Europe/Paris', ['HTTP_SEC_FETCH_SITE' => 'cross-site']);
        self::assertResponseStatusCodeSame(403);
        $envoyer('Europe/Paris', ['HTTP_USER_AGENT' => 'Googlebot/2.1']);
        self::assertResponseStatusCodeSame(204);
        self::assertCount(3, $fuseaux(), 'ni autre site, ni robot');

        // Le globe ne reçoit que l'ordre, le fuseau, sa position et le lieu de l'IP : pas l'heure
        $client->request('GET', '/arrivees?depuis='.$depuis);
        $arrivees = json_decode($client->getResponse()->getContent(), true);
        self::assertSame(['id', 'fuseau', 'lat', 'lon', 'ip'], array_keys($arrivees[0]));
        self::assertSame(['Europe/Paris', 48.87, 2.33, null], [$arrivees[0]['fuseau'], $arrivees[0]['lat'], $arrivees[0]['lon'], $arrivees[0]['ip']]);
        self::assertSame(['lat' => 51.5, 'lon' => -0.1], $arrivees[2]['ip']);

        // En clair sous le globe, sur /stats
        $client->request('GET', '/stats');
        self::assertSelectorExists('.stats-globe[data-globe-url-value="/arrivees"]');
        self::assertAnySelectorTextContains('.stats-listes li', 'Kolkata');

        $connection->executeStatement('DELETE FROM arrivee WHERE id > :depuis', ['depuis' => $depuis]);
    }

    /** Thème : posé dans <head> avant l'affichage (choix du visiteur, sinon le système), choix Système / Sombre / Clair dans les Réglages */
    public function testLeThemeSeChoisitDansLesReglages(): void
    {
        $crawler = static::createClient()->request('GET', '/en/projects');

        self::assertStringContainsString('prefers-color-scheme: light', $crawler->filter('head script')->first()->text());
        self::assertSame(['systeme', 'dark', 'light'], $crawler->filter('#reglages input[name="theme"]')->extract(['value']));
        self::assertSame(['hasard', 'trou-noir', 'teleportation', 'distorsion', 'lumiere'], $crawler->filter('#reglages input[name="effet"]')->extract(['value']));
        self::assertSelectorTextContains('#reglages', 'Theme');
        self::assertSelectorExists('body[data-page="app_projects"]'); // profil de l'ambiance sonore (ambiance_page.js)
    }

    /** Comparateur : chaque compétence arrive avec ses mots-clés (nom français, traduit, synonymes) et ses projets */
    public function testLeComparateurConnaitLesMotsDeChaqueCompetence(): void
    {
        $crawler = static::createClient()->request('GET', '/en/competences/comparer');

        $competences = array_column(json_decode($crawler->filter('[data-comparateur-competences-value]')->attr('data-comparateur-competences-value'), true), null, 'nom');
        self::assertContains('Symfony', $competences['PHP / Symfony']['mots']);
        self::assertContains('html', $competences['HTML5']['mots']);
        self::assertContains('Travail en équipe', $competences['Teamwork']['mots']); // offre en français, page en anglais
        self::assertNotEmpty($competences['PHP / Symfony']['projets']);
        $client = static::getClient();
        $client->request('GET', '/en/competences');
        self::assertSelectorExists('a[href="/en/competences/comparer"]');
    }

    /** Images de partage : un PNG 1200 × 630 par projet, dans la langue de l'adresse ; projet inconnu : 404 */
    public function testLesImagesDePartageSontGenerees(): void
    {
        $client = static::createClient();
        $client->request('GET', '/en/partage/projet/pendu.png');

        self::assertResponseHeaderSame('Content-Type', 'image/png');
        self::assertStringContainsString('max-age=86400', $client->getResponse()->headers->get('Cache-Control'));
        [$largeur, $hauteur] = getimagesizefromstring($client->getResponse()->getContent());
        self::assertSame([1200, 630], [$largeur, $hauteur]);

        $client->request('GET', '/partage/projet/inconnu.png');
        self::assertResponseStatusCodeSame(404);
    }

    /** Terminal : profil, projets (avec leur page), compétences et contact fournis à la page, traduits */
    public function testLeTerminalConnaitLePortfolio(): void
    {
        $crawler = static::createClient()->request('GET', '/en/terminal');

        $donnees = json_decode($crawler->filter('[data-terminal-donnees-value]')->attr('data-terminal-donnees-value'), true);
        self::assertContains('/en/projects/pendu', array_column($donnees['projets'], 'url'));
        self::assertArrayHasKey('Networks / Infra', $donnees['competences']);
        self::assertNotEmpty($donnees['resume']);
        self::assertStringContainsString('@', $donnees['contact']['email']);
        self::assertSelectorExists('label[for="terminal-commande"]');
    }

    /** Application installable : manifeste valide avec ses icônes, service worker, page hors ligne, autorisés par la CSP */
    public function testLeSiteEstInstallable(): void
    {
        $client = static::createClient(server: self::NAVIGATEUR_FRANCAIS);
        $crawler = $client->request('GET', '/');
        self::assertSame('/manifest.webmanifest', $crawler->filter('link[rel="manifest"]')->attr('href'));
        self::assertStringContainsString("worker-src 'self'", $client->getResponse()->headers->get('Content-Security-Policy'));

        $public = self::getContainer()->getParameter('kernel.project_dir').'/public';
        $manifeste = json_decode(file_get_contents($public.'/manifest.webmanifest'), true, flags: \JSON_THROW_ON_ERROR);
        self::assertSame('/', $manifeste['start_url']);
        foreach ($manifeste['icons'] as $icone) {
            [$largeur, $hauteur] = getimagesize($public.$icone['src']);
            self::assertSame($icone['sizes'], "{$largeur}x{$hauteur}");
        }
        self::assertStringContainsString("'/hors-ligne.html'", file_get_contents($public.'/sw.js'));
        self::assertFileExists($public.'/hors-ligne.html');
    }

    public function testTelechargementCvPdf(): void
    {
        $client = static::createClient();
        $client->request('GET', '/CV/download?theme=light');

        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('Content-Type', 'application/pdf');
    }

    /** Adresse inconnue : 404 aux couleurs du site, dans la langue de l'adresse */
    public function testLaPage404EstTraduite(): void
    {
        $client = static::createClient(['debug' => false]);

        $client->request('GET', '/nexiste-pas');
        self::assertResponseStatusCodeSame(404);
        self::assertSelectorTextContains('h1', 'Page introuvable');
        self::assertSelectorExists('html[lang="fr"] .erreur-liens a[href="/cerveau"]');
        self::assertSelectorExists('[data-controller="asteroides"] button[data-action="asteroides#jouer"]'); // mini-jeu

        $client->request('GET', '/en/does-not-exist');
        self::assertResponseStatusCodeSame(404);
        self::assertSelectorTextContains('h1', 'Page not found');
        self::assertSelectorExists('html[lang="en"] .erreur-liens a[href="/en/cerveau"]');

        $client->request('GET', '/it/non-esiste');
        self::assertResponseStatusCodeSame(404);
        self::assertSelectorTextContains('h1', 'Pagina non trovata');
        self::assertSelectorExists('html[lang="it"] .erreur-liens a[href="/it/cerveau"]');
    }

    /** La lettre de motivation suit la langue de la page (visionneuse et téléchargement) */
    public function testLaLettreDeMotivationSuitLaLangue(): void
    {
        $client = static::createClient();

        $fr = $client->request('GET', '/univers');
        self::assertStringContainsString('lettre de motivation', $fr->filter('a[download^="Lettre_Motivation"]')->attr('href'));

        $en = $client->request('GET', '/en/univers');
        self::assertStringContainsString('cover-letter', $en->filter('a[download="Cover_Letter_Nicolas_Cataluna.pdf"]')->attr('href'));
        self::assertStringContainsString('cover-letter', $en->filter('button[data-pdf-title-param="My cover letter"]')->attr('data-pdf-url-param'));

        // Chaque langue a sa lettre, et le PDF existe bien
        foreach (['es' => 'carta-de-presentacion', 'de' => 'anschreiben', 'it' => 'lettera-di-presentazione', 'pt' => 'carta-de-apresentacao'] as $langue => $fichier) {
            $href = $client->request('GET', "/$langue/univers")->filter('a[download$=".pdf"]')->attr('href');
            self::assertStringContainsString($fichier, $href, $langue);
            $client->request('GET', $href);
            self::assertResponseIsSuccessful($href);
        }
    }

    /** Première visite sur / : langue du navigateur ; ensuite le cookie « langue » fige le choix */
    public function testLaPremiereVisiteSuitLaLangueDuNavigateur(): void
    {
        $client = static::createClient();

        $client->request('GET', '/', server: ['HTTP_ACCEPT_LANGUAGE' => 'de-DE,de;q=0.9,en;q=0.8']);
        self::assertResponseRedirects('http://localhost/de/');
        self::assertResponseNotHasCookie('langue');

        $client->followRedirect();
        self::assertResponseHasCookie('langue');
        self::assertSame('de', $client->getCookieJar()->get('langue')->getValue());

        // Clic sur « Français » : le cookie existe, plus de redirection
        $client->request('GET', '/', server: ['HTTP_ACCEPT_LANGUAGE' => 'de-DE']);
        self::assertResponseIsSuccessful();
        self::assertSelectorExists('html[lang="fr"]');
    }

    public function testSansLangueConnueOnResteEnFrancais(): void
    {
        $client = static::createClient();

        $client->request('GET', '/', server: ['HTTP_ACCEPT_LANGUAGE' => '']); // robot : pas d'Accept-Language
        self::assertResponseIsSuccessful();

        $client->getCookieJar()->clear();
        $client->request('GET', '/', server: ['HTTP_ACCEPT_LANGUAGE' => 'ja-JP']);
        self::assertResponseIsSuccessful();
        self::assertSelectorExists('html[lang="fr"]');
    }
}
