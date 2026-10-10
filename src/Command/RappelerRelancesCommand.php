<?php

namespace App\Command;

use App\Entity\Candidature;
use App\Service\Notificateur;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Chaque matin (cron, voir README) : une alerte ntfy pour les entretiens d'aujourd'hui et de demain,
 * une autre listant les candidatures à relancer ; rien à signaler, aucune alerte.
 */
#[AsCommand('app:candidatures:relances', 'Envoie sur le téléphone les entretiens proches et les candidatures à relancer')]
final class RappelerRelancesCommand
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly Notificateur $notificateur,
    ) {
    }

    public function __invoke(SymfonyStyle $io): int
    {
        /** @var list<Candidature> $entretiens */
        $entretiens = $this->entityManager->createQuery('SELECT c FROM '.Candidature::class.' c WHERE c.entretienLe >= :aujourdhui AND c.entretienLe < :apresDemain ORDER BY c.entretienLe')
            ->setParameter('aujourdhui', new \DateTimeImmutable('today'))
            ->setParameter('apresDemain', new \DateTimeImmutable('today +2 days'))
            ->getResult();
        /** @var list<Candidature> $relances */
        $relances = $this->entityManager->createQuery('SELECT c FROM '.Candidature::class.' c WHERE c.relancerLe <= :aujourdhui ORDER BY c.relancerLe')
            ->setParameter('aujourdhui', new \DateTimeImmutable('today'))
            ->getResult();

        $lignesEntretiens = array_map(fn (Candidature $c) => '• '.$c.' : '
            .($c->getEntretienLe() < new \DateTimeImmutable('tomorrow') ? 'aujourd’hui' : 'demain').' à '.$c->getEntretienLe()->format('H:i'), $entretiens);
        $lignesRelances = array_map(fn (Candidature $c) => '• '.$c.' (envoyée le '.$c->getEnvoyeeLe()->format('d/m').')', $relances);

        if ($entretiens) {
            $this->notificateur->prevenir(\count($entretiens).' entretien(s) aujourd’hui ou demain', implode("\n", $lignesEntretiens), 'handshake');
        }
        if ($relances) {
            $this->notificateur->prevenir(\count($relances).' candidature(s) à relancer', implode("\n", $lignesRelances), 'alarm_clock');
        }
        $this->notificateur->envoyer(); // pas de kernel.terminate en console

        $io->section('Entretiens aujourd’hui et demain');
        $entretiens ? $io->listing($lignesEntretiens) : $io->text('Aucun.');
        $io->section('Candidatures à relancer');
        $relances ? $io->listing($lignesRelances) : $io->text('Aucune.');

        return Command::SUCCESS;
    }
}
