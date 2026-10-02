<?php

namespace App\Command;

use App\Repository\JournalRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Supprime les entrées anciennes du journal : il contient des adresses IP (données personnelles),
 * à ne pas garder indéfiniment (12 mois : durée habituellement recommandée par la CNIL).
 */
#[AsCommand('app:journal:purge', 'Supprime les entrées du journal plus anciennes que N mois')]
final class PurgerJournalCommand
{
    public function __construct(private readonly JournalRepository $journal)
    {
    }

    public function __invoke(SymfonyStyle $io, #[Option('Âge maximal, en mois')] int $mois = 12): int
    {
        $limite = new \DateTimeImmutable("-$mois months");
        $supprimees = $this->journal->purgerAvant($limite);

        $io->success("$supprimees entrée(s) antérieure(s) au {$limite->format('d/m/Y')} supprimée(s).");

        return Command::SUCCESS;
    }
}
