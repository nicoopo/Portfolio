<?php

namespace App\Tests;

use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Le reste (slugs uniques, projet → compétence existante) est garanti par la base
 * (index uniques, clés étrangères).
 */
final class DataTest extends KernelTestCase
{
    public function testLesNomsDuCerveauSontUniques(): void
    {
        // La légende du cerveau sélectionne neurones, nébuleuses et souvenirs par leur nom,
        // à travers trois tables : un index unique ne suffit pas
        $noms = self::getContainer()->get(Connection::class)->fetchFirstColumn(
            'SELECT nom FROM competence UNION ALL SELECT nom FROM passion UNION ALL SELECT nom FROM etape_parcours',
        );

        self::assertNotEmpty($noms);
        self::assertSame($noms, array_values(array_unique($noms)));
    }
}
