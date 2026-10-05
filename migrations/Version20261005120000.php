<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Cinq compétences de plus, chacune reliée aux projets qui la montrent.
 * Le cerveau 3D place les nouveaux neurones tout seul dans le lobe de leur catégorie (assets/cerveau/neurons.js).
 */
final class Version20261005120000 extends AbstractMigration
{
    /** nom => [catégorie, projets (slugs)] */
    private const COMPETENCES = [
        'Go' => ['Back-End', ['mini-paas', 'gestionnaire-de-serveur']],
        'PHPUnit' => ['Back-End', ['plateforme-qcm', 'portfolio']],
        'SonarQube' => ['Outils', ['plateforme-qcm', 'portfolio']],
        'GlitchTip / Sentry' => ['Outils', ['plateforme-qcm', 'portfolio']],
        'GitHub Actions' => ['Outils', ['plateforme-qcm', 'portfolio']],
    ];

    public function getDescription(): string
    {
        return 'Compétences : Go, PHPUnit, SonarQube, GlitchTip / Sentry, GitHub Actions';
    }

    public function up(Schema $schema): void
    {
        foreach (self::COMPETENCES as $nom => [$categorie, $projets]) {
            // À la suite des compétences existantes de la catégorie
            $this->addSql(
                'INSERT INTO competence (categorie_id, nom, position)
                 SELECT c.id, :nom, COALESCE((SELECT MAX(k.position) FROM competence k WHERE k.categorie_id = c.id), 0) + 1
                 FROM categorie_competence c WHERE c.nom = :categorie',
                ['nom' => $nom, 'categorie' => $categorie],
            );
            foreach ($projets as $slug) {
                $this->addSql(
                    'INSERT INTO projet_competence (projet_id, competence_id) SELECT p.id, k.id FROM projet p, competence k WHERE p.slug = :slug AND k.nom = :nom',
                    ['slug' => $slug, 'nom' => $nom],
                );
            }
        }
    }

    public function down(Schema $schema): void
    {
        foreach (array_keys(self::COMPETENCES) as $nom) {
            // projet_competence suit (ON DELETE CASCADE)
            $this->addSql('DELETE FROM competence WHERE nom = :nom', ['nom' => $nom]);
        }
    }
}
