<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261004180000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Projets : image envoyée depuis l\'admin (public/uploads/projets), l\'image du dépôt devient facultative';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE projet ADD image_envoyee VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE projet ALTER image DROP NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql("UPDATE projet SET image = '' WHERE image IS NULL");
        $this->addSql('ALTER TABLE projet ALTER image SET NOT NULL');
        $this->addSql('ALTER TABLE projet DROP image_envoyee');
    }
}
