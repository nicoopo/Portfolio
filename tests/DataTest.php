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
    /**
     * La légende du cerveau sélectionne neurones, nébuleuses et souvenirs par leur nom, à travers
     * trois tables (un index unique ne suffit pas), dans chaque langue : le nom traduit
     * (colonne traductions), ou le français s'il n'y en a pas.
     */
    public function testLesNomsDuCerveauSontUniquesDansChaqueLangue(): void
    {
        foreach (self::getContainer()->getParameter('kernel.enabled_locales') as $langue) {
            $nom = "COALESCE(traductions::jsonb -> '$langue' ->> 'nom', nom)";
            $noms = self::getContainer()->get(Connection::class)->fetchFirstColumn(
                "SELECT $nom FROM competence UNION ALL SELECT $nom FROM passion UNION ALL SELECT $nom FROM etape_parcours",
            );

            self::assertNotEmpty($noms);
            self::assertSame($noms, array_values(array_unique($noms)), $nom);
        }
    }
}
