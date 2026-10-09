<?php

namespace App\Controller;

use App\Entity\Competence;
use App\Repository\ArticleRepository;
use App\Repository\ProjetRepository;
use App\Twig\Traduction;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Ce que la palette de commandes (Ctrl+K, assets/scripts/palette.js) sait chercher, dans la langue de l'adresse :
 * pages, compétences (neurone du cerveau), projets, articles publiés. Chargé à la première ouverture.
 */
final class RechercheController extends AbstractController
{
    /** Route => libellé (clé de traduction déjà utilisée par le menu ou les pages) */
    private const PAGES = [
        'app_home' => 'Accueil', 'app_cerveau' => 'Mon Cerveau', 'app_projects' => 'Projets', 'app_projects_frise' => 'Mes projets dans le temps', 'app_articles' => 'Articles',
        'app_univers' => 'Mon Univers', 'app_competences' => 'Compétences', 'app_comparateur' => 'Comparer avec votre offre', 'app_cv' => 'Mon CV', 'app_contact' => 'Contact', 'app_now' => 'En ce moment', 'app_livre_or' => 'Livre d’or', 'app_terminal' => 'Terminal',
        'app_mentions_legales' => 'Mentions légales', 'app_confidentialite' => 'Confidentialité',
    ];

    #[Route('/recherche.json', name: 'app_recherche')]
    public function __invoke(TranslatorInterface $translator, Traduction $traduction, EntityManagerInterface $entityManager, ProjetRepository $projets, ArticleRepository $articles): JsonResponse
    {
        $entrees = [];
        foreach (self::PAGES as $route => $libelle) {
            $entrees[] = ['type' => 'page', 'titre' => $translator->trans($libelle), 'url' => $this->generateUrl($route)];
        }
        foreach ($entityManager->getRepository(Competence::class)->findBy([], ['position' => 'ASC']) as $competence) {
            $nom = $traduction->loc($competence, 'nom');
            $entrees[] = ['type' => 'competence', 'titre' => $nom, 'detail' => $traduction->loc($competence->getCategorie(), 'nom'),
                'url' => $this->generateUrl('app_cerveau').'#'.rawurlencode($nom)];
        }
        foreach ($projets->findBy([], ['position' => 'ASC']) as $projet) {
            $entrees[] = ['type' => 'projet', 'titre' => $traduction->loc($projet, 'titre'), 'detail' => $traduction->loc($projet, 'categorie'),
                'url' => $this->generateUrl('app_project', ['slug' => $projet->getSlug()])];
        }
        foreach ($articles->findPublies() as $article) {
            $entrees[] = ['type' => 'article', 'titre' => $traduction->loc($article, 'titre'), 'detail' => $traduction->loc($article, 'resume'),
                'url' => $this->generateUrl('app_article', ['slug' => $article->getSlug()])];
        }

        return $this->json($entrees);
    }
}
