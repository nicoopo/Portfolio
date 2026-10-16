<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Projets mis en avant par lien recruteur (3 au plus), affichés sur l'accueil personnalisé.
 */
final class Version20261010161319 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Lien recruteur : projets mis en avant';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE lien_recruteur_projet (lien_recruteur_id INT NOT NULL, projet_id INT NOT NULL, PRIMARY KEY (lien_recruteur_id, projet_id))');
        $this->addSql('CREATE INDEX IDX_43571D2813F011A0 ON lien_recruteur_projet (lien_recruteur_id)');
        $this->addSql('CREATE INDEX IDX_43571D28C18272 ON lien_recruteur_projet (projet_id)');
        $this->addSql('ALTER TABLE lien_recruteur_projet ADD CONSTRAINT FK_43571D2813F011A0 FOREIGN KEY (lien_recruteur_id) REFERENCES lien_recruteur (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE lien_recruteur_projet ADD CONSTRAINT FK_43571D28C18272 FOREIGN KEY (projet_id) REFERENCES projet (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE lien_recruteur_projet DROP CONSTRAINT FK_43571D2813F011A0');
        $this->addSql('ALTER TABLE lien_recruteur_projet DROP CONSTRAINT FK_43571D28C18272');
        $this->addSql('DROP TABLE lien_recruteur_projet');
    }
}
