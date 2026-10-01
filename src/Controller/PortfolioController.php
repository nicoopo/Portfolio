<?php

namespace App\Controller;

use App\Data\Projets;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class PortfolioController extends AbstractController
{
    #[Route('/projects', name: 'app_projects')]
    public function index(): Response
    {
        return $this->render('portfolio/index.html.twig', [
            'categories' => Projets::CATEGORIES,
        ]);
    }
}
