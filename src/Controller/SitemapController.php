<?php

namespace App\Controller;

use App\Repository\ArticleRepository;
use App\Repository\ProjetRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

/**
 * /sitemap.xml : les pages publiques, chacune en français et en anglais (hreflang).
 * Route déclarée dans config/routes.yaml : hors du préfixe de langue (pas de /en/sitemap.xml).
 */
final class SitemapController extends AbstractController
{
    private const PAGES = ['app_home', 'app_cerveau', 'app_competences', 'app_projects', 'app_articles', 'app_univers', 'app_cv', 'app_contact', 'app_now', 'app_mentions_legales', 'app_confidentialite'];

    public function __invoke(ProjetRepository $projets, ArticleRepository $articles): Response
    {
        // [route, paramètres] : les pages fixes, puis une page par projet et par article publié
        $pages = array_map(fn (string $route) => [$route, []], self::PAGES);
        foreach ($projets->findBy([], ['position' => 'ASC']) as $projet) {
            $pages[] = ['app_project', ['slug' => $projet->getSlug()]];
        }
        foreach ($articles->findPublies() as $article) {
            $pages[] = ['app_article', ['slug' => $article->getSlug()]];
        }

        $response = $this->render('sitemap.xml.twig', ['pages' => $pages]); // langues : global Twig langues_site
        $response->headers->set('Content-Type', 'application/xml; charset=UTF-8');

        return $response;
    }
}
