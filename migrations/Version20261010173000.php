<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Lien recruteur : accroche et points forts (compétences, 5 au plus) du CV ouvert depuis ce lien.
 */
final class Version20261010173000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Lien recruteur : accroche et points forts du CV';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE lien_recruteur ADD accroche TEXT DEFAULT NULL');
        $this->addSql('CREATE TABLE lien_recruteur_competence (lien_recruteur_id INT NOT NULL, competence_id INT NOT NULL, PRIMARY KEY (lien_recruteur_id, competence_id))');
        $this->addSql('CREATE INDEX IDX_6737A4CF13F011A0 ON lien_recruteur_competence (lien_recruteur_id)');
        $this->addSql('CREATE INDEX IDX_6737A4CF15761DAB ON lien_recruteur_competence (competence_id)');
        $this->addSql('ALTER TABLE lien_recruteur_competence ADD CONSTRAINT FK_6737A4CF13F011A0 FOREIGN KEY (lien_recruteur_id) REFERENCES lien_recruteur (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE lien_recruteur_competence ADD CONSTRAINT FK_6737A4CF15761DAB FOREIGN KEY (competence_id) REFERENCES competence (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE lien_recruteur_competence');
        $this->addSql('ALTER TABLE lien_recruteur DROP accroche');
    }
}
