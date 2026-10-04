<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Messenger retiré (installé par le squelette Symfony, jamais utilisé) : sa table et la
 * fonction qui notifiait les workers disparaissent.
 */
final class Version20261002130130 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Suppression de la table messenger_messages (Messenger retiré)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS messenger_messages'); // emporte sa séquence et son trigger
        $this->addSql('DROP FUNCTION IF EXISTS notify_messenger_messages()');
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException('Messenger n\'est plus installé : rien n\'utiliserait cette table.');
    }
}
