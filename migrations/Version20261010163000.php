<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Candidature : e-mail du recruteur (relance en un clic) et date d'entretien (rappel ntfy, fichier .ics).
 */
final class Version20261010163000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Candidature : e-mail du recruteur et date d\'entretien';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE candidature ADD email VARCHAR(180) DEFAULT NULL');
        $this->addSql('ALTER TABLE candidature ADD entretien_le TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE candidature DROP email');
        $this->addSql('ALTER TABLE candidature DROP entretien_le');
    }
}
