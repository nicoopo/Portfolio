<?php

namespace App\Twig;

use App\Repository\ArticleRepository;
use App\Repository\ProjetRepository;
use Doctrine\DBAL\Exception;
use Twig\Attribute\AsTwigFunction;

/**
 * Pages qui n'ont encore rien à montrer (aucun article publié, aucun projet daté) : leurs liens sont masqués
 * (menu, page Projets, palette de recherche, sitemap) jusqu'au premier contenu. La page reste accessible.
 */
final class PagesVides
{
    public function __construct(
        private readonly ArticleRepository $articles,
        private readonly ProjetRepository $projets,
    ) {
    }

    #[AsTwigFunction('page_vide')]
    public function estVide(string $route): bool
    {
        try {
            return match ($route) {
                'app_articles' => [] === $this->articles->findPublies(),
                'app_projects_frise' => [] === $this->projets->findDates(),
                default => false,
            };
        } catch (Exception) {
            return false; // sans base (page d'erreur) : le menu garde tous ses liens
        }
    }
}
