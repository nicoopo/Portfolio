<?php

namespace App\Tests;

use App\Entity\Candidature;
use App\Entity\StatutCandidature;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

final class RappelerRelancesTest extends KernelTestCase
{
    /** Seules les candidatures dont la date de relance est passée sont listées */
    public function testSeulesLesCandidaturesEnRetardSontListees(): void
    {
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $enRetard = (new Candidature())->setEntreprise('Acme Relance')->setPoste('Dev')->setEnvoyeeLe(new \DateTimeImmutable('-10 days'));
        $recente = (new Candidature())->setEntreprise('Jeune Pousse')->setPoste('Dev');
        $repondu = (new Candidature())->setEntreprise('Déjà Répondu')->setPoste('Dev')
            ->setEnvoyeeLe(new \DateTimeImmutable('-10 days'))->setStatut(StatutCandidature::Entretien);
        foreach ([$enRetard, $recente, $repondu] as $candidature) {
            $entityManager->persist($candidature);
        }
        $entityManager->flush();

        try {
            $tester = new CommandTester((new Application(self::$kernel))->find('app:candidatures:relances'));
            $tester->execute([]);

            $sortie = $tester->getDisplay();
            self::assertStringContainsString('Acme Relance', $sortie);
            self::assertStringNotContainsString('Jeune Pousse', $sortie);
            self::assertStringNotContainsString('Déjà Répondu', $sortie);
        } finally {
            foreach ([$enRetard, $recente, $repondu] as $candidature) {
                $entityManager->remove($candidature);
            }
            $entityManager->flush();
        }
    }
}
