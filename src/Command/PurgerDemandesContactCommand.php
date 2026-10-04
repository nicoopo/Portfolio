<?php

namespace App\Command;

use App\Repository\DemandeContactRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Supprime les demandes de contact anciennes (nom, e-mail, message : données personnelles).
 * 12 mois : durée annoncée dans la politique de confidentialité.
 */
#[AsCommand('app:contact:purge', 'Supprime les demandes de contact plus anciennes que N mois')]
final class PurgerDemandesContactCommand
{
    public function __construct(private readonly DemandeContactRepository $demandes)
    {
    }

    public function __invoke(SymfonyStyle $io, #[Option('Âge maximal, en mois')] int $mois = 12): int
    {
        $limite = new \DateTimeImmutable("-$mois months");
        $supprimees = $this->demandes->purgerAvant($limite);

        $io->success("$supprimees demande(s) reçue(s) avant le {$limite->format('d/m/Y')} supprimée(s).");

        return Command::SUCCESS;
    }
}
