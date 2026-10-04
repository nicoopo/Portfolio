<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Journal des événements (connexions, modifications du contenu, contact).
 */
final class Version20261002123258 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Journal des événements (table journal)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE journal (id SERIAL NOT NULL, date TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, type VARCHAR(20) NOT NULL, message VARCHAR(255) NOT NULL, utilisateur VARCHAR(50) DEFAULT NULL, ip VARCHAR(45) DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_C1A7E74DAA9E377A ON journal (date)');
        $this->addSql('CREATE INDEX IDX_C1A7E74D8CDE5729 ON journal (type)');
        $this->addSql('COMMENT ON COLUMN journal.date IS \'(DC2Type:datetime_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE journal');
    }
}
