<?php

namespace App\Controller;

use App\Repository\ArriveeRepository;
use App\Repository\VisiteJourRepository;
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
    public function __invoke(VisiteJourRepository $visites, ArriveeRepository $arrivees): Response
    {
        return $this->render('stats/index.html.twig', [
            'jours' => $visites->vuesParJour(),
            'total' => $visites->total(),
            'depuis' => $visites->depuis(),
            'pages' => $visites->classement('chemin', 30, 5),
            'langues' => $visites->classement('langue', 30),
            // Globe des arrivées (ArriveeController) : les fuseaux horaires d'où l'on arrive le plus, en clair sous le globe
            'fuseaux' => $arrivees->fuseaux(),
        ]);
    }
}
