<?php

namespace App\Command;

use App\Service\Notificateur;
use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

/** Chaque dimanche soir (cron, voir README) : la semaine du site en une alerte ntfy (7 derniers jours, aujourd'hui compris). */
#[AsCommand('app:bilan:hebdo', 'Envoie sur le téléphone le bilan de la semaine du site')]
final class BilanHebdoCommand
{
    public function __construct(
        private readonly Connection $connection,
        private readonly Notificateur $notificateur,
    ) {
    }

    public function __invoke(SymfonyStyle $io): int
    {
        $vues = (int) $this->connection->fetchOne('SELECT COALESCE(SUM(nombre), 0) FROM visite_jour WHERE jour >= CURRENT_DATE - 6');
        $avant = (int) $this->connection->fetchOne('SELECT COALESCE(SUM(nombre), 0) FROM visite_jour WHERE jour BETWEEN CURRENT_DATE - 13 AND CURRENT_DATE - 7');
        $pages = $this->classement('chemin');
        $sources = $this->classement('source', "AND source NOT IN ('site', 'direct')");
        $liens = $this->connection->fetchFirstColumn("SELECT entreprise FROM lien_recruteur WHERE derniere_visite >= CURRENT_DATE - 6 ORDER BY derniere_visite DESC");
        $contacts = (int) $this->connection->fetchOne('SELECT COUNT(*) FROM demande_contact WHERE recu_le >= CURRENT_DATE - 6');
        $livreOr = (int) $this->connection->fetchOne('SELECT COUNT(*) FROM message_livre_or WHERE NOT approuve');
        $relances = (int) $this->connection->fetchOne('SELECT COUNT(*) FROM candidature WHERE relancer_le <= CURRENT_DATE');

        $lignes = array_filter([
            "👀 $vues pages vues".($avant ? ' ('.($vues >= $avant ? '+' : '').round(100 * ($vues - $avant) / $avant).' %)' : ''),
            $pages ? '📄 '.$pages : null,
            $sources ? '🔗 '.$sources : null,
            $liens ? '💼 Liens recruteur ouverts : '.implode(', ', $liens) : null,
            $contacts ? "✉️ $contacts message(s) de contact" : null,
            $livreOr ? "⭐ $livreOr message(s) du livre d'or à modérer" : null,
            $relances ? "⏰ $relances candidature(s) à relancer" : null,
        ]);

        $this->notificateur->prevenir('Bilan de la semaine', implode("\n", $lignes), 'bar_chart');
        $this->notificateur->envoyer(); // pas de kernel.terminate en console

        $io->listing($lignes);

        return Command::SUCCESS;
    }

    /** Les 3 premières valeurs d'une colonne de visite_jour sur 7 jours : « /cerveau 12, /projects 8… » */
    private function classement(string $colonne, string $filtre = ''): string
    {
        $lignes = $this->connection->fetchAllKeyValue(
            "SELECT $colonne, SUM(nombre) AS total FROM visite_jour WHERE jour >= CURRENT_DATE - 6 $filtre GROUP BY $colonne ORDER BY total DESC LIMIT 3",
        );

        return implode(', ', array_map(fn ($valeur, $total) => "$valeur $total", array_keys($lignes), $lignes));
    }
}
