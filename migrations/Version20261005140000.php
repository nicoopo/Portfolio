<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * CV : alternance chez HabitatPresto (depuis le 20 janvier 2026), en tête des expériences,
 * et accroche mise à jour (plus en recherche d'alternance). Pas de nom interne (outils, services).
 */
final class Version20261005140000 extends AbstractMigration
{
    private const EXPERIENCE = [
        'poste' => 'Développeur Back-end PHP',
        'poste_en' => 'Back-end PHP Developer',
        'entreprise' => 'HABITATPRESTO',
        'lieu' => 'PARIS',
        'periode' => 'Janvier 2026 - aujourd\'hui',
        'periode_en' => 'January 2026 - present',
        'contrat' => 'Alternance (CDA) · télétravail partiel',
        'contrat_en' => 'Work-study (CDA) · partly remote',
        'position' => 1,
        'missions' => <<<'TXT'
            Plateforme de mise en relation entre particuliers et professionnels du bâtiment : développement de fonctionnalités back-end sur une application Laravel en architecture DDD
            Mise en conformité RGPD/CNIL du consentement au suivi des e-mails (formulaires, synchronisation PostgreSQL, liens de désinscription, en-têtes d'e-mails)
            Filtrage des artisans certifiés RGE dans le parcours de demande de devis
            Remplacement d'une tâche cron par un envoi d'e-mails planifié en base, couvert par des tests PHPUnit
            Migration de modules d'administration d'un ancien back-office Zend vers la nouvelle administration
            Extension de l'envoi de SMS aux particuliers, balisage Schema.org JSON-LD (SEO), composants AJAX de saisie rapide
            TXT,
        'missions_en' => <<<'TXT'
            Platform connecting homeowners with building professionals: developed back-end features on a Laravel application built with a DDD architecture
            Brought email tracking consent into GDPR/CNIL compliance (forms, PostgreSQL sync, unsubscribe links, email headers)
            Filtered RGE-certified tradespeople in the quote request flow
            Replaced a cron job with database-scheduled email sending, covered by PHPUnit tests
            Migrated admin modules from a legacy Zend back office to the new administration
            Extended SMS sending to homeowners, added Schema.org JSON-LD markup (SEO) and AJAX quick-entry components
            TXT,
    ];

    private const ACCROCHE_AVANT = "🎓 En recherche d'alternance : Bachelor IA, Développement Fullstack DevOps chez IPSSI\n📅 Rythme : 3 semaines en entreprise / 1 semaine en formation (12 mois)";
    private const ACCROCHE_APRES = "🎓 En alternance chez HabitatPresto depuis janvier 2026 : Bachelor IA, Développement Fullstack DevOps (titre CDA) chez IPSSI\n📅 Rythme : 3 semaines en entreprise / 1 semaine en formation (12 mois)";
    private const ACCROCHE_EN_AVANT = "🎓 Looking for a work-study position: Bachelor in AI, Full-stack Development & DevOps at IPSSI\n📅 Schedule: 3 weeks in the company / 1 week at school (12 months)";
    private const ACCROCHE_EN_APRES = "🎓 Work-study at HabitatPresto since January 2026: Bachelor in AI, Full-stack Development & DevOps (CDA qualification) at IPSSI\n📅 Schedule: 3 weeks in the company / 1 week at school (12 months)";

    public function getDescription(): string
    {
        return 'CV : expérience HabitatPresto et accroche à jour';
    }

    public function up(Schema $schema): void
    {
        // L'expérience la plus récente passe en tête
        $this->addSql('UPDATE experience SET position = position + 1');
        $colonnes = array_keys(self::EXPERIENCE);
        $this->addSql(
            \sprintf('INSERT INTO experience (%s) VALUES (:%s)', implode(', ', $colonnes), implode(', :', $colonnes)),
            self::EXPERIENCE,
        );
        // Seulement si l'accroche n'a pas été modifiée depuis l'admin entre-temps
        $this->addSql('UPDATE cv_profil SET accroche = :apres WHERE accroche = :avant', ['avant' => self::ACCROCHE_AVANT, 'apres' => self::ACCROCHE_APRES]);
        $this->addSql('UPDATE cv_profil SET accroche_en = :apres WHERE accroche_en = :avant', ['avant' => self::ACCROCHE_EN_AVANT, 'apres' => self::ACCROCHE_EN_APRES]);
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DELETE FROM experience WHERE entreprise = 'HABITATPRESTO'");
        $this->addSql('UPDATE experience SET position = position - 1');
        $this->addSql('UPDATE cv_profil SET accroche = :avant WHERE accroche = :apres', ['avant' => self::ACCROCHE_AVANT, 'apres' => self::ACCROCHE_APRES]);
        $this->addSql('UPDATE cv_profil SET accroche_en = :avant WHERE accroche_en = :apres', ['avant' => self::ACCROCHE_EN_AVANT, 'apres' => self::ACCROCHE_EN_APRES]);
    }
}
