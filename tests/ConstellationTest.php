<?php

namespace App\Tests;

use App\Twig\Constellation;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

final class ConstellationTest extends TestCase
{
    /** Toutes les étoiles dans le cadre (marge pour leur halo), et chacune reliée à une précédente : un seul tracé */
    #[TestWith([0])]
    #[TestWith([1])]
    #[TestWith([6])]
    #[TestWith([20])]
    public function testLesEtoilesTiennentDansLeCadreEtSontToutesReliees(int $nombre): void
    {
        foreach ([1, 2, 7] as $graine) {
            ['etoiles' => $etoiles, 'liens' => $liens] = (new Constellation())->constellation($nombre, $graine);

            self::assertCount($nombre, $etoiles);
            foreach ($etoiles as [$x, $y]) {
                self::assertGreaterThanOrEqual(8, $x);
                self::assertLessThanOrEqual(Constellation::LARGEUR - 8, $x);
                self::assertGreaterThanOrEqual(8, $y);
                self::assertLessThanOrEqual(Constellation::HAUTEUR - 8, $y);
            }
            self::assertCount(max($nombre - 1, 0), $liens);
            foreach ($liens as [$a, $b]) {
                self::assertLessThan($b, $a);
            }
        }
    }
}
