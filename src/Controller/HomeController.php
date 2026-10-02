<?php

namespace App\Controller;

use App\Data\Competences;
use App\Data\Parcours;
use App\Data\Passions;
use App\Data\Projets;
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
    public function cerveau(): Response
    {
        // Un neurone par compétence, avec les projets qui l'utilisent
        $neurones = [];
        foreach (Competences::CATEGORIES as $categorie => $infos) {
            foreach ($infos['competences'] as $competence) {
                $projets = [];
                foreach (Projets::CATEGORIES as $liste) {
                    foreach ($liste as $projet) {
                        if (\in_array($competence, $projet['competences'], true)) {
                            $projets[] = [
                                'titre' => $projet['titre'],
                                'url' => $this->generateUrl('app_projects').'#'.$projet['slug'],
                            ];
                        }
                    }
                }
                $neurones[] = [
                    'nom' => $competence,
                    'categorie' => $categorie,
                    'zone' => $infos['zone'],
                    'couleur' => $infos['couleur'],
                    'projets' => $projets,
                ];
            }
        }

        return $this->render('home/cerveau.html.twig', [
            'categories' => Competences::CATEGORIES,
            'neurones' => $neurones,
            'passions' => Passions::LISTE,
            'parcours' => Parcours::LISTE,
        ]);
    }
}
