<?php

namespace App\Command;

use App\Entity\Candidature;
use App\Service\Notificateur;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

/** Chaque matin (cron, voir README) : une alerte ntfy listant les candidatures à relancer ; aucune, aucune alerte. */
#[AsCommand('app:candidatures:relances', 'Envoie sur le téléphone la liste des candidatures à relancer')]
final class RappelerRelancesCommand
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly Notificateur $notificateur,
    ) {
    }

    public function __invoke(SymfonyStyle $io): int
    {
        /** @var list<Candidature> $candidatures */
        $candidatures = $this->entityManager->createQuery('SELECT c FROM '.Candidature::class.' c WHERE c.relancerLe <= :aujourdhui ORDER BY c.relancerLe')
            ->setParameter('aujourdhui', new \DateTimeImmutable('today'))
            ->getResult();

        if ([] === $candidatures) {
            $io->success('Aucune candidature à relancer.');

            return Command::SUCCESS;
        }

        $lignes = array_map(fn (Candidature $c) => '• '.$c.' (envoyée le '.$c->getEnvoyeeLe()->format('d/m').')', $candidatures);
        $this->notificateur->prevenir(\count($candidatures).' candidature(s) à relancer', implode("\n", $lignes), 'alarm_clock');
        $this->notificateur->envoyer(); // pas de kernel.terminate en console

        $io->listing($lignes);
        $io->success(\count($candidatures).' candidature(s) à relancer.');

        return Command::SUCCESS;
    }
}
