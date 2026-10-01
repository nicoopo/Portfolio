<?php

namespace App\Controller;

use App\Data\Competences;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class CompetencesController extends AbstractController
{
    #[Route('/competences', name: 'app_competences')]
    public function index(): Response
    {
        return $this->render('competences/index.html.twig', [
            'categories' => Competences::CATEGORIES,
        ]);
    }
}
