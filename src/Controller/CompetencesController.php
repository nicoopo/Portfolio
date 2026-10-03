<?php

namespace App\Controller;

use App\Repository\CategorieCompetenceRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class CompetencesController extends AbstractController
{
    #[Route('/competences', name: 'app_competences')]
    public function index(CategorieCompetenceRepository $categories): Response
    {
        return $this->render('competences/index.html.twig', [
            'categories' => $categories->findAllWithCompetences(),
        ]);
    }
}
