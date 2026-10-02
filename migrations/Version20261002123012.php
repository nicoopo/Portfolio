<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Messages du formulaire de contact (enregistrés avant l'envoi de l'e-mail).
 */
final class Version20261002123012 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Demandes de contact (table demande_contact)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE demande_contact (id SERIAL NOT NULL, nom VARCHAR(100) NOT NULL, email VARCHAR(180) NOT NULL, message TEXT NOT NULL, langue VARCHAR(2) NOT NULL, statut VARCHAR(10) NOT NULL, recu_le TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_7C955D97AFA10557 ON demande_contact (recu_le)');
        $this->addSql('COMMENT ON COLUMN demande_contact.recu_le IS \'(DC2Type:datetime_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE demande_contact');
    }
}
