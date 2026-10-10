<?php

namespace App\Tests;

use App\Entity\Candidature;
use App\Entity\StatutCandidature;
use PHPUnit\Framework\TestCase;

final class CandidatureTest extends TestCase
{
    /** Relance une semaine après l'envoi, une semaine après chaque relance, plus du tout une fois la réponse arrivée */
    public function testLaDateDeRelanceSuitLeStatut(): void
    {
        $candidature = (new Candidature())->setEnvoyeeLe(new \DateTimeImmutable('2026-09-01'));
        self::assertSame('2026-09-08', $candidature->getRelancerLe()->format('Y-m-d'));
        self::assertTrue($candidature->aRelancer());

        $candidature->setStatut(StatutCandidature::Relancee);
        self::assertSame((new \DateTimeImmutable('today +7 days'))->format('Y-m-d'), $candidature->getRelancerLe()->format('Y-m-d'));
        self::assertFalse($candidature->aRelancer());

        self::assertNull($candidature->getReponseLe());

        $candidature->setStatut(StatutCandidature::Entretien);
        self::assertNull($candidature->getRelancerLe());
        self::assertFalse($candidature->aRelancer());
        self::assertEquals(new \DateTimeImmutable('today'), $candidature->getReponseLe());

        // Entretien → acceptée : la réponse garde sa date (ici remise à hier pour le vérifier)
        (new \ReflectionProperty($candidature, 'reponseLe'))->setValue($candidature, new \DateTimeImmutable('yesterday'));
        $candidature->setStatut(StatutCandidature::Acceptee);
        self::assertEquals(new \DateTimeImmutable('yesterday'), $candidature->getReponseLe());

        // Retour en « Envoyée » (erreur de saisie corrigée) : relance recalculée depuis l'envoi, plus de réponse
        $candidature->setStatut(StatutCandidature::Envoyee);
        self::assertSame('2026-09-08', $candidature->getRelancerLe()->format('Y-m-d'));
        self::assertNull($candidature->getReponseLe());
    }
}
