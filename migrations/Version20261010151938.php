<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Date de réponse des candidatures (délai de réponse du tableau de bord). Les réponses déjà saisies restent sans date.
 */
final class Version20261010151938 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Candidature : date de réponse';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE candidature ADD reponse_le DATE DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE candidature DROP reponse_le');
    }
}
