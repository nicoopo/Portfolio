<?php

namespace App\Controller;

use App\Repository\CategorieCompetenceRepository;
use App\Twig\Traduction;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class CompetencesController extends AbstractController
{
    /** Autres façons d'écrire une compétence dans une offre (en minuscules, sans accents) ; ajoutées au nom français et traduit */
    private const SYNONYMES = [
        'HTML5' => ['html'],
        'CSS3' => ['css'],
        'SCSS' => ['sass'],
        'JavaScript' => ['js', 'ecmascript'],
        'TypeScript' => ['ts'],
        'Vue.js' => ['vue', 'vuejs'],
        'React' => ['reactjs', 'react.js'],
        'PostgreSQL' => ['postgres'],
        'API REST' => ['rest', 'api', 'restful'],
        'Go' => ['golang'],
        'Bash' => ['shell', 'script shell'],
        'Virtualisation' => ['virtualization', 'vmware', 'hyper-v'],
        'GitHub Actions' => ['ci/cd', 'integration continue', 'continuous integration'],
        'Travail en équipe' => ['equipe', 'team', 'teamwork', 'collaboratif'],
        'Autonomie' => ['autonome', 'autonomous', 'independent'],
        'Rigueur' => ['rigoureux', 'rigoureuse', 'rigorous'],
        'Curiosité' => ['curieux', 'curieuse', 'curious'],
    ];

    #[Route('/competences', name: 'app_competences')]
    public function index(CategorieCompetenceRepository $categories, Traduction $traduction): Response
    {
        $categories = $categories->findAllWithCompetences();

        // Système solaire en 3D (systeme_controller.js) : une orbite par catégorie, une planète par compétence,
        // une lune par projet qui l'utilise
        $systeme = array_map(fn ($categorie) => [
            'nom' => $traduction->loc($categorie, 'nom'),
            'couleur' => $categorie->getCouleur(),
            'competences' => array_map(fn ($competence) => [
                'id' => $competence->getId(),
                'nom' => $traduction->loc($competence, 'nom'),
                'cerveau' => $this->generateUrl('app_cerveau').'#'.rawurlencode($traduction->loc($competence, 'nom')),
                'projets' => array_map(fn ($projet) => [
                    'titre' => $traduction->loc($projet, 'titre'),
                    'url' => $this->generateUrl('app_project', ['slug' => $projet->getSlug()]),
                ], $competence->getProjets()->toArray()),
            ], $categorie->getCompetences()->toArray()),
        ], $categories);

        return $this->render('competences/index.html.twig', [
            'categories' => $categories,
            'systeme' => $systeme,
        ]);
    }

    /** Comparateur : le recruteur colle son offre, comparateur_controller.js y cherche ces mots-clés (rien n'est envoyé) */
    #[Route('/competences/comparer', name: 'app_comparateur')]
    public function comparer(CategorieCompetenceRepository $categories, Traduction $traduction): Response
    {
        $competences = [];
        foreach ($categories->findAllWithCompetences() as $categorie) {
            foreach ($categorie->getCompetences() as $competence) {
                $nom = $traduction->loc($competence, 'nom');
                $morceaux = array_merge(explode('/', $competence->getNom()), explode('/', $nom));
                $mots = [];
                foreach ($morceaux as $morceau) {
                    $morceau = trim(preg_replace('/[^\p{L}\p{N} .#+\-]/u', '', $morceau));
                    if ('' !== $morceau) {
                        $mots[] = $morceau;
                        array_push($mots, ...(self::SYNONYMES[$morceau] ?? []));
                    }
                }
                $competences[] = [
                    'nom' => $nom,
                    'categorie' => $traduction->loc($categorie, 'nom'),
                    'mots' => array_values(array_unique($mots)),
                    'projets' => array_map(fn ($projet) => [
                        'titre' => $traduction->loc($projet, 'titre'),
                        'url' => $this->generateUrl('app_project', ['slug' => $projet->getSlug()]),
                    ], $competence->getProjets()->toArray()),
                ];
            }
        }

        return $this->render('competences/comparer.html.twig', ['competences' => $competences]);
    }
}
