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
     * trois tables (un index unique ne suffit pas), dans chaque langue : en anglais, le nom
     * affiché est nom_en, ou nom s'il est vide.
     */
    public function testLesNomsDuCerveauSontUniquesDansChaqueLangue(): void
    {
        foreach (['nom', 'COALESCE(nom_en, nom)'] as $nom) {
            $noms = self::getContainer()->get(Connection::class)->fetchFirstColumn(
                "SELECT $nom FROM competence UNION ALL SELECT $nom FROM passion UNION ALL SELECT $nom FROM etape_parcours",
            );

            self::assertNotEmpty($noms);
            self::assertSame($noms, array_values(array_unique($noms)), $nom);
        }
    }
}
