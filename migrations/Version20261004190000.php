<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Contenu (issue #85) : texte détaillé de chaque projet. Pendu, Poupée russe, Plateforme de QCM, Todolist et CRUD :
 * rédigés d'après le code des dépôts ; Encaissements, Cisco, Debian : premières versions courtes, à compléter depuis l'admin.
 */
final class Version20261004190000 extends AbstractMigration
{
    private const PROJETS = [
        'todolist-javafx' => [
            'description' => 'Gestion de listes de tâches en Java, en version terminal puis avec une interface JavaFX, sur une base MySQL.',
            'description_en' => 'Task list manager in Java, first in the terminal and then with a JavaFX interface, backed by MySQL.',
            'details' => <<<'TXT'
                Un projet que j’ai repris plusieurs fois, de plusieurs façons : une gestion de listes de tâches en Java, d’abord en ligne de commande, puis avec une interface graphique JavaFX.

                Les données sont dans une base MySQL : des utilisateurs qui créent un compte et se connectent, des listes (qui peuvent contenir des sous-listes) partagées entre plusieurs utilisateurs, et des tâches avec un type, une description et un état, réalisée ou non. Le code est découpé en couches : le modèle, l’accès à la base et l’affichage.

                Le refaire plusieurs fois m’a permis de comparer les approches : la même logique métier, présentée dans un terminal puis dans une interface graphique.
                TXT,
            'details_en' => <<<'TXT'
                A project I rebuilt several times, in several ways: a task list manager in Java, first as a command-line program, then with a JavaFX graphical interface.

                The data lives in a MySQL database: users who sign up and log in, lists (which can contain sub-lists) shared between several users, and tasks with a type, a description and a done / not done state. The code is split into layers: the model, database access and display.

                Building it several times let me compare approaches: the same business logic, presented in a terminal and then in a graphical interface.
                TXT,
        ],
        'crud-java' => [
            'description' => 'Application JavaFX de gestion des utilisateurs : inscription, connexion, mot de passe oublié et CRUD sur une base MySQL.',
            'description_en' => 'JavaFX user management app: sign-up, login, password reset and CRUD on a MySQL database.',
            'details' => <<<'TXT'
                Une application de bureau en JavaFX qui gère des comptes utilisateurs, reliée à une base MySQL avec JDBC.

                On peut s’inscrire, se connecter et réinitialiser un mot de passe oublié grâce à un e-mail envoyé par l’application. Une fois connecté, on retrouve la liste des utilisateurs, et on peut en créer, en modifier ou en supprimer : les quatre opérations de base d’un CRUD (Create, Read, Update, Delete). Les écrans sont décrits en FXML, et l’accès à la base passe par une classe dédiée qui utilise des requêtes préparées.

                J’en retiens la structure d’une application avec interface : séparer les écrans, la logique et l’accès aux données.
                TXT,
            'details_en' => <<<'TXT'
                A JavaFX desktop application that manages user accounts, connected to a MySQL database through JDBC.

                You can sign up, log in and reset a forgotten password through an e-mail sent by the application. Once logged in, you see the list of users and can create, edit or delete them: the four basic operations of a CRUD (Create, Read, Update, Delete). Screens are described in FXML, and database access goes through a dedicated class using prepared statements.

                What I take away: how an application with a user interface is structured, keeping screens, logic and data access apart.
                TXT,
        ],
        'encaissements' => [
            'details' => <<<'TXT'
                Un projet réalisé dans le cadre de mon ancien poste : une application web interne, développée avec Symfony et Symfony UX sur une base MySQL, pour consulter et suivre les encaissements, et gérer à plusieurs des portefeuilles clients et d’investissements.

                Le code appartient à l’entreprise : il n’est pas publié.
                TXT,
            'details_en' => <<<'TXT'
                A project built as part of my previous job: an internal web application, developed with Symfony and Symfony UX on a MySQL database, to view and track incoming payments and to manage client and investment portfolios collaboratively.

                The code belongs to the company, so it is not published.
                TXT,
        ],
        'topologie-cisco' => [
            'details' => <<<'TXT'
                Un projet réseau réalisé dans le cadre de mes cours : concevoir et configurer un réseau complet dans Cisco Packet Tracer, le simulateur de Cisco.

                Le réseau est découpé en VLAN, et les routeurs échangent leurs routes automatiquement grâce au protocole de routage dynamique OSPF. Une maquette comme celle-ci permet de valider une architecture et ses configurations avant de les appliquer sur du vrai matériel.
                TXT,
            'details_en' => <<<'TXT'
                A networking project from my studies: designing and configuring a complete network in Cisco Packet Tracer, Cisco's network simulator.

                The network is split into VLANs, and the routers exchange their routes automatically with the OSPF dynamic routing protocol. A model like this one makes it possible to validate an architecture and its configuration before applying them to real hardware.
                TXT,
        ],
        'serveur-debian' => [
            'details' => <<<'TXT'
                Un projet système réalisé dans le cadre de mes cours : installer et configurer un serveur web sous Debian, de l’installation à la mise en service.

                Apache2 sert des pages PHP, le serveur s’administre à distance en SSH, et le déploiement inclut sa sécurisation.
                TXT,
            'details_en' => <<<'TXT'
                A systems project from my studies: installing and configuring a web server on Debian, from installation to going live.

                Apache2 serves PHP pages, the server is administered remotely over SSH, and the deployment includes securing it.
                TXT,
        ],
        'pendu' => [
            'depot' => 'https://github.com/nicoopo/pendu',
            'description' => 'Le jeu du pendu en ligne de commande : un joueur choisit le mot, l’autre le devine lettre par lettre.',
            'description_en' => 'Hangman in the terminal: one player picks the word, the other guesses it letter by letter.',
            'details' => <<<'TXT'
                Un de mes premiers programmes en Java : le jeu du pendu, joué dans le terminal. Un premier joueur saisit le mot à deviner et le confirme, le second a dix vies pour le trouver.

                À chaque tour, on choisit de proposer une lettre ou le mot entier. Le programme affiche le mot avec les lettres déjà trouvées, l’historique des lettres et des mots essayés, les vies restantes et le dessin du pendu, qui se complète à chaque erreur. En niveau facile, la première et la dernière lettre sont dévoilées dès le départ. Une partie finie, on peut en relancer une autre.

                La difficulté a été de rendre le jeu robuste face aux saisies : redemander quand la réponse n’est ni « lettre » ni « mot », ignorer les majuscules, ne pas faire perdre de vie pour une lettre déjà proposée, et refuser un mot qui n’a pas la bonne longueur.

                J’en retiens les bases de l’algorithmique : boucles, conditions, tableaux et lecture des saisies de l’utilisateur.
                TXT,
            'details_en' => <<<'TXT'
                One of my first Java programs: hangman, played in the terminal. The first player types the word to guess and confirms it; the second has ten lives to find it.

                Each turn, you choose to guess a letter or the whole word. The program shows the word with the letters found so far, the history of letters and words tried, the remaining lives and the gallows drawing, which grows with each mistake. In easy mode, the first and last letters are revealed from the start. Once a game is over, you can start another one.

                The challenge was making the game robust to user input: asking again when the answer is neither "letter" nor "word", ignoring case, not costing a life for a letter already tried, and rejecting a word of the wrong length.

                What I take away: the basics of algorithms, with loops, conditions, arrays and reading user input.
                TXT,
        ],
        'poupee-russe' => [
            'depot' => 'https://github.com/nicoopo/pouper',
            'description' => 'Modélisation objet de poupées russes qui s’ouvrent, se ferment et s’emboîtent selon leur taille.',
            'description_en' => 'Object model of Russian dolls that open, close and nest inside each other by size.',
            'details' => <<<'TXT'
                Un exercice de programmation orientée objet en Java : représenter des poupées russes et les règles qui permettent de les ranger les unes dans les autres.

                Chaque poupée connaît sa taille, son état (ouverte ou fermée), la poupée qui la contient et celles qu’elle contient. Une poupée ne peut être rangée que dans une poupée plus grande qui n’est pas elle-même rangée ailleurs ; on ne peut ouvrir une poupée que si elle n’est pas enfermée dans une autre, et on ne peut en sortir une que si sa contenante est ouverte. Un programme principal enchaîne les manipulations et affiche le contenu des poupées.

                La difficulté était de traduire ces règles en méthodes (ouvrir, fermer, placerDans, sortirDe) sans jamais laisser d’état incohérent, comme une poupée rangée dans deux poupées à la fois.

                J’en retiens l’encapsulation : l’état de chaque poupée est privé, et seules les méthodes de la classe peuvent le modifier, en respectant les règles.
                TXT,
            'details_en' => <<<'TXT'
                An object-oriented programming exercise in Java: modelling Russian dolls and the rules for nesting them inside each other.

                Each doll knows its size, its state (open or closed), the doll containing it and the dolls it contains. A doll can only go into a bigger doll that is not itself inside another one; a doll can only be opened if it is not enclosed in another, and a doll can only be taken out if its container is open. A main program runs a sequence of moves and prints what each doll contains.

                The challenge was turning these rules into methods (open, close, placeInside, takeOutOf) without ever leaving an inconsistent state, such as a doll stored in two dolls at once.

                What I take away: encapsulation. Each doll's state is private, and only the class's own methods can change it, following the rules.
                TXT,
        ],
        'plateforme-qcm' => [
            'depot' => null, // dépôt privé (projet client)
            'tech' => 'Symfony, PostgreSQL, Bootstrap, Stripe',
            'description' => 'Plateforme web et mobile d’entraînement aux QCM pour les élèves de la formation Cabin Crew Attestation (CCA).',
            'description_en' => 'Web and mobile multiple-choice training platform for Cabin Crew Attestation (CCA) students.',
            'details' => <<<'TXT'
                Un projet commercial, réalisé pour un client à partir de son cahier des charges : une plateforme où les élèves de la formation Cabin Crew Attestation (CCA), qui prépare au métier de personnel navigant commercial, s’entraînent sur une banque d’environ 2 500 questions classées par module.

                Les élèves composent des QCM par module, aléatoires ou personnalisés (questions ratées, questions marquées), en mode entraînement ou en mode examen. L’examen blanc reprend les conditions réelles : 70 questions en 1 h 45, réussite à 85 %, puis un bilan par module avec la correction de chaque question. L’accès est payant (paiement en ligne avec Stripe), et une application Android reprend la plateforme sur mobile.

                Côté technique : Symfony 8 et PostgreSQL, Turbo pour une navigation fluide, une administration pour gérer les questions et les comptes, le tout dans Docker. Chaque modification passe par une intégration continue (tests automatisés) et une analyse de qualité avec SonarQube, puis est déployée automatiquement sur un serveur de préproduction.

                La difficulté a été de passer d’un cahier des charges à une application qu’on peut livrer et faire évoluer sereinement : découper le travail en petites étapes, et mettre en place les tests, la préproduction et le suivi de qualité qui permettent de modifier le code sans casser l’existant.

                J’en retiens ce qu’implique un projet pour un vrai client : comprendre son métier, prioriser avec lui, et soigner la fiabilité autant que les fonctionnalités.
                TXT,
            'details_en' => <<<'TXT'
                A commercial project, built for a client from their specifications: a platform where Cabin Crew Attestation (CCA) students, training to become flight attendants, practise on a bank of about 2,500 questions sorted by module.

                Students take quizzes by module, random or customised (missed questions, flagged questions), in practice mode or exam mode. The mock exam reproduces real conditions: 70 questions in 1 h 45, 85 % to pass, followed by a per-module summary with the correction of each question. Access is paid (online payment with Stripe), and an Android app brings the platform to mobile.

                On the technical side: Symfony 8 and PostgreSQL, Turbo for smooth navigation, a back office to manage questions and accounts, all running in Docker. Every change goes through continuous integration (automated tests) and a SonarQube quality analysis, then is deployed automatically to a pre-production server.

                The challenge was going from specifications to an application that can be delivered and evolved with confidence: splitting the work into small steps, and setting up the tests, pre-production and quality tracking that make it possible to change the code without breaking what already works.

                What I take away: what a project for a real client involves. Understanding their business, prioritising with them, and caring about reliability as much as features.
                TXT,
        ],
    ];

    public function getDescription(): string
    {
        return 'Contenu : texte détaillé des 8 projets (#85) ; Portfolio : Symfony 8';
    }

    public function up(Schema $schema): void
    {
        foreach (self::PROJETS as $slug => $champs) {
            $colonnes = implode(', ', array_map(static fn (string $c) => "$c = :$c", array_keys($champs)));
            $this->addSql("UPDATE projet SET $colonnes WHERE slug = :slug", $champs + ['slug' => $slug]);
        }

        // Le site est passé de Symfony 7 à 8 (#45)
        $this->addSql("UPDATE projet SET details = REPLACE(details, 'Symfony 7', 'Symfony 8'), details_en = REPLACE(details_en, 'Symfony 7', 'Symfony 8') WHERE slug = 'portfolio'");
    }

    public function down(Schema $schema): void
    {
        // Contenu d'avant (les textes détaillés étaient vides)
        $avant = [
            'todolist-javafx' => ['Application JavaFX permettant la gestion de tâches avec une base mysql.', 'JavaFX application for managing tasks, backed by a MySQL database.', 'JavaFX, MYSQL, MVC'],
            'crud-java' => ['CRUD Utilisateur', 'User management CRUD.', 'Java, POO, MYSQL'],
            'pendu' => ['Version terminal  .', 'Terminal version.', 'Java'],
            'poupee-russe' => ['Version terminal  .', 'Terminal version.', 'Java, POO'],
            'plateforme-qcm' => ['Application web de QCM pour les formations CCA, avec authentification et suivi des scores.', 'Quiz web application for CCA training courses, with authentication and score tracking.', 'Symfony, Bootstrap, MySQL'],
        ];
        foreach ($avant as $slug => [$description, $descriptionEn, $tech]) {
            $this->addSql('UPDATE projet SET details = NULL, details_en = NULL, depot = NULL, description = :d, description_en = :de, tech = :t WHERE slug = :slug',
                ['d' => $description, 'de' => $descriptionEn, 't' => $tech, 'slug' => $slug]);
        }
        $this->addSql("UPDATE projet SET details = NULL, details_en = NULL WHERE slug IN ('encaissements', 'topologie-cisco', 'serveur-debian')");
        $this->addSql("UPDATE projet SET details = REPLACE(details, 'Symfony 8', 'Symfony 7'), details_en = REPLACE(details_en, 'Symfony 8', 'Symfony 7') WHERE slug = 'portfolio'");
    }
}
