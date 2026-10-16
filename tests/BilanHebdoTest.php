<?php

namespace App\Tests;

use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

final class BilanHebdoTest extends KernelTestCase
{
    /** Visites de la semaine, provenances (hors navigation interne) et liens recruteur ouverts apparaissent dans le bilan */
    public function testLeBilanResumeLaSemaine(): void
    {
        $connection = self::getContainer()->get(Connection::class);
        $connection->executeStatement("INSERT INTO visite_jour (jour, chemin, langue, source, nombre) VALUES
            (CURRENT_DATE, '/bilan-test', 'fr', 'bilan-test.example', 500),
            (CURRENT_DATE - 2, '/bilan-test', 'fr', 'site', 400)");
        $connection->executeStatement("INSERT INTO lien_recruteur (code, entreprise, visites, derniere_visite) VALUES
            ('bilantest01', 'Bilan Test SA', 1, NOW()), ('bilantest02', 'Vieux Lien SA', 1, NOW() - INTERVAL '20 days')");

        try {
            $tester = new CommandTester((new Application(self::$kernel))->find('app:bilan:hebdo'));
            $tester->execute([]);
            $sortie = $tester->getDisplay();

            self::assertStringContainsString('/bilan-test 900', $sortie);
            self::assertStringContainsString('bilan-test.example 500', $sortie);
            self::assertStringNotContainsString('site 400', $sortie);
            self::assertStringContainsString('Bilan Test SA', $sortie);
            self::assertStringNotContainsString('Vieux Lien SA', $sortie);
        } finally {
            $connection->executeStatement("DELETE FROM visite_jour WHERE chemin = '/bilan-test'");
            $connection->executeStatement("DELETE FROM lien_recruteur WHERE code IN ('bilantest01', 'bilantest02')");
        }
    }
}
