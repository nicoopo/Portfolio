<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Lieu approché d'après l'IP (coordonnées arrondies, pas l'IP), pour le globe de /stats.
 */
final class Version20261012220000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Arrivées : lieu d’après l’IP';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE arrivee ADD ip_lat DOUBLE PRECISION DEFAULT NULL');
        $this->addSql('ALTER TABLE arrivee ADD ip_lon DOUBLE PRECISION DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE arrivee DROP ip_lat');
        $this->addSql('ALTER TABLE arrivee DROP ip_lon');
    }
}
