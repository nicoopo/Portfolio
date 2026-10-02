<?php

namespace App\Controller;

use App\Entity\EtapeParcours;
use App\Entity\Passion;
use App\Repository\CategorieCompetenceRepository;
use App\Repository\EtapeParcoursRepository;
use App\Repository\PassionRepository;
use App\Twig\Traduction;
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
        Traduction $traduction,
    ): Response {
        $categories = $categoriesRepository->findAllWithCompetences();
        $passions = $passionsRepository->findBy([], ['position' => 'ASC']);
        $parcours = $parcoursRepository->findBy([], ['position' => 'ASC']);

        // Un neurone par compétence, avec les projets qui l'utilisent (textes dans la langue de la page)
        $neurones = [];
        foreach ($categories as $categorie) {
            foreach ($categorie->getCompetences() as $competence) {
                $projets = [];
                foreach ($competence->getProjets() as $projet) {
                    $projets[] = [
                        'titre' => $traduction->loc($projet, 'titre'),
                        'url' => $this->generateUrl('app_projects').'#'.$projet->getSlug(),
                    ];
                }
                $neurones[] = [
                    'nom' => $traduction->loc($competence, 'nom'),
                    'categorie' => $traduction->loc($categorie, 'nom'),
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
            'passions_3d' => array_map(fn (Passion $passion) => [
                'nom' => $traduction->loc($passion, 'nom'),
                'couleur' => $passion->getCouleur(),
                'description' => $traduction->loc($passion, 'description'),
            ], $passions),
            'parcours_3d' => array_map(fn (EtapeParcours $etape) => [
                'nom' => $traduction->loc($etape, 'nom'),
                'dates' => $etape->getDates(),
                'intitule' => $traduction->loc($etape, 'intitule'),
                'option' => $traduction->loc($etape, 'specialite'),
                'ecole' => $etape->getEcole(),
                'lieu' => $etape->getLieu(),
                'resultat' => $traduction->loc($etape, 'resultat'),
            ], $parcours),
        ]);
    }
}
