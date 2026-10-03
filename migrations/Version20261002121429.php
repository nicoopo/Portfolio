<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Version anglaise du contenu : colonnes « …_en » facultatives (vides → le français s'affiche)
 * et traduction du contenu existant (repéré par son nom ou son slug, pas par son id).
 */
final class Version20261002121429 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Traductions anglaises du contenu (colonnes …_en)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE categorie_competence ADD nom_en VARCHAR(50) DEFAULT NULL');
        $this->addSql('ALTER TABLE competence ADD nom_en VARCHAR(50) DEFAULT NULL');
        $this->addSql('ALTER TABLE etape_parcours ADD nom_en VARCHAR(50) DEFAULT NULL');
        $this->addSql('ALTER TABLE etape_parcours ADD intitule_en VARCHAR(150) DEFAULT NULL');
        $this->addSql('ALTER TABLE etape_parcours ADD specialite_en VARCHAR(150) DEFAULT NULL');
        $this->addSql('ALTER TABLE etape_parcours ADD resultat_en VARCHAR(100) DEFAULT NULL');
        $this->addSql('ALTER TABLE passion ADD nom_en VARCHAR(50) DEFAULT NULL');
        $this->addSql('ALTER TABLE passion ADD description_en TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE projet ADD titre_en VARCHAR(100) DEFAULT NULL');
        $this->addSql('ALTER TABLE projet ADD description_en TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE projet ADD categorie_en VARCHAR(50) DEFAULT NULL');

        // Seulement ce qui change en anglais (HTML5, Docker… restent tels quels)
        foreach ([
            'Réseaux / Infra' => 'Networks / Infra',
            'Outils' => 'Tools',
        ] as $nom => $en) {
            $this->addSql('UPDATE categorie_competence SET nom_en = :en WHERE nom = :nom', ['en' => $en, 'nom' => $nom]);
        }

        foreach ([
            'Virtualisation' => 'Virtualization',
            'Travail en équipe' => 'Teamwork',
            'Autonomie' => 'Autonomy',
            'Rigueur' => 'Rigour',
            'Curiosité' => 'Curiosity',
            'Bonne humeur 😄' => 'Good mood 😄',
        ] as $nom => $en) {
            $this->addSql('UPDATE competence SET nom_en = :en WHERE nom = :nom', ['en' => $en, 'nom' => $nom]);
        }

        foreach ([
            'todolist-javafx' => ['To-do list (with and without GUI)', 'JavaFX application for managing tasks, backed by a MySQL database.', null],
            'pendu' => ['Hangman game', 'Terminal version.', null],
            'poupee-russe' => ['Russian dolls', 'Terminal version.', null],
            'crud-java' => ['CRUD', 'User management CRUD.', null],
            'encaissements' => ['Collaborative web app for payment collection', 'Business software for managing client and investment portfolios.', null],
            'plateforme-qcm' => ['Multiple-choice quiz platform', 'Quiz web application for CCA training courses, with authentication and score tracking.', null],
            'portfolio' => ['Portfolio', 'This site: skills, projects and background, explored through an interactive 3D brain.', null],
            'topologie-cisco' => ['Virtual Cisco topology', 'A complete network built in Cisco Packet Tracer, with dynamic routing.', 'Networks / Infra'],
            'serveur-debian' => ['Debian web server', 'Full deployment of a secured Apache/PHP server on Debian.', 'Networks / Infra'],
        ] as $slug => [$titre, $description, $categorie]) {
            $this->addSql(
                'UPDATE projet SET titre_en = :titre, description_en = :description, categorie_en = :categorie WHERE slug = :slug',
                ['titre' => $titre, 'description' => $description, 'categorie' => $categorie, 'slug' => $slug],
            );
        }

        foreach ([
            'Espace' => ['Space', 'Astronomy, space exploration, whatever happens beyond our atmosphere.'],
            'Informatique' => ['Computer science', 'Understanding how machines think, and building with them.'],
            'Biotechnologie' => ['Biotechnology', 'When living things become technology: genetics, bioengineering.'],
            'Science' => [null, 'Methodical curiosity: observe, doubt, experiment.'],
            'Physique' => ['Physics', 'The laws that make the universe turn, from atoms to galaxies.'],
            'Médecine' => ['Medicine', 'The human body, the brain, and how to heal them.'],
        ] as $nom => [$en, $description]) {
            $this->addSql('UPDATE passion SET nom_en = :en, description_en = :description WHERE nom = :nom', ['en' => $en, 'description' => $description, 'nom' => $nom]);
        }

        // Noms de diplômes français gardés (BTS SIO…) : l'intitulé anglais les explique
        foreach ([
            'Bachelor IPSSI' => ['IPSSI Bachelor', 'Bachelor in AI, Full-stack Development & DevOps', null, 'In progress'],
            'BTS SIO' => [null, 'BTS SIO: IT Services for Organisations (2-year higher national diploma)', 'Option SLAM: Software Solutions and Business Applications', 'Diploma obtained'],
            'Bac Pro SN' => [null, 'Vocational Baccalaureate in Digital Systems', 'Option: Computer Networks and Communicating Systems (RISC)', 'Baccalaureate obtained'],
            'Brevet' => [null, 'Brevet des collèges (secondary school diploma)', null, 'Diploma obtained'],
        ] as $nom => [$en, $intitule, $specialite, $resultat]) {
            $this->addSql(
                'UPDATE etape_parcours SET nom_en = :en, intitule_en = :intitule, specialite_en = :specialite, resultat_en = :resultat WHERE nom = :nom',
                ['en' => $en, 'intitule' => $intitule, 'specialite' => $specialite, 'resultat' => $resultat, 'nom' => $nom],
            );
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE categorie_competence DROP nom_en');
        $this->addSql('ALTER TABLE competence DROP nom_en');
        $this->addSql('ALTER TABLE etape_parcours DROP nom_en');
        $this->addSql('ALTER TABLE etape_parcours DROP intitule_en');
        $this->addSql('ALTER TABLE etape_parcours DROP specialite_en');
        $this->addSql('ALTER TABLE etape_parcours DROP resultat_en');
        $this->addSql('ALTER TABLE passion DROP nom_en');
        $this->addSql('ALTER TABLE passion DROP description_en');
        $this->addSql('ALTER TABLE projet DROP titre_en');
        $this->addSql('ALTER TABLE projet DROP description_en');
        $this->addSql('ALTER TABLE projet DROP categorie_en');
    }
}
