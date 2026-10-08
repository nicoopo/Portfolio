<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Images de projet du dépôt réencodées en WebP (audit Lighthouse, #161) : les chemins enregistrés suivent.
 * Seules celles dont le WebP est plus léger changent ; les autres restent en PNG.
 */
final class Version20261008160000 extends AbstractMigration
{
    private const RENOMMEES = [
        'java/crud.png' => 'java/crud.webp',
        'java/pendu.png' => 'java/pendu.webp',
        'java/poupee.png' => 'java/poupee.webp',
        'symfony/portfolio-cerveau.jpg' => 'symfony/portfolio-cerveau.webp',
    ];

    public function getDescription(): string
    {
        return 'Images de projet : chemins des images passées en WebP';
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
