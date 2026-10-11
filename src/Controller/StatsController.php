<?php

namespace App\Controller;

use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Statistiques publiques, tirées de visite_jour (App\EventListener\StatistiquesListener) : des totaux, sans donnée personnelle.
 * Les provenances ne sont pas montrées : elles pourraient trahir le site d'une entreprise.
 */
final class StatsController extends AbstractController
{
    #[Route('/stats', name: 'app_stats')]
    public function __invoke(Connection $connection): Response
    {
        $jours = [];
        for ($i = 29; $i >= 0; --$i) {
            $jours[(new \DateTimeImmutable("-$i days"))->format('Y-m-d')] = 0;
        }
        foreach ($connection->fetchAllKeyValue("SELECT to_char(jour, 'YYYY-MM-DD'), SUM(nombre) FROM visite_jour WHERE jour >= CURRENT_DATE - 29 GROUP BY jour") as $jour => $nombre) {
            $jours[$jour] = (int) $nombre;
        }

        return $this->render('stats/index.html.twig', [
            'jours' => $jours,
            'total' => (int) $connection->fetchOne('SELECT COALESCE(SUM(nombre), 0) FROM visite_jour'),
            'depuis' => $connection->fetchOne('SELECT MIN(jour) FROM visite_jour'),
            'pages' => array_map('intval', $connection->fetchAllKeyValue('SELECT chemin, SUM(nombre) AS total FROM visite_jour WHERE jour >= CURRENT_DATE - 29 GROUP BY chemin ORDER BY total DESC, chemin LIMIT 5')),
            'langues' => array_map('intval', $connection->fetchAllKeyValue('SELECT langue, SUM(nombre) AS total FROM visite_jour WHERE jour >= CURRENT_DATE - 29 GROUP BY langue ORDER BY total DESC, langue')),
        ]);
    }
}
