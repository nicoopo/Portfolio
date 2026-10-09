<?php

namespace App\Controller;

use App\Entity\CvProfil;
use App\Repository\CategorieCompetenceRepository;
use App\Repository\ProjetRepository;
use App\Twig\Traduction;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/** /terminal : faux terminal (terminal_controller.js) ; ce qu'il affiche vient de la base, dans la langue de la page */
final class TerminalController extends AbstractController
{
    #[Route('/terminal', name: 'app_terminal')]
    public function __invoke(EntityManagerInterface $entityManager, ProjetRepository $projets, CategorieCompetenceRepository $categories, Traduction $traduction): Response
    {
        $profil = $entityManager->getRepository(CvProfil::class)->findOneBy([]);

        return $this->render('terminal/index.html.twig', ['donnees' => [
            'titre' => $profil ? $traduction->loc($profil, 'titre') : '',
            'qualites' => $profil ? $traduction->loc($profil, 'qualites') : '',
            'resume' => $profil ? $traduction->loc($profil, 'resume') : '',
            'contact' => [
                'email' => $this->getParameter('app.contact_email'),
                'github' => 'https://github.com/nicoopo',
                'linkedin' => 'https://www.linkedin.com/in/nicolas-cataluna-737182224/',
            ],
            'projets' => array_map(fn ($projet) => [
                'slug' => $projet->getSlug(),
                'titre' => $traduction->loc($projet, 'titre'),
                'description' => $traduction->loc($projet, 'description'),
                'tech' => $projet->getTech(),
                'url' => $this->generateUrl('app_project', ['slug' => $projet->getSlug()]),
            ], $projets->findBy([], ['position' => 'ASC'])),
            'competences' => array_merge(...array_map(fn ($categorie) => [
                $traduction->loc($categorie, 'nom') => array_map(fn ($c) => $traduction->loc($c, 'nom'), $categorie->getCompetences()->toArray()),
            ], $categories->findAllWithCompetences())),
            'pages' => [
                'cv' => $this->generateUrl('app_cv'),
                'contact' => $this->generateUrl('app_contact'),
                'cerveau' => $this->generateUrl('app_cerveau'),
                'accueil' => $this->generateUrl('app_home'),
            ],
        ]]);
    }
}
