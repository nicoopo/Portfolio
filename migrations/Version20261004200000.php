<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Trois projets de plus, rédigés d'après leurs dépôts : mini-PaaS (Go), Tableau de bord OBD (Python),
 * SecureScan (Symfony, en équipe). Sans image : à envoyer depuis l'admin.
 */
final class Version20261004200000 extends AbstractMigration
{
    private const PROJETS = [
        [
            'slug' => 'securescan',
            'titre' => 'SecureScan',
            'titre_en' => null,
            'categorie' => 'PHP / Symfony',
            'categorie_en' => null,
            'tech' => 'Symfony, Docker, Chart.js, Dompdf',
            'position' => 8,
            'depot' => 'https://github.com/nicoopo/SecureScan',
            'competences' => ['PHP / Symfony', 'Twig', 'Docker', 'Git / GitHub', 'Travail en équipe'],
            'description' => 'Projet d’équipe : une application qui analyse le code d’un projet selon le Top 10 OWASP et propose des correctifs.',
            'description_en' => 'Team project: an application that analyses a project’s code against the OWASP Top 10 and suggests fixes.',
            'details' => <<<'TXT'
                Un projet réalisé en équipe de cinq : SecureScan analyse la sécurité d’un projet web. On lui soumet un dépôt Git ou une archive ZIP, et il passe le code au crible des dix catégories du Top 10 OWASP, la référence des risques de sécurité des applications web.

                Chaque catégorie a son analyseur : l’un d’eux, par exemple, repère dans les fichiers composer et npm les dépendances restées dans des versions connues pour être vulnérables. Les problèmes trouvés sont classés par gravité, accompagnés de correctifs proposés (le code d’origine, le code corrigé et une explication), et résumés dans un tableau de bord avec graphiques et dans un rapport PDF.

                J’ai créé le dépôt et organisé le travail de l’équipe avec Git : une branche principale stable, une branche de développement, une branche par fonctionnalité, et des pull requests pour intégrer les contributions de chacun. J’en suis aussi le premier contributeur.

                J’en retiens le travail à plusieurs sur un même code, et une meilleure connaissance des failles web les plus courantes.
                TXT,
            'details_en' => <<<'TXT'
                A project built by a team of five: SecureScan analyses the security of a web project. You submit a Git repository or a ZIP archive, and it checks the code against the ten categories of the OWASP Top 10, the reference list of web application security risks.

                Each category has its own analyser: one of them, for example, looks through composer and npm files for dependencies stuck on versions known to be vulnerable. The issues found are ranked by severity, come with suggested fixes (the original code, the corrected code and an explanation), and are summarised in a dashboard with charts and in a PDF report.

                I created the repository and organised the team's work with Git: a stable main branch, a development branch, one branch per feature, and pull requests to bring in everyone's contributions. I am also the project's top contributor.

                What I take away: working with others on the same codebase, and a better knowledge of the most common web vulnerabilities.
                TXT,
        ],
        [
            'slug' => 'mini-paas',
            'titre' => 'mini-PaaS',
            'titre_en' => null,
            'categorie' => 'Outils perso',
            'categorie_en' => 'Personal tools',
            'tech' => 'Go, Docker, Git',
            'position' => 10,
            'depot' => 'https://github.com/nicoopo/mini-paas',
            'competences' => ['Docker', 'Git / GitHub', 'Bash / Terminal', 'Autonomie'],
            'description' => 'Un mini Heroku perso : un seul binaire Go qui déploie un dépôt Git dans un conteneur Docker, piloté depuis un tableau de bord web.',
            'description_en' => 'A personal mini Heroku: a single Go binary that deploys a Git repository into a Docker container, driven from a web dashboard.',
            'details' => <<<'TXT'
                Un outil que j’ai écrit pour déployer mes propres projets sans passer par un hébergeur : un petit « PaaS » à la manière de Heroku, qui tient en un seul binaire Go, sans aucune dépendance externe.

                Depuis le tableau de bord web, on déclare un projet (adresse du dépôt Git, branche, variables d’environnement, ports) et on clique sur Déployer. L’outil clone ou met à jour le dépôt, construit l’image à partir de son Dockerfile, lance le conteneur et affiche toute la sortie du déploiement. Les projets sont enregistrés dans un simple fichier JSON, et le tableau de bord est protégé par un mot de passe.

                Le choix a été de rester minimal : uniquement la bibliothèque standard de Go, qui pilote directement les commandes git et docker déjà installées sur la machine, plutôt qu’un SDK. Les limites assumées sont notées dans le code, comme le déploiement synchrone, avec la piste pour aller plus loin : une tâche en arrière-plan et un journal affiché en direct.

                J’en retiens la force de Go pour ce genre d’outil : un binaire unique, facile à installer, et une bibliothèque standard qui suffit pour un serveur web, des processus et du JSON.
                TXT,
            'details_en' => <<<'TXT'
                A tool I wrote to deploy my own projects without a hosting provider: a small Heroku-style "PaaS" that fits in a single Go binary, with no external dependencies.

                From the web dashboard, you declare a project (Git repository URL, branch, environment variables, ports) and click Deploy. The tool clones or updates the repository, builds the image from its Dockerfile, starts the container and shows the full deployment output. Projects are stored in a plain JSON file, and the dashboard is password-protected.

                The choice was to stay minimal: only Go's standard library, driving the git and docker commands already installed on the machine, rather than an SDK. The deliberate limits are noted in the code, such as synchronous deployment, along with the way forward: a background job and a live log.

                What I take away: how well Go suits this kind of tool. A single binary, easy to install, and a standard library that covers a web server, processes and JSON.
                TXT,
        ],
        [
            'slug' => 'tableau-de-bord-obd',
            'titre' => 'Tableau de bord OBD',
            'titre_en' => 'OBD dashboard',
            'categorie' => 'Outils perso',
            'categorie_en' => 'Personal tools',
            'tech' => 'Python, FastAPI, WebSocket, SQLite',
            'position' => 11,
            'depot' => null, // dépôt privé
            'competences' => ['Python', 'API REST', 'JavaScript / TypeScript', 'HTML5', 'Curiosité'],
            'description' => 'Lire en direct les données moteur et les codes défaut de ma voiture depuis un navigateur, grâce à un adaptateur OBD-II.',
            'description_en' => 'Reading my car’s live engine data and fault codes in a browser, through an OBD-II adapter.',
            'details' => <<<'TXT'
                Un outil né d’un besoin concret : savoir ce qui se passe dans ma voiture, une Audi A3, sans logiciel de garage. Un adaptateur ELM327 se branche sur la prise de diagnostic OBD-II et se relie au PC en USB.

                Une application Python (FastAPI) lit les données grâce à la bibliothèque python-OBD et les envoie en temps réel à un tableau de bord web par WebSocket : régime moteur, vitesse, température du liquide de refroidissement, tension de la batterie, niveau de carburant et position du papillon, avec une courbe du régime sur les dernières mesures. On peut aussi lire les codes défaut, avec leur description, et les effacer.

                Tout l’historique est enregistré dans une base SQLite et s’exporte en CSV. L’application tourne en local, sans compte ni serveur distant, et démarre même si la voiture n’est pas branchée : on relance la connexion une fois l’adaptateur prêt.

                La difficulté vient du matériel : la bibliothèque ne connaît que les codes défaut génériques, si bien que ceux propres au groupe Volkswagen remontent sans description. J’en retiens le plaisir de coder pour un usage bien réel, et la découverte de la communication avec du matériel.
                TXT,
            'details_en' => <<<'TXT'
                A tool born from a real need: knowing what is going on in my car, an Audi A3, without garage software. An ELM327 adapter plugs into the OBD-II diagnostic port and connects to the PC over USB.

                A Python application (FastAPI) reads the data with the python-OBD library and streams it in real time to a web dashboard over WebSocket: engine speed, vehicle speed, coolant temperature, battery voltage, fuel level and throttle position, with a chart of the engine speed over the latest readings. You can also read the fault codes, with their description, and clear them.

                The whole history is stored in a SQLite database and can be exported as CSV. The app runs locally, with no account or remote server, and starts even when the car is not connected: you reconnect once the adapter is ready.

                The challenge comes from the hardware: the library only knows the generic fault codes, so codes specific to the Volkswagen group come up without a description. What I take away: the fun of coding for a very real use, and a first taste of talking to hardware.
                TXT,
        ],
    ];

    public function getDescription(): string
    {
        return 'Contenu : projets SecureScan, mini-PaaS et Tableau de bord OBD';
    }

    public function up(Schema $schema): void
    {
        foreach (self::PROJETS as $projet) {
            $competences = $projet['competences'];
            unset($projet['competences']);
            $colonnes = array_keys($projet);
            $this->addSql(
                \sprintf('INSERT INTO projet (%s) VALUES (:%s)', implode(', ', $colonnes), implode(', :', $colonnes)),
                $projet,
            );
            foreach ($competences as $competence) {
                $this->addSql(
                    'INSERT INTO projet_competence (projet_id, competence_id) SELECT p.id, c.id FROM projet p, competence c WHERE p.slug = :slug AND c.nom = :nom',
                    ['slug' => $projet['slug'], 'nom' => $competence],
                );
            }
        }
    }

    public function down(Schema $schema): void
    {
        $slugs = "('securescan', 'mini-paas', 'tableau-de-bord-obd')";
        $this->addSql("DELETE FROM projet_competence WHERE projet_id IN (SELECT id FROM projet WHERE slug IN $slugs)");
        $this->addSql("DELETE FROM projet WHERE slug IN $slugs");
    }
}
