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

    #[Route('/articles/{slug}', name: 'app_article', requirements: ['slug' => '[a-z0-9-]+'])]
    public function show(string $slug, ArticleRepository $articles): Response
    {
        return $this->render('article/show.html.twig', [
            'article' => $articles->findPublie($slug) ?? throw $this->createNotFoundException(),
        ]);
    }
}
