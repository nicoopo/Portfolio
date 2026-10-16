<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Traductions en JSON (trait App\Entity\Traduisible) : les colonnes « …_en » sont recopiées dans
 * traductions = {"en": {"champ": "…"}}, puis supprimées. Ajouter une langue ne demande plus de migration.
 */
final class Version20261008180958 extends AbstractMigration
{
    /** Table => [champ => type de l'ancienne colonne <champ>_en] */
    private const CHAMPS = [
        'categorie_competence' => ['nom' => 'VARCHAR(50)'],
        'centre_interet' => ['texte' => 'VARCHAR(255)'],
        'competence' => ['nom' => 'VARCHAR(50)'],
        'cv_competence' => ['titre' => 'VARCHAR(100)', 'elements' => 'TEXT'],
        'cv_profil' => ['titre' => 'VARCHAR(100)', 'qualites' => 'VARCHAR(150)', 'resume' => 'TEXT', 'accroche' => 'TEXT'],
        'etape_parcours' => ['nom' => 'VARCHAR(50)', 'intitule' => 'VARCHAR(150)', 'specialite' => 'VARCHAR(150)', 'resultat' => 'VARCHAR(100)'],
        'experience' => ['poste' => 'VARCHAR(100)', 'periode' => 'VARCHAR(50)', 'contrat' => 'VARCHAR(50)', 'missions' => 'TEXT'],
        'langue' => ['nom' => 'VARCHAR(50)', 'niveau' => 'VARCHAR(50)'],
        'passion' => ['nom' => 'VARCHAR(50)', 'description' => 'TEXT'],
        'projet' => ['titre' => 'VARCHAR(100)', 'description' => 'TEXT', 'categorie' => 'VARCHAR(50)', 'details' => 'TEXT'],
    ];

    public function getDescription(): string
    {
        return 'Traductions dans une colonne JSON par entité (remplace les colonnes …_en)';
    }

    public function up(Schema $schema): void
    {
        foreach (self::CHAMPS as $table => $champs) {
            $this->addSql("ALTER TABLE $table ADD traductions JSON DEFAULT '{}' NOT NULL");
            // {"champ": valeur} sans les champs vides ; {"en": …} seulement s'il reste quelque chose
            $anglais = 'jsonb_strip_nulls(jsonb_build_object('.implode(', ', array_map(
                static fn (string $champ) => "'$champ', NULLIF({$champ}_en, '')",
                array_keys($champs),
            )).'))';
            $this->addSql("UPDATE $table SET traductions = CASE WHEN $anglais = '{}'::jsonb THEN '{}'::json
                ELSE jsonb_build_object('en', $anglais)::json END");
            foreach (array_keys($champs) as $champ) {
                $this->addSql("ALTER TABLE $table DROP {$champ}_en");
            }
        }
    }

    /** Retour aux colonnes « …_en » : seul l'anglais est conservé */
    public function down(Schema $schema): void
    {
        foreach (self::CHAMPS as $table => $champs) {
            foreach ($champs as $champ => $type) {
                $this->addSql("ALTER TABLE $table ADD {$champ}_en $type DEFAULT NULL");
                $this->addSql("UPDATE $table SET {$champ}_en = traductions::jsonb -> 'en' ->> '$champ'");
            }
            $this->addSql("ALTER TABLE $table DROP traductions");
        }
    }
}
