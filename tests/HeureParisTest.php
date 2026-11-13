<?php

namespace App\Tests;

use App\HeureParis;
use PHPUnit\Framework\TestCase;

final class HeureParisTest extends TestCase
{
    /** « Aujourd'hui » est la date de Paris, rendue à minuit dans le fuseau de PHP (comme les dates lues en base) */
    public function testAujourdhuiEstLaDateDeParis(): void
    {
        $paris = new \DateTimeImmutable('now', new \DateTimeZone('Europe/Paris'));
        $aujourdhui = HeureParis::at('today');

        self::assertSame($paris->format('Y-m-d').' 00:00:00', $aujourdhui->format('Y-m-d H:i:s'));
        self::assertSame(date_default_timezone_get(), $aujourdhui->getTimezone()->getName());
    }
}
