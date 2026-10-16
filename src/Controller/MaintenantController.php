<?php

namespace App\Controller;

use App\Repository\MaintenantRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class MaintenantController extends AbstractController
{
    /** Page « now » (nownownow.com) : ce que je fais en ce moment */
    #[Route('/now', name: 'app_now')]
    public function __invoke(MaintenantRepository $maintenant): Response
    {
        return $this->render('now/index.html.twig', [
            'maintenant' => $maintenant->findOneBy([]) ?? throw $this->createNotFoundException(),
        ]);
    }
}
