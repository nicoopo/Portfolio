<?php

namespace App\Controller;

use App\Entity\Outils;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class OutilsController extends AbstractController
{
    /** Page « uses » (uses.tech) : mon matériel, mes logiciels, ma stack */
    #[Route('/uses', name: 'app_uses')]
    public function __invoke(EntityManagerInterface $entityManager): Response
    {
        return $this->render('uses/index.html.twig', [
            'outils' => $entityManager->getRepository(Outils::class)->findOneBy([]) ?? throw $this->createNotFoundException(),
        ]);
    }
}
