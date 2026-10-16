<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Captures des trois projets ajoutés par Version20261004200000 (images du dépôt, assets/images/projets/).
 * mini-PaaS : vrai déploiement depuis son tableau de bord ; SecureScan : scan de DVWA (application volontairement
 * vulnérable) ; OBD : interface sans voiture branchée, à remplacer par une capture en conditions réelles.
 */
final class Version20261004210000 extends AbstractMigration
{
    private const IMAGES = [
        'mini-paas' => 'outils/mini-paas.webp',
        'tableau-de-bord-obd' => 'outils/tableau-de-bord-obd.webp',
        'securescan' => 'symfony/securescan.webp',
    ];

    public function getDescription(): string
    {
        return 'Contenu : captures de mini-PaaS, du Tableau de bord OBD et de SecureScan';
    }

    public function up(Schema $schema): void
    {
        foreach (self::IMAGES as $slug => $image) {
            $this->addSql('UPDATE projet SET image = :image WHERE slug = :slug AND image IS NULL', ['image' => $image, 'slug' => $slug]);
        }
    }

    public function down(Schema $schema): void
    {
        foreach (self::IMAGES as $slug => $image) {
            $this->addSql('UPDATE projet SET image = NULL WHERE slug = :slug AND image = :image', ['image' => $image, 'slug' => $slug]);
        }
    }
}
