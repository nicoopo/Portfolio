<?php

namespace App\Controller;

use App\Repository\OutilsRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class OutilsController extends AbstractController
{
    /** Page « uses » (uses.tech) : mon matériel, mes logiciels, ma stack */
    #[Route('/uses', name: 'app_uses')]
    public function __invoke(OutilsRepository $outils): Response
    {
        return $this->render('uses/index.html.twig', [
            'outils' => $outils->findOneBy([]) ?? throw $this->createNotFoundException(),
        ]);
    }
}
