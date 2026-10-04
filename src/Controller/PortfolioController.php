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
