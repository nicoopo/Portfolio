<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * CV : les compétences ajoutées au cerveau (Version20261005120000) et Docker, dans la section « Compétences ».
 * Les versions anglaises de ces deux lignes sont vides : le CV anglais reprend le français (noms d'outils).
 */
final class Version20261005130000 extends AbstractMigration
{
    /** titre => [avant, après] */
    private const LIGNES = [
        'Développement Fullstack & Mobile' => [
            'PHP (OOP), Symfony, MVC, HTML/CSS/JS, JavaScript, Java',
            'PHP (OOP), Symfony, MVC, HTML/CSS/JS, JavaScript, Java, Go, PHPUnit',
        ],
        'Outils & DevOps' => [
            'Git / GitHub, GitLab CI/CD, Windows/Linux, Ubuntu, Debian, Bash/PowerShell',
            'Git / GitHub, GitLab CI/CD, GitHub Actions, Docker, SonarQube, GlitchTip / Sentry, Windows/Linux, Ubuntu, Debian, Bash/PowerShell',
        ],
    ];

    public function getDescription(): string
    {
        return 'CV : Go, PHPUnit, GitHub Actions, Docker, SonarQube, GlitchTip / Sentry';
    }

    public function up(Schema $schema): void
    {
        foreach (self::LIGNES as $titre => [$avant, $apres]) {
            // Seulement si la ligne n'a pas été modifiée depuis l'admin entre-temps
            $this->addSql('UPDATE cv_competence SET elements = :apres WHERE titre = :titre AND elements = :avant', ['titre' => $titre, 'avant' => $avant, 'apres' => $apres]);
        }
    }

    public function down(Schema $schema): void
    {
        foreach (self::LIGNES as $titre => [$avant, $apres]) {
            $this->addSql('UPDATE cv_competence SET elements = :avant WHERE titre = :titre AND elements = :apres', ['titre' => $titre, 'avant' => $avant, 'apres' => $apres]);
        }
    }
}
