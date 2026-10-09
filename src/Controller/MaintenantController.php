<?php

namespace App\Controller;

use App\Entity\Maintenant;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class MaintenantController extends AbstractController
{
    /** Page « now » (nownownow.com) : ce que je fais en ce moment */
    #[Route('/now', name: 'app_now')]
    public function __invoke(EntityManagerInterface $entityManager): Response
    {
        return $this->render('now/index.html.twig', [
            'maintenant' => $entityManager->getRepository(Maintenant::class)->findOneBy([]) ?? throw $this->createNotFoundException(),
        ]);
    }
}
