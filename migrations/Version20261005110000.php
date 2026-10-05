<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Gestionnaire de serveur : develop a été fusionnée dans master (branche par défaut),
 * le lien vers le code pointe donc vers la page d'accueil du dépôt.
 */
final class Version20261005110000 extends AbstractMigration
{
    private const AVANT = 'https://github.com/nicoopo/Gestionnaire_de_Serveur/tree/develop';
    private const APRES = 'https://github.com/nicoopo/Gestionnaire_de_Serveur';

    public function getDescription(): string
    {
        return 'Gestionnaire de serveur : lien vers la page d\'accueil du dépôt';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("UPDATE projet SET depot = :apres WHERE slug = 'gestionnaire-de-serveur' AND depot = :avant", ['avant' => self::AVANT, 'apres' => self::APRES]);
    }

    public function down(Schema $schema): void
    {
        $this->addSql("UPDATE projet SET depot = :avant WHERE slug = 'gestionnaire-de-serveur' AND depot = :apres", ['avant' => self::AVANT, 'apres' => self::APRES]);
    }
}
