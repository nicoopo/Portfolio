<?php

namespace App\Controller;

use App\Entity\Decouverte;
use App\Repository\DecouverteRepository;
use App\Security\BlocIp;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\RateLimiter\RateLimiterFactoryInterface;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Un visiteur vient de trouver un easter egg (decouvertes.js, une seule fois par navigateur) : +1 au compteur.
 * Seulement depuis le site (Sec-Fetch-Site), et limité par adresse IP (config/packages/rate_limiter.yaml).
 */
final class DecouverteController extends AbstractController
{
    #[Route('/decouvertes', name: 'app_decouverte', methods: ['POST'])]
    public function __invoke(Request $request, DecouverteRepository $decouvertes,RateLimiterFactoryInterface $decouvertesLimiter): Response
    {
        if ('same-origin' !== $request->headers->get('Sec-Fetch-Site', 'same-origin')) {
            return new Response(null, Response::HTTP_FORBIDDEN);
        }
        $id = $request->getPayload()->getString('id');
        if (!\in_array($id, Decouverte::IDS, true)) {
            return new Response(null, Response::HTTP_BAD_REQUEST);
        }
        if (!$decouvertesLimiter->create(BlocIp::de($request))->consume()->isAccepted()) {
            return new Response(null, Response::HTTP_TOO_MANY_REQUESTS);
        }

        $decouvertes->compter($id);

        return new Response(null, Response::HTTP_NO_CONTENT);
    }
}
