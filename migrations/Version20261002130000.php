<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Animation du projet Todolist JavaFX : GIF (246 Ko) → WebP animé (79 Ko).
 */
final class Version20261002130000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Image du projet Todolist JavaFX en WebP';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("UPDATE projet SET image = 'java/javafx_todo.webp' WHERE image = 'java/javafx_todo.gif'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("UPDATE projet SET image = 'java/javafx_todo.gif' WHERE image = 'java/javafx_todo.webp'");
    }
}
