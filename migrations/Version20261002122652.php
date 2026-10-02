<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Comptes de l'administration (table « utilisateur » : « user » est réservé en PostgreSQL).
 */
final class Version20261002122652 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Comptes de l\'administration (table utilisateur)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE utilisateur (id SERIAL NOT NULL, identifiant VARCHAR(50) NOT NULL, mot_de_passe VARCHAR(255) NOT NULL, roles JSON NOT NULL, derniere_connexion TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_1D1C63B3C90409EC ON utilisateur (identifiant)');
        $this->addSql('COMMENT ON COLUMN utilisateur.derniere_connexion IS \'(DC2Type:datetime_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE utilisateur');
    }
}
