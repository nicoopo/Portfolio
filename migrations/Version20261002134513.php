<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261002134513 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Projets : page détaillée (texte long FR/EN, liens code et démo)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE projet ADD details TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE projet ADD depot VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE projet ADD demo VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE projet ADD details_en TEXT DEFAULT NULL');

        // Les autres projets se complètent depuis l'admin (sans texte long, leur page affiche la description)
        $this->addSql('UPDATE projet SET details = :details, details_en = :detailsEn, depot = :depot WHERE slug = :slug', [
            'slug' => 'portfolio',
            'depot' => 'https://github.com/nicoopo/Portfolio',
            'details' => <<<'TXT'
                Mon portfolio n'est pas une simple vitrine : c'est un projet complet, pensé comme une application. L'idée centrale est un cerveau en 3D où chaque neurone est une compétence, relié par des synapses aux autres compétences et aux projets qui l'utilisent. Mon parcours y forme un fil de souvenirs et mes passions des nébuleuses tout autour.

                Le cerveau est un nuage de points généré en Three.js : plis du cortex en relief, éclairage calculé point par point, nébuleuses, lueur (bloom) et sons d'ambiance synthétisés avec la Web Audio API. On peut s'y déplacer librement, chercher une compétence, suivre une visite guidée ou partager un lien vers une vue précise.

                Côté serveur, c'est du Symfony 7 avec PostgreSQL et Doctrine : tout le contenu (compétences, projets, parcours, CV) vient de la base et se modifie depuis une administration EasyAdmin protégée (comptes en base, limitation des tentatives de connexion, journal des actions). Le CV est généré depuis la base, en HTML et en PDF, en français et en anglais, comme le reste du site.

                Le projet tourne dans Docker, avec des tests automatisés (PHPUnit), une attention à l'accessibilité (navigation au clavier, lecteurs d'écran, mouvement réduit) et aux performances (assets mis en cache, rendu 3D allégé sur mobile).
                TXT,
            'detailsEn' => <<<'TXT'
                My portfolio is not just a showcase: it is a complete project, built like an application. The core idea is a 3D brain where each neuron is a skill, linked by synapses to other skills and to the projects that use it. My background forms a thread of memories, and my passions are nebulae all around it.

                The brain is a point cloud generated with Three.js: raised cortex folds, per-point lighting, nebulae, bloom and ambient sound synthesised with the Web Audio API. You can move around freely, search for a skill, follow a guided tour or share a link to a specific view.

                On the server side, it runs on Symfony 7 with PostgreSQL and Doctrine: all the content (skills, projects, background, CV) comes from the database and is edited through a protected EasyAdmin back office (database accounts, login throttling, activity log). The CV is generated from the database, as HTML and PDF, in French and English, like the rest of the site.

                The project runs in Docker, with automated tests (PHPUnit), care for accessibility (keyboard navigation, screen readers, reduced motion) and performance (cached assets, lighter 3D rendering on mobile).
                TXT,
        ]);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE projet DROP details');
        $this->addSql('ALTER TABLE projet DROP depot');
        $this->addSql('ALTER TABLE projet DROP demo');
        $this->addSql('ALTER TABLE projet DROP details_en');
    }
}
