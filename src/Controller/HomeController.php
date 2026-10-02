<?php

namespace App\Controller;

use App\Entity\EtapeParcours;
use App\Entity\Passion;
use App\Repository\CategorieCompetenceRepository;
use App\Repository\EtapeParcoursRepository;
use App\Repository\PassionRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(): Response
    {
        return $this->render('home/index.html.twig');
    }

    #[Route('/cerveau', name: 'app_cerveau')]
    public function cerveau(
        CategorieCompetenceRepository $categoriesRepository,
        PassionRepository $passionsRepository,
        EtapeParcoursRepository $parcoursRepository,
    ): Response {
        $categories = $categoriesRepository->findAllWithCompetences();
        $passions = $passionsRepository->findBy([], ['position' => 'ASC']);
        $parcours = $parcoursRepository->findBy([], ['position' => 'ASC']);

        // Un neurone par compétence, avec les projets qui l'utilisent
        $neurones = [];
        foreach ($categories as $categorie) {
            foreach ($categorie->getCompetences() as $competence) {
                $projets = [];
                foreach ($competence->getProjets() as $projet) {
                    $projets[] = [
                        'titre' => $projet->getTitre(),
                        'url' => $this->generateUrl('app_projects').'#'.$projet->getSlug(),
                    ];
                }
                $neurones[] = [
                    'nom' => $competence->getNom(),
                    'categorie' => $categorie->getNom(),
                    'zone' => $categorie->getZone(),
                    'couleur' => $categorie->getCouleur(),
                    'projets' => $projets,
                ];
            }
        }

        return $this->render('home/cerveau.html.twig', [
            'categories' => $categories,
            'neurones' => $neurones,
            'passions' => $passions,
            'parcours' => $parcours,
            'passions_3d' => array_map(fn (Passion $passion) => $passion->toArray(), $passions),
            'parcours_3d' => array_map(fn (EtapeParcours $etape) => $etape->toArray(), $parcours),
        ]);
    }
}
