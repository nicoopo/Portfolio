<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Frise des projets (#216) : année de chaque projet, remplie dans l'admin.
 */
final class Version20261009133321 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Projets : année (frise)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE projet ADD annee SMALLINT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE projet DROP annee');
    }
}
