<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Image du projet HabitatPresto : capture de l'accueil du site, rangée dans le dépôt.
 * Seulement si aucune image n'est déjà choisie (une image envoyée depuis l'admin l'emporte).
 */
final class Version20261030122410 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Projet HabitatPresto : image';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("UPDATE projet SET image = 'alternance/habitatpresto.webp' WHERE slug = 'habitatpresto' AND image IS NULL AND image_envoyee IS NULL");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("UPDATE projet SET image = NULL WHERE slug = 'habitatpresto' AND image = 'alternance/habitatpresto.webp'");
    }
}
