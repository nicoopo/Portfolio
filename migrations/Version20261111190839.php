<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Trois images de projet étaient déjà du WebP sous une extension .png (servies en image/png) :
 * fichiers renommés en .webp, les chemins enregistrés suivent. Même principe que Version20261008160000.
 */
final class Version20261111190839 extends AbstractMigration
{
    private const RENOMMEES = [
        'reseau/debian_server.png' => 'reseau/debian_server.webp',
        'reseau/cisco_network.png' => 'reseau/cisco_network.webp',
        'symfony/crm_finance.png' => 'symfony/crm_finance.webp',
    ];

    public function getDescription(): string
    {
        return 'Images de projet : extension .webp pour trois WebP nommés en .png';
    }

    public function up(Schema $schema): void
    {
        foreach (self::RENOMMEES as $avant => $apres) {
            $this->addSql('UPDATE projet SET image = :apres WHERE image = :avant', ['avant' => $avant, 'apres' => $apres]);
        }
    }

    public function down(Schema $schema): void
    {
        foreach (self::RENOMMEES as $avant => $apres) {
            $this->addSql('UPDATE projet SET image = :avant WHERE image = :apres', ['avant' => $avant, 'apres' => $apres]);
        }
    }
}
