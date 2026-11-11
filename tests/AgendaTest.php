<?php

namespace App\Tests;

use App\Service\Agenda;
use PHPUnit\Framework\TestCase;

final class AgendaTest extends TestCase
{
    /** Heure saisie à Paris mais lue en UTC (fuseau de PHP) : l'événement des liens d'agenda la remet à Paris, heure d'hiver comme d'été */
    public function testLEvenementGardeLHeureDeParis(): void
    {
        $utc = new \DateTimeZone('UTC');
        foreach (['2026-11-20 14:00:00' => '2026-11-20 13:00', '2026-07-01 14:00:00' => '2026-07-01 12:00'] as $saisie => $attendu) {
            $evenement = (new Agenda())->evenement(new \DateTimeImmutable($saisie, $utc), 'Entretien');
            self::assertSame($attendu, $evenement->start->setTimezone($utc)->format('Y-m-d H:i'), $saisie);
            self::assertSame(3600, $evenement->end->getTimestamp() - $evenement->start->getTimestamp());
        }
        self::assertNull((new Agenda())->evenement(new \DateTimeImmutable('2026-11-20 14:00', $utc), 'Entretien')->description);
    }
}
