<?php

namespace App\Controller;

use App\Entity\Projet;
use App\Repository\ArticleRepository;
use App\Service\ImagePartage;
use App\Twig\Traduction;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/** Images d'aperçu (og:image) des projets et des articles, dans la langue de l'adresse ; mises en cache un jour par les réseaux */
final class PartageController extends AbstractController
{
    public function __construct(private readonly ImagePartage $image, private readonly Traduction $traduction)
    {
    }

    #[Route('/partage/projet/{slug}.png', name: 'app_partage_projet', requirements: ['slug' => '[a-z0-9-]+'])]
    public function projet(#[MapEntity(mapping: ['slug' => 'slug'])] Projet $projet): Response
    {
        return $this->png(
            $this->traduction->loc($projet, 'categorie'),
            $this->traduction->loc($projet, 'titre'),
            array_map('trim', explode(',', $projet->getTech())),
        );
    }

    #[Route('/partage/article/{slug}.png', name: 'app_partage_article', requirements: ['slug' => '[a-z0-9-]+'])]
    public function article(string $slug, ArticleRepository $articles): Response
    {
        $article = $articles->findPublie($slug) ?? throw $this->createNotFoundException();

        return $this->png('Articles', $this->traduction->loc($article, 'titre'), []);
    }

    /** @param list<string> $technos */
    private function png(string $surtitre, string $titre, array $technos): Response
    {
        $response = new Response($this->image->png($surtitre, $titre, $technos), 200, ['Content-Type' => 'image/png']);
        $response->setPublic();
        $response->setMaxAge(86400);

        return $response;
    }
}
