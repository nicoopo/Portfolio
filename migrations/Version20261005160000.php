<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Projet HabitatPresto (alternance), pour relier le neurone Laravel et les autres compétences utilisées.
 * Code confidentiel : problèmes et solutions seulement, sans nom interne, sans capture ni extrait de code.
 */
final class Version20261005160000 extends AbstractMigration
{
    private const PROJET = [
        'slug' => 'habitatpresto',
        'titre' => 'HabitatPresto',
        'titre_en' => null,
        'categorie' => 'Alternance',
        'categorie_en' => 'Work-study',
        'tech' => 'Laravel, PHP, PostgreSQL, MySQL, PHPUnit, Docker',
        'position' => 0, // le groupe « Alternance » passe en tête de la page Projets
        'depot' => null,
        'demo' => 'https://www.habitatpresto.com/',
        'description' => 'Alternance : développement back-end de la plateforme qui met en relation particuliers et professionnels du bâtiment.',
        'description_en' => 'Work-study: back-end development of the platform connecting homeowners with building professionals.',
        'details' => <<<'TXT'
            Depuis janvier 2026, je suis développeur back-end PHP en alternance chez HabitatPresto, une plateforme qui met en relation les particuliers avec des professionnels du bâtiment. L’application est écrite en Laravel, en architecture DDD (Domain-Driven Design), et je travaille en équipe sur des branches partagées, avec revues de code et tests.

            Mon sujet le plus transverse : la mise en conformité RGPD/CNIL du suivi des e-mails. Un enjeu réglementaire qui touche toute l’application, des formulaires de consentement à la synchronisation en base PostgreSQL, en passant par les liens de désinscription et les en-têtes des e-mails.

            J’ai aussi remplacé une tâche cron par un envoi d’e-mails planifié en base de données, plus fiable, couvert par des tests PHPUnit et validé par le lead dev. Côté utilisateurs, j’ai ajouté le filtrage des artisans certifiés RGE dans le parcours de demande de devis, avec une règle métier qui dépend d’une fenêtre de temps.

            Enfin, j’ai migré des modules d’administration d’un ancien back-office Zend vers la nouvelle administration, étendu l’envoi de SMS aux particuliers et ajouté un balisage Schema.org pour le référencement.

            J’en retiens le travail en équipe sur une grosse base de code existante : comprendre avant de modifier, découper proprement et tester. Le code appartient à l’entreprise : il n’est pas publié.
            TXT,
        'details_en' => <<<'TXT'
            Since January 2026, I have been a work-study back-end PHP developer at HabitatPresto, a platform connecting homeowners with building professionals. The application is written in Laravel with a DDD (Domain-Driven Design) architecture, and I work in a team on shared branches, with code reviews and tests.

            My most cross-cutting topic: bringing email tracking into GDPR/CNIL compliance. A regulatory issue that touches the whole application, from consent forms to syncing with the PostgreSQL database, unsubscribe links and email headers.

            I also replaced a cron job with database-scheduled email sending, more reliable, covered by PHPUnit tests and approved by the lead developer. On the user side, I added filtering of RGE-certified tradespeople in the quote request flow, with a business rule that depends on a time window.

            Finally, I migrated admin modules from a legacy Zend back office to the new administration, extended SMS sending to homeowners and added Schema.org markup for SEO.

            What I take away: working as a team on a large existing codebase. Understanding before changing, splitting work cleanly, and testing. The code belongs to the company, so it is not published.
            TXT,
    ];

    private const COMPETENCES = ['Laravel', 'PHP / Symfony', 'MySQL / PostgreSQL', 'PHPUnit', 'Docker', 'Git / GitHub', 'Travail en équipe'];

    public function getDescription(): string
    {
        return 'Contenu : projet HabitatPresto (alternance), relié au neurone Laravel';
    }

    public function up(Schema $schema): void
    {
        $colonnes = array_keys(self::PROJET);
        $this->addSql(
            \sprintf('INSERT INTO projet (%s) VALUES (:%s)', implode(', ', $colonnes), implode(', :', $colonnes)),
            self::PROJET,
        );
        foreach (self::COMPETENCES as $competence) {
            $this->addSql(
                'INSERT INTO projet_competence (projet_id, competence_id) SELECT p.id, c.id FROM projet p, competence c WHERE p.slug = :slug AND c.nom = :nom',
                ['slug' => self::PROJET['slug'], 'nom' => $competence],
            );
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DELETE FROM projet_competence WHERE projet_id = (SELECT id FROM projet WHERE slug = 'habitatpresto')");
        $this->addSql("DELETE FROM projet WHERE slug = 'habitatpresto'");
    }
}
