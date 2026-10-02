<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Données du portfolio en base : schéma + contenu repris de l'ancien src/Data
 * (compétences, projets, passions, parcours).
 */
final class Version20261002091900 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Compétences, projets, passions et parcours en base (avec leur contenu)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE categorie_competence (id SERIAL NOT NULL, nom VARCHAR(50) NOT NULL, zone VARCHAR(20) NOT NULL, couleur VARCHAR(7) NOT NULL, position INT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_A1DA2D36C6E55B5 ON categorie_competence (nom)');
        $this->addSql('CREATE TABLE competence (id SERIAL NOT NULL, categorie_id INT NOT NULL, nom VARCHAR(50) NOT NULL, position INT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_94D4687F6C6E55B5 ON competence (nom)');
        $this->addSql('CREATE INDEX IDX_94D4687FBCF5E72D ON competence (categorie_id)');
        $this->addSql('CREATE TABLE etape_parcours (id SERIAL NOT NULL, nom VARCHAR(50) NOT NULL, dates VARCHAR(20) NOT NULL, intitule VARCHAR(150) NOT NULL, specialite VARCHAR(150) DEFAULT NULL, ecole VARCHAR(100) NOT NULL, lieu VARCHAR(100) NOT NULL, resultat VARCHAR(100) NOT NULL, position INT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_88EBBC1C6C6E55B5 ON etape_parcours (nom)');
        $this->addSql('CREATE TABLE passion (id SERIAL NOT NULL, nom VARCHAR(50) NOT NULL, couleur VARCHAR(7) NOT NULL, description TEXT NOT NULL, position INT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_7A3D8D5F6C6E55B5 ON passion (nom)');
        $this->addSql('CREATE TABLE projet (id SERIAL NOT NULL, slug VARCHAR(50) NOT NULL, titre VARCHAR(100) NOT NULL, description TEXT NOT NULL, tech VARCHAR(100) NOT NULL, image VARCHAR(100) NOT NULL, categorie VARCHAR(50) NOT NULL, position INT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_50159CA9989D9B62 ON projet (slug)');
        $this->addSql('CREATE TABLE projet_competence (projet_id INT NOT NULL, competence_id INT NOT NULL, PRIMARY KEY(projet_id, competence_id))');
        $this->addSql('CREATE INDEX IDX_15498055C18272 ON projet_competence (projet_id)');
        $this->addSql('CREATE INDEX IDX_1549805515761DAB ON projet_competence (competence_id)');
        $this->addSql('CREATE TABLE messenger_messages (id BIGSERIAL NOT NULL, body TEXT NOT NULL, headers TEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, available_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, delivered_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 ON messenger_messages (queue_name, available_at, delivered_at, id)');
        $this->addSql('COMMENT ON COLUMN messenger_messages.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN messenger_messages.available_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN messenger_messages.delivered_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE OR REPLACE FUNCTION notify_messenger_messages() RETURNS TRIGGER AS $$
            BEGIN
                PERFORM pg_notify(\'messenger_messages\', NEW.queue_name::text);
                RETURN NEW;
            END;
        $$ LANGUAGE plpgsql;');
        $this->addSql('DROP TRIGGER IF EXISTS notify_trigger ON messenger_messages;');
        $this->addSql('CREATE TRIGGER notify_trigger AFTER INSERT OR UPDATE ON messenger_messages FOR EACH ROW EXECUTE PROCEDURE notify_messenger_messages();');
        $this->addSql('ALTER TABLE competence ADD CONSTRAINT FK_94D4687FBCF5E72D FOREIGN KEY (categorie_id) REFERENCES categorie_competence (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE projet_competence ADD CONSTRAINT FK_15498055C18272 FOREIGN KEY (projet_id) REFERENCES projet (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE projet_competence ADD CONSTRAINT FK_1549805515761DAB FOREIGN KEY (competence_id) REFERENCES competence (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');

        // Contenu
        $this->addSql('INSERT INTO categorie_competence (id, nom, zone, couleur, position) VALUES (1, \'Front-End\', \'frontal\', \'#00d4ff\', 1)');
        $this->addSql('INSERT INTO competence (id, categorie_id, nom, position) VALUES (1, 1, \'HTML5\', 1)');
        $this->addSql('INSERT INTO competence (id, categorie_id, nom, position) VALUES (2, 1, \'CSS3 / SCSS\', 2)');
        $this->addSql('INSERT INTO competence (id, categorie_id, nom, position) VALUES (3, 1, \'JavaScript / TypeScript\', 3)');
        $this->addSql('INSERT INTO competence (id, categorie_id, nom, position) VALUES (4, 1, \'Vue.js\', 4)');
        $this->addSql('INSERT INTO competence (id, categorie_id, nom, position) VALUES (5, 1, \'React\', 5)');
        $this->addSql('INSERT INTO competence (id, categorie_id, nom, position) VALUES (6, 1, \'Twig\', 6)');
        $this->addSql('INSERT INTO competence (id, categorie_id, nom, position) VALUES (7, 1, \'Three.js\', 7)');
        $this->addSql('INSERT INTO categorie_competence (id, nom, zone, couleur, position) VALUES (2, \'Back-End\', \'parietal\', \'#7f5af0\', 2)');
        $this->addSql('INSERT INTO competence (id, categorie_id, nom, position) VALUES (8, 2, \'PHP / Symfony\', 8)');
        $this->addSql('INSERT INTO competence (id, categorie_id, nom, position) VALUES (9, 2, \'Java / Spring\', 9)');
        $this->addSql('INSERT INTO competence (id, categorie_id, nom, position) VALUES (10, 2, \'Python\', 10)');
        $this->addSql('INSERT INTO competence (id, categorie_id, nom, position) VALUES (11, 2, \'API REST\', 11)');
        $this->addSql('INSERT INTO competence (id, categorie_id, nom, position) VALUES (12, 2, \'MySQL / PostgreSQL\', 12)');
        $this->addSql('INSERT INTO categorie_competence (id, nom, zone, couleur, position) VALUES (3, \'Réseaux / Infra\', \'temporal\', \'#2cb67d\', 3)');
        $this->addSql('INSERT INTO competence (id, categorie_id, nom, position) VALUES (13, 3, \'Linux / Windows\', 13)');
        $this->addSql('INSERT INTO competence (id, categorie_id, nom, position) VALUES (14, 3, \'Cisco\', 14)');
        $this->addSql('INSERT INTO competence (id, categorie_id, nom, position) VALUES (15, 3, \'Packet tracer\', 15)');
        $this->addSql('INSERT INTO competence (id, categorie_id, nom, position) VALUES (16, 3, \'Bash / Terminal\', 16)');
        $this->addSql('INSERT INTO competence (id, categorie_id, nom, position) VALUES (17, 3, \'Virtualisation\', 17)');
        $this->addSql('INSERT INTO categorie_competence (id, nom, zone, couleur, position) VALUES (4, \'Outils\', \'occipital\', \'#ff8906\', 4)');
        $this->addSql('INSERT INTO competence (id, categorie_id, nom, position) VALUES (18, 4, \'Git / GitHub\', 18)');
        $this->addSql('INSERT INTO competence (id, categorie_id, nom, position) VALUES (19, 4, \'VS Code / JetBrains / Cursor\', 19)');
        $this->addSql('INSERT INTO competence (id, categorie_id, nom, position) VALUES (20, 4, \'Docker\', 20)');
        $this->addSql('INSERT INTO competence (id, categorie_id, nom, position) VALUES (21, 4, \'Figma\', 21)');
        $this->addSql('INSERT INTO competence (id, categorie_id, nom, position) VALUES (22, 4, \'VirtualBox\', 22)');
        $this->addSql('INSERT INTO categorie_competence (id, nom, zone, couleur, position) VALUES (5, \'Soft Skills\', \'limbique\', \'#f25f8c\', 5)');
        $this->addSql('INSERT INTO competence (id, categorie_id, nom, position) VALUES (23, 5, \'Travail en équipe\', 23)');
        $this->addSql('INSERT INTO competence (id, categorie_id, nom, position) VALUES (24, 5, \'Autonomie\', 24)');
        $this->addSql('INSERT INTO competence (id, categorie_id, nom, position) VALUES (25, 5, \'Rigueur\', 25)');
        $this->addSql('INSERT INTO competence (id, categorie_id, nom, position) VALUES (26, 5, \'Curiosité\', 26)');
        $this->addSql('INSERT INTO competence (id, categorie_id, nom, position) VALUES (27, 5, \'Bonne humeur 😄\', 27)');
        $this->addSql('INSERT INTO projet (id, slug, titre, description, tech, image, categorie, position) VALUES (1, \'todolist-javafx\', \'Todolist avec et sans interface\', \'Application JavaFX permettant la gestion de tâches avec une base mysql.\', \'JavaFX, MYSQL, MVC\', \'java/javafx_todo.gif\', \'Java & JavaFX\', 1)');
        $this->addSql('INSERT INTO projet_competence (projet_id, competence_id) VALUES (1, 9)');
        $this->addSql('INSERT INTO projet_competence (projet_id, competence_id) VALUES (1, 12)');
        $this->addSql('INSERT INTO projet (id, slug, titre, description, tech, image, categorie, position) VALUES (2, \'pendu\', \'Jeu du pendu\', \'Version terminal  .\', \'Java\', \'java/pendu.png\', \'Java & JavaFX\', 2)');
        $this->addSql('INSERT INTO projet_competence (projet_id, competence_id) VALUES (2, 9)');
        $this->addSql('INSERT INTO projet (id, slug, titre, description, tech, image, categorie, position) VALUES (3, \'poupee-russe\', \'Poupée russe\', \'Version terminal  .\', \'Java, POO\', \'java/poupee.png\', \'Java & JavaFX\', 3)');
        $this->addSql('INSERT INTO projet_competence (projet_id, competence_id) VALUES (3, 9)');
        $this->addSql('INSERT INTO projet (id, slug, titre, description, tech, image, categorie, position) VALUES (4, \'crud-java\', \'CRUD\', \'CRUD Utilisateur\', \'Java, POO, MYSQL\', \'java/crud.png\', \'Java & JavaFX\', 4)');
        $this->addSql('INSERT INTO projet_competence (projet_id, competence_id) VALUES (4, 9)');
        $this->addSql('INSERT INTO projet_competence (projet_id, competence_id) VALUES (4, 12)');
        $this->addSql('INSERT INTO projet (id, slug, titre, description, tech, image, categorie, position) VALUES (5, \'encaissements\', \'WEB consultation et collaboratif Encaissements\', \'Progiciel pour la gestion de portefeuilles clients et d’investissements.\', \'Symfony, UX, MYSQL\', \'symfony/crm_finance.png\', \'PHP / Symfony\', 5)');
        $this->addSql('INSERT INTO projet_competence (projet_id, competence_id) VALUES (5, 8)');
        $this->addSql('INSERT INTO projet_competence (projet_id, competence_id) VALUES (5, 6)');
        $this->addSql('INSERT INTO projet_competence (projet_id, competence_id) VALUES (5, 12)');
        $this->addSql('INSERT INTO projet (id, slug, titre, description, tech, image, categorie, position) VALUES (6, \'plateforme-qcm\', \'Plateforme de QCM\', \'Application web de QCM pour les formations CCA, avec authentification et suivi des scores.\', \'Symfony, Bootstrap, MySQL\', \'symfony/qcm_app.webp\', \'PHP / Symfony\', 6)');
        $this->addSql('INSERT INTO projet_competence (projet_id, competence_id) VALUES (6, 8)');
        $this->addSql('INSERT INTO projet_competence (projet_id, competence_id) VALUES (6, 6)');
        $this->addSql('INSERT INTO projet_competence (projet_id, competence_id) VALUES (6, 12)');
        $this->addSql('INSERT INTO projet_competence (projet_id, competence_id) VALUES (6, 20)');
        $this->addSql('INSERT INTO projet_competence (projet_id, competence_id) VALUES (6, 18)');
        $this->addSql('INSERT INTO projet (id, slug, titre, description, tech, image, categorie, position) VALUES (7, \'portfolio\', \'Portfolio\', \'Ce site : compétences, projets et parcours, à explorer dans un cerveau 3D interactif.\', \'Symfony, Twig, Stimulus, Three.js, Docker\', \'symfony/portfolio-cerveau.jpg\', \'PHP / Symfony\', 7)');
        $this->addSql('INSERT INTO projet_competence (projet_id, competence_id) VALUES (7, 8)');
        $this->addSql('INSERT INTO projet_competence (projet_id, competence_id) VALUES (7, 6)');
        $this->addSql('INSERT INTO projet_competence (projet_id, competence_id) VALUES (7, 1)');
        $this->addSql('INSERT INTO projet_competence (projet_id, competence_id) VALUES (7, 2)');
        $this->addSql('INSERT INTO projet_competence (projet_id, competence_id) VALUES (7, 3)');
        $this->addSql('INSERT INTO projet_competence (projet_id, competence_id) VALUES (7, 7)');
        $this->addSql('INSERT INTO projet_competence (projet_id, competence_id) VALUES (7, 20)');
        $this->addSql('INSERT INTO projet_competence (projet_id, competence_id) VALUES (7, 18)');
        $this->addSql('INSERT INTO projet (id, slug, titre, description, tech, image, categorie, position) VALUES (8, \'topologie-cisco\', \'Topologie Cisco virtuelle\', \'Mise en place d’un réseau complet sous Cisco Packet Tracer avec routage dynamique.\', \'Cisco, VLAN, OSPF\', \'reseau/cisco_network.png\', \'Réseau / Infra\', 8)');
        $this->addSql('INSERT INTO projet_competence (projet_id, competence_id) VALUES (8, 14)');
        $this->addSql('INSERT INTO projet_competence (projet_id, competence_id) VALUES (8, 15)');
        $this->addSql('INSERT INTO projet (id, slug, titre, description, tech, image, categorie, position) VALUES (9, \'serveur-debian\', \'Serveur Web Debian\', \'Déploiement complet d’un serveur Apache/PHP sécurisé sous Debian.\', \'Linux, Apache2, SSH\', \'reseau/debian_server.png\', \'Réseau / Infra\', 9)');
        $this->addSql('INSERT INTO projet_competence (projet_id, competence_id) VALUES (9, 13)');
        $this->addSql('INSERT INTO projet_competence (projet_id, competence_id) VALUES (9, 16)');
        $this->addSql('INSERT INTO passion (id, nom, couleur, description, position) VALUES (1, \'Espace\', \'#5b8cff\', \'Astronomie, exploration spatiale, ce qui se passe au-delà de notre atmosphère.\', 1)');
        $this->addSql('INSERT INTO passion (id, nom, couleur, description, position) VALUES (2, \'Informatique\', \'#00d4ff\', \'Comprendre comment les machines pensent, et construire avec elles.\', 2)');
        $this->addSql('INSERT INTO passion (id, nom, couleur, description, position) VALUES (3, \'Biotechnologie\', \'#2cb67d\', \'Quand le vivant devient une technologie : génétique, bio-ingénierie.\', 3)');
        $this->addSql('INSERT INTO passion (id, nom, couleur, description, position) VALUES (4, \'Science\', \'#e4c35b\', \'La curiosité méthodique : observer, douter, expérimenter.\', 4)');
        $this->addSql('INSERT INTO passion (id, nom, couleur, description, position) VALUES (5, \'Physique\', \'#a06cff\', \'Les lois qui font tourner l’univers, de l’atome aux galaxies.\', 5)');
        $this->addSql('INSERT INTO passion (id, nom, couleur, description, position) VALUES (6, \'Médecine\', \'#f25f8c\', \'Le corps humain, le cerveau, et la façon de les soigner.\', 6)');
        $this->addSql('INSERT INTO etape_parcours (id, nom, dates, intitule, specialite, ecole, lieu, resultat, position) VALUES (1, \'Bachelor IPSSI\', \'2025 - 2026\', \'Bachelor IA, Développement Fullstack DevOps\', NULL, \'IPSSI\', \'Paris\', \'En cours\', 1)');
        $this->addSql('INSERT INTO etape_parcours (id, nom, dates, intitule, specialite, ecole, lieu, resultat, position) VALUES (2, \'BTS SIO\', \'2022 - 2024\', \'SIO : Services informatiques aux organisations\', \'Option B S.L.A.M : Solutions logicielles et Applications Métiers\', \'Lycée UFA Robert Schuman\', \'Dugny, Seine-Saint-Denis\', \'Obtention du BTS\', 2)');
        $this->addSql('INSERT INTO etape_parcours (id, nom, dates, intitule, specialite, ecole, lieu, resultat, position) VALUES (3, \'Bac Pro SN\', \'2019 - 2022\', \'Baccalauréat Professionnel Systèmes Numériques\', \'Option Réseaux Informatiques et Systèmes Communicants (RISC)\', \'Lycée La Salle\', \'Saint-Denis, Seine-Saint-Denis\', \'Obtention du BAC\', 3)');
        $this->addSql('INSERT INTO etape_parcours (id, nom, dates, intitule, specialite, ecole, lieu, resultat, position) VALUES (4, \'Brevet\', \'2010 - 2019\', \'Brevet des collèges\', NULL, \'Lycée La Salle\', \'Saint-Denis, Seine-Saint-Denis\', \'Obtention du brevet\', 4)');
        $this->addSql('SELECT setval(\'categorie_competence_id_seq\', (SELECT MAX(id) FROM categorie_competence))');
        $this->addSql('SELECT setval(\'competence_id_seq\', (SELECT MAX(id) FROM competence))');
        $this->addSql('SELECT setval(\'projet_id_seq\', (SELECT MAX(id) FROM projet))');
        $this->addSql('SELECT setval(\'passion_id_seq\', (SELECT MAX(id) FROM passion))');
        $this->addSql('SELECT setval(\'etape_parcours_id_seq\', (SELECT MAX(id) FROM etape_parcours))');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE competence DROP CONSTRAINT FK_94D4687FBCF5E72D');
        $this->addSql('ALTER TABLE projet_competence DROP CONSTRAINT FK_15498055C18272');
        $this->addSql('ALTER TABLE projet_competence DROP CONSTRAINT FK_1549805515761DAB');
        $this->addSql('DROP TABLE categorie_competence');
        $this->addSql('DROP TABLE competence');
        $this->addSql('DROP TABLE etape_parcours');
        $this->addSql('DROP TABLE passion');
        $this->addSql('DROP TABLE projet');
        $this->addSql('DROP TABLE projet_competence');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
