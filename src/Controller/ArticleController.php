<?php

namespace App\Controller;

use App\Repository\ArticleRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/** Blog : seuls les articles publiés sont visibles (brouillon ou publication programmée : 404) */
final class ArticleController extends AbstractController
{
    #[Route('/articles', name: 'app_articles')]
    public function index(ArticleRepository $articles): Response
    {
        return $this->render('article/index.html.twig', ['articles' => $articles->findPublies()]);
    }

    /** Flux RSS, un par langue (/articles/rss.xml, /en/articles/rss.xml…) */
    #[Route('/articles/rss.xml', name: 'app_articles_rss')]
    public function rss(ArticleRepository $articles): Response
    {
        $response = $this->render('article/rss.xml.twig', ['articles' => $articles->findPublies()]);
        $response->headers->set('Content-Type', 'application/rss+xml; charset=UTF-8');

        return $response;
    }

    #[Route('/articles/{slug}', name: 'app_article', requirements: ['slug' => '[a-z0-9-]+'])]
    public function show(string $slug, ArticleRepository $articles): Response
    {
        return $this->render('article/show.html.twig', [
            'article' => $articles->findPublie($slug) ?? throw $this->createNotFoundException(),
        ]);
    }
}
