<?php

namespace App\Controller;

use App\Entity\EtapeParcours;
use App\Entity\LienRecruteur;
use App\Entity\Passion;
use App\Repository\CategorieCompetenceRepository;
use App\Service\Notificateur;
use App\Repository\EtapeParcoursRepository;
use App\Repository\PassionRepository;
use App\Twig\Traduction;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    /** Lien recruteur (/?pour=code) : accueil personnalisé, visite comptée sauf pour l'administrateur connecté, notification à la première */
    #[Route('/', name: 'app_home')]
    public function index(Request $request, EntityManagerInterface $entityManager, Notificateur $notificateur): Response
    {
        $code = $request->query->getString('pour');
        $recruteur = '' !== $code ? $entityManager->getRepository(LienRecruteur::class)->findOneBy(['code' => $code]) : null;
        if ($recruteur && !$this->isGranted('ROLE_ADMIN')) {
            $recruteur->visiter();
            $entityManager->flush();
            if (1 === $recruteur->getVisites()) { // la première ouverture seulement : de quoi relancer au bon moment
                $notificateur->prevenir('Lien recruteur ouvert', $recruteur->getEntreprise().' vient d’ouvrir ton portfolio'.($recruteur->getPoste() ? ' ('.$recruteur->getPoste().')' : ''), 'eyes');
            }
        }

        return $this->render('home/index.html.twig', ['recruteur' => $recruteur]);
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
                        'url' => $this->generateUrl('app_project', ['slug' => $projet->getSlug()]),
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
                'url' => $this->generateUrl('app_univers').'#etape-'.$etape->getId(),
            ], $parcours),
        ]);
    }
}
