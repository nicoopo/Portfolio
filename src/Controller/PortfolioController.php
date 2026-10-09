<?php

namespace App\Controller;

use App\Entity\Projet;
use App\Repository\ProjetRepository;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class PortfolioController extends AbstractController
{
    #[Route('/projects', name: 'app_projects')]
    public function index(ProjetRepository $projets): Response
    {
        return $this->render('portfolio/index.html.twig', [
            'categories' => $projets->findAllByCategorie(),
        ]);
    }

    /**
     * Frise : projets datés, du plus ancien au plus récent, groupés par année ; pour chaque projet, les technos
     * qui apparaissent pour la première fois (ce qui a été appris, et quand). Avant /projects/{slug} (priorité).
     */
    #[Route('/projects/frise', name: 'app_projects_frise', priority: 1)]
    public function frise(ProjetRepository $projets): Response
    {
        $annees = [];
        $nouvelles = [];
        $vues = [];
        foreach ($projets->findDates() as $projet) {
            $annees[$projet->getAnnee()][] = $projet;
            $techs = array_map('trim', explode(',', $projet->getTech()));
            $nouvelles[$projet->getId()] = array_values(array_diff($techs, $vues));
            $vues = array_merge($vues, $techs);
        }

        return $this->render('portfolio/frise.html.twig', [
            'annees' => $annees,
            'nouvelles' => $nouvelles,
            'techs' => array_values(array_unique($vues)),
        ]);
    }

    /** Page d'un projet : texte détaillé, liens, compétences, et projets voisins (ordre de la page Projets) */
    #[Route('/projects/{slug}', name: 'app_project', requirements: ['slug' => '[a-z0-9-]+'])]
    public function show(#[MapEntity(mapping: ['slug' => 'slug'])] Projet $projet, ProjetRepository $projets): Response
    {
        $tous = $projets->findBy([], ['position' => 'ASC']);
        $rang = array_search($projet, $tous, true);

        return $this->render('portfolio/show.html.twig', [
            'projet' => $projet,
            'precedent' => $tous[$rang - 1] ?? null,
            'suivant' => $tous[$rang + 1] ?? null,
        ]);
    }
}
