<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Projet « Portfolio » : le site est passé de deux à six langues.
 * Remplacement de la seule phrase concernée (sans effet si le texte a été réécrit dans l'administration).
 */
final class Version20261008193000 extends AbstractMigration
{
    private const AVANT_FR = 'en français et en anglais, comme le reste du site';
    private const APRES_FR = 'en six langues (français, anglais, espagnol, allemand, italien et portugais), comme le reste du site';
    private const AVANT_EN = 'in French and English, like the rest of the site';
    private const APRES_EN = 'in six languages (French, English, Spanish, German, Italian and Portuguese), like the rest of the site';

    public function getDescription(): string
    {
        return 'Projet Portfolio : « en six langues » au lieu de « en français et en anglais »';
    }

    public function up(Schema $schema): void
    {
        $this->remplacer(self::AVANT_FR, self::APRES_FR, self::AVANT_EN, self::APRES_EN);
    }

    public function down(Schema $schema): void
    {
        $this->remplacer(self::APRES_FR, self::AVANT_FR, self::APRES_EN, self::AVANT_EN);
    }

    private function remplacer(string $avantFr, string $apresFr, string $avantEn, string $apresEn): void
    {
        $this->addSql(
            "UPDATE projet SET details = replace(details, :avant_fr, :apres_fr),
                traductions = jsonb_set(traductions::jsonb, '{en,details}',
                    to_jsonb(replace(traductions::jsonb -> 'en' ->> 'details', :avant_en, :apres_en)))::json
            WHERE slug = 'portfolio' AND jsonb_exists(traductions::jsonb -> 'en', 'details')",
            ['avant_fr' => $avantFr, 'apres_fr' => $apresFr, 'avant_en' => $avantEn, 'apres_en' => $apresEn],
        );
    }
}
