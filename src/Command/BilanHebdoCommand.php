<?php

namespace App\Command;

use App\Repository\CandidatureRepository;
use App\Repository\DemandeContactRepository;
use App\Repository\LienRecruteurRepository;
use App\Repository\MessageLivreOrRepository;
use App\Repository\VisiteJourRepository;
use App\Service\Notificateur;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

/** Chaque dimanche soir (cron, voir README) : la semaine du site en une alerte ntfy (7 derniers jours, aujourd'hui compris). */
#[AsCommand('app:bilan:hebdo', 'Envoie sur le téléphone le bilan de la semaine du site')]
final class BilanHebdoCommand
{
    public function __construct(
        private readonly VisiteJourRepository $visites,
        private readonly LienRecruteurRepository $liens,
        private readonly DemandeContactRepository $demandes,
        private readonly MessageLivreOrRepository $livreOr,
        private readonly CandidatureRepository $candidatures,
        private readonly Notificateur $notificateur,
    ) {
    }

    public function __invoke(SymfonyStyle $io): int
    {
        $debut = new \DateTimeImmutable('today -6 days');
        $vues = $this->visites->vuesEntre(6, 0);
        $avant = $this->visites->vuesEntre(13, 7);
        $pages = self::enLigne($this->visites->classement('chemin', 7, 3));
        $sources = self::enLigne($this->visites->classement('source', 7, 3, ['site', 'direct']));
        $liens = $this->liens->entreprisesOuvertesDepuis($debut);
        $contacts = $this->demandes->compterDepuis($debut);
        $livreOr = $this->livreOr->count(['approuve' => false]);
        $relances = $this->candidatures->compterARelancer();

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

    /** Un classement en une ligne : « /cerveau 12, /projects 8… » */
    private static function enLigne(array $lignes): string
    {
        return implode(', ', array_map(fn ($valeur, $total) => "$valeur $total", array_keys($lignes), $lignes));
    }
}
