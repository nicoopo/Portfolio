<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Encaissements = première alternance (Tessi Encaissements, 2023 - 2024) : groupe « Alternance »,
 * texte réécrit d'après les missions du CV (code confidentiel, non publié).
 */
final class Version20261005170000 extends AbstractMigration
{
    private const APRES = [
        'categorie' => 'Alternance',
        'categorie_en' => 'Work-study',
        'tech' => 'Symfony, Symfony UX, PHP, MySQL',
        'description' => 'Première alternance : migration vers Symfony d’une application interne de suivi des encaissements et des portefeuilles clients.',
        'description_en' => 'First work-study: migrating to Symfony an internal application for tracking payments and client portfolios.',
        'details' => <<<'TXT'
            Ma première alternance, en contrat d’apprentissage de 2023 à 2024, chez Tessi Encaissements à Nanterre, dans le domaine de l’encaissement : une application web interne pour consulter et suivre les encaissements, et gérer à plusieurs des portefeuilles clients et d’investissements.

            J’ai participé à la migration de sites web d’un ancien noyau PHP vers Symfony, et mis en place des composants dans Symfony : export de données, graphiques dynamiques et tableaux de données dynamiques, avec Symfony UX. J’ai aussi contribué au développement d’un nouveau noyau web.

            C’est là que je me suis spécialisé en Symfony, le framework que je préfère encore aujourd’hui. Le code appartient à l’entreprise : il n’est pas publié.
            TXT,
        'details_en' => <<<'TXT'
            My first work-study position, as an apprentice from 2023 to 2024, at Tessi Encaissements in Nanterre, in the payment collection sector: an internal web application to view and track incoming payments and to manage client and investment portfolios collaboratively.

            I took part in migrating websites from a legacy PHP core to Symfony, and built Symfony components: data export, dynamic charts and dynamic data tables, with Symfony UX. I also contributed to the development of a new web core.

            That is where I specialised in Symfony, still my favourite framework today. The code belongs to the company, so it is not published.
            TXT,
    ];

    private const AVANT = [
        'categorie' => 'PHP / Symfony',
        'categorie_en' => null,
        'tech' => 'Symfony, UX, MYSQL',
        'description' => 'Progiciel pour la gestion de portefeuilles clients et d’investissements.',
        'description_en' => 'Business software for managing client and investment portfolios.',
        'details' => "Un projet réalisé dans le cadre de mon ancien poste : une application web interne, développée avec Symfony et Symfony UX sur une base MySQL, pour consulter et suivre les encaissements, et gérer à plusieurs des portefeuilles clients et d’investissements.\n\nLe code appartient à l’entreprise : il n’est pas publié.",
        'details_en' => "A project built as part of my previous job: an internal web application, developed with Symfony and Symfony UX on a MySQL database, to view and track incoming payments and to manage client and investment portfolios collaboratively.\n\nThe code belongs to the company, so it is not published.",
    ];

    public function getDescription(): string
    {
        return 'Contenu : Encaissements présenté comme première alternance (groupe « Alternance »)';
    }

    public function up(Schema $schema): void
    {
        $this->maj(self::APRES);
    }

    public function down(Schema $schema): void
    {
        $this->maj(self::AVANT);
    }

    private function maj(array $champs): void
    {
        $colonnes = implode(', ', array_map(static fn (string $c) => "$c = :$c", array_keys($champs)));
        $this->addSql("UPDATE projet SET $colonnes WHERE slug = 'encaissements'", $champs);
    }
}
