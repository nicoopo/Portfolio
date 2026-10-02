<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * CV en base (profil, expériences, compétences du CV, langues, centres d'intérêt) avec son contenu.
 */
final class Version20261002123550 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'CV en base (profil, expériences, compétences du CV, langues, centres d’intérêt)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE centre_interet (id SERIAL NOT NULL, texte VARCHAR(255) NOT NULL, position INT NOT NULL, texte_en VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE cv_competence (id SERIAL NOT NULL, titre VARCHAR(100) NOT NULL, elements TEXT NOT NULL, position INT NOT NULL, titre_en VARCHAR(100) DEFAULT NULL, elements_en TEXT DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE cv_profil (id SERIAL NOT NULL, titre VARCHAR(100) NOT NULL, qualites VARCHAR(150) NOT NULL, resume TEXT NOT NULL, accroche TEXT DEFAULT NULL, telephone VARCHAR(30) NOT NULL, email VARCHAR(180) NOT NULL, titre_en VARCHAR(100) DEFAULT NULL, qualites_en VARCHAR(150) DEFAULT NULL, resume_en TEXT DEFAULT NULL, accroche_en TEXT DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE experience (id SERIAL NOT NULL, poste VARCHAR(100) NOT NULL, entreprise VARCHAR(100) NOT NULL, lieu VARCHAR(100) NOT NULL, periode VARCHAR(50) NOT NULL, contrat VARCHAR(50) NOT NULL, missions TEXT NOT NULL, position INT NOT NULL, poste_en VARCHAR(100) DEFAULT NULL, periode_en VARCHAR(50) DEFAULT NULL, contrat_en VARCHAR(50) DEFAULT NULL, missions_en TEXT DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE langue (id SERIAL NOT NULL, nom VARCHAR(50) NOT NULL, niveau VARCHAR(50) NOT NULL, position INT NOT NULL, nom_en VARCHAR(50) DEFAULT NULL, niveau_en VARCHAR(50) DEFAULT NULL, PRIMARY KEY(id))');

        // Contenu repris des templates du CV (français + anglais)
        $this->addSql(
            'INSERT INTO cv_profil (titre, qualites, resume, accroche, telephone, email, titre_en, qualites_en, resume_en, accroche_en)
             VALUES (:titre, :qualites, :resume, :accroche, :telephone, :email, :titre_en, :qualites_en, :resume_en, :accroche_en)',
            [
                'titre' => 'Développeur Fullstack',
                'qualites' => 'Curieux, Rigoureux, Autonome',
                'resume' => 'Développeur full-stack junior spécialisé Symfony, motivé par la conception de solutions web performantes et les environnements connectés (domotique). Curieux, rigoureux et passionné par les nouvelles technologies, je cherche à contribuer à des projets concrets en lien avec l\'innovation digitale.',
                'accroche' => "🎓 En recherche d'alternance : Bachelor IA, Développement Fullstack DevOps chez IPSSI\n📅 Rythme : 3 semaines en entreprise / 1 semaine en formation (12 mois)",
                'telephone' => '+33 7 67 80 74 69',
                'email' => 'nicolas.cataluna@proton.me',
                'titre_en' => 'Full-stack Developer',
                'qualites_en' => 'Curious, Thorough, Autonomous',
                'resume_en' => 'Junior full-stack developer specialising in Symfony, driven by building efficient web solutions and connected environments (home automation). Curious, thorough and passionate about new technologies, I am looking to contribute to real-world projects in digital innovation.',
                'accroche_en' => "🎓 Looking for a work-study position: Bachelor in AI, Full-stack Development & DevOps at IPSSI\n📅 Schedule: 3 weeks in the company / 1 week at school (12 months)",
            ],
        );

        foreach ([
            [
                'Développeur', 'TESSI ENCAISSEMENTS', 'NANTERRE', '2023 - 2024', "Contrat d'apprentissage",
                "Migration de sites web d'un noyau PHP vers Symfony (domaine de l'encaissement)\nMise en place de composants dans Symfony : export de données, création de graphiques dynamiques, tableau de données dynamique\nProjet évolution d'un nouveau noyau web - collaboration au développement",
                'Developer', '2023 - 2024', 'Apprenticeship',
                "Migrated websites from a legacy PHP core to Symfony (payment collection)\nBuilt Symfony components: data export, dynamic charts, dynamic data tables\nContributed to the development of a new web core",
            ],
            [
                'Stagiaire Technicien Informatique', 'TESSI ENCAISSEMENTS', 'NANTERRE', 'Mai - Juin 2022', 'Stage',
                "Création d'un support Excel répertoriant l'ensemble des serveurs (vérification obsolescence et état matériel)\nMise en place de flux de transfert et traitement de données\nParamétrage de l'application domotique DOMOTICZ\nImplémentation de protocoles Wifi et Bluetooth (IPV6, domotique)",
                'IT Technician Intern', 'May - June 2022', 'Internship',
                "Built an Excel inventory of all servers (obsolescence and hardware status checks)\nSet up data transfer and processing flows\nConfigured the DOMOTICZ home automation application\nImplemented Wi-Fi and Bluetooth protocols (IPv6, home automation)",
            ],
        ] as $position => [$poste, $entreprise, $lieu, $periode, $contrat, $missions, $posteEn, $periodeEn, $contratEn, $missionsEn]) {
            $this->addSql(
                'INSERT INTO experience (poste, entreprise, lieu, periode, contrat, missions, position, poste_en, periode_en, contrat_en, missions_en)
                 VALUES (:poste, :entreprise, :lieu, :periode, :contrat, :missions, :position, :poste_en, :periode_en, :contrat_en, :missions_en)',
                [
                    'poste' => $poste, 'entreprise' => $entreprise, 'lieu' => $lieu, 'periode' => $periode, 'contrat' => $contrat,
                    'missions' => $missions, 'position' => $position + 1,
                    'poste_en' => $posteEn, 'periode_en' => $periodeEn, 'contrat_en' => $contratEn, 'missions_en' => $missionsEn,
                ],
            );
        }

        foreach ([
            ['Développement Fullstack & Mobile', 'PHP (OOP), Symfony, MVC, HTML/CSS/JS, JavaScript, Java', 'Full-stack & Mobile Development', null],
            ['BDD & Hébergement', 'MySQL, MariaDB, PostgreSQL, SQL Server, VMWare', 'Databases & Hosting', null],
            ['Sécurité, Réseaux & Télécom', 'Cisco, 802.1x, Switch/Router, Protocoles domotiques, DOMOTICZ', 'Security, Networks & Telecom', 'Cisco, 802.1x, Switch/Router, Home automation protocols, DOMOTICZ'],
            ['Outils & DevOps', 'Git / GitHub, GitLab CI/CD, Windows/Linux, Ubuntu, Debian, Bash/PowerShell', 'Tools & DevOps', null],
            ['Informatique Générale', 'Pack Office, SST/PRAP', 'General IT', 'Microsoft Office, Workplace first aid (SST/PRAP)'],
        ] as $position => [$titre, $elements, $titreEn, $elementsEn]) {
            $this->addSql(
                'INSERT INTO cv_competence (titre, elements, position, titre_en, elements_en) VALUES (:titre, :elements, :position, :titre_en, :elements_en)',
                ['titre' => $titre, 'elements' => $elements, 'position' => $position + 1, 'titre_en' => $titreEn, 'elements_en' => $elementsEn],
            );
        }

        foreach ([
            ['Français', 'Langue maternelle', 'French', 'Native'],
            ['Portugais', 'Courant', 'Portuguese', 'Fluent'],
            ['Anglais', 'B1', 'English', 'B1 (intermediate)'],
            ['Espagnol', 'A2', 'Spanish', 'A2 (elementary)'],
        ] as $position => [$nom, $niveau, $nomEn, $niveauEn]) {
            $this->addSql(
                'INSERT INTO langue (nom, niveau, position, nom_en, niveau_en) VALUES (:nom, :niveau, :position, :nom_en, :niveau_en)',
                ['nom' => $nom, 'niveau' => $niveau, 'position' => $position + 1, 'nom_en' => $nomEn, 'niveau_en' => $niveauEn],
            );
        }

        foreach ([
            ['🎵 Musique Latino, R&B, Création musicale assistée par ordinateur (MAO)', '🎵 Latin music, R&B, computer-assisted music production'],
            ['✈️ Voyages : Portugal, Espagne, France et découverte de cultures', '✈️ Travel: Portugal, Spain, France and discovering other cultures'],
            ['💪 Sport : Musculation, Basketball', '💪 Sport: weight training, basketball'],
            ['🔭 Physique, Astronomie', '🔭 Physics, astronomy'],
        ] as $position => [$texte, $texteEn]) {
            $this->addSql(
                'INSERT INTO centre_interet (texte, position, texte_en) VALUES (:texte, :position, :texte_en)',
                ['texte' => $texte, 'position' => $position + 1, 'texte_en' => $texteEn],
            );
        }

        // Les formations du CV viennent désormais du parcours : la mention du Bac (présente sur le CV) y est reportée
        $this->addSql("UPDATE etape_parcours SET resultat = 'Obtention du BAC, mention Assez Bien', resultat_en = 'Baccalaureate obtained with honours (Assez Bien)' WHERE nom = 'Bac Pro SN'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE centre_interet');
        $this->addSql('DROP TABLE cv_competence');
        $this->addSql('DROP TABLE cv_profil');
        $this->addSql('DROP TABLE experience');
        $this->addSql('DROP TABLE langue');
        $this->addSql("UPDATE etape_parcours SET resultat = 'Obtention du BAC', resultat_en = 'Baccalaureate obtained' WHERE nom = 'Bac Pro SN'");
    }
}
