<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Expérience : lien vers le site auquel j'ai contribué (HabitatPresto) ; compétence Laravel (cerveau et CV).
 */
final class Version20261005150000 extends AbstractMigration
{
    private const CV_AVANT = 'PHP (OOP), Symfony, MVC, HTML/CSS/JS, JavaScript, Java, Go, PHPUnit';
    private const CV_APRES = 'PHP (OOP), Symfony, Laravel, MVC, HTML/CSS/JS, JavaScript, Java, Go, PHPUnit';

    public function getDescription(): string
    {
        return 'Expérience : champ site (HabitatPresto) ; compétence Laravel';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE experience ADD site VARCHAR(255) DEFAULT NULL');
        $this->addSql("UPDATE experience SET site = 'https://www.habitatpresto.com/' WHERE entreprise = 'HABITATPRESTO'");

        // Cerveau : Back-End, à la suite (aucun projet public à relier : le code d'HabitatPresto est confidentiel)
        $this->addSql("INSERT INTO competence (categorie_id, nom, position)
            SELECT c.id, 'Laravel', COALESCE((SELECT MAX(k.position) FROM competence k WHERE k.categorie_id = c.id), 0) + 1
            FROM categorie_competence c WHERE c.nom = 'Back-End'");

        // CV : juste après Symfony, seulement si la ligne n'a pas été modifiée depuis l'admin
        $this->addSql("UPDATE cv_competence SET elements = :apres WHERE titre = 'Développement Fullstack & Mobile' AND elements = :avant", ['avant' => self::CV_AVANT, 'apres' => self::CV_APRES]);
    }

    public function down(Schema $schema): void
    {
        $this->addSql("UPDATE cv_competence SET elements = :avant WHERE titre = 'Développement Fullstack & Mobile' AND elements = :apres", ['avant' => self::CV_AVANT, 'apres' => self::CV_APRES]);
        $this->addSql("DELETE FROM competence WHERE nom = 'Laravel'");
        $this->addSql('ALTER TABLE experience DROP site');
    }
}
