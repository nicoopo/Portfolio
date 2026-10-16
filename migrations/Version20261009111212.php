<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Statistiques publiques des easter eggs (#204) : un compteur par découverte, aucune donnée personnelle.
 */
final class Version20261009111212 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Compteurs des easter eggs : table decouverte';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE decouverte (id VARCHAR(30) NOT NULL, nombre INT NOT NULL, PRIMARY KEY (id))');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE decouverte');
    }
}
