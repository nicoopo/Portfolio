<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Mentions légales (LCEN) et politique de confidentialité (RGPD).
 * Texte juridique long : un template par langue (legal/<page>.<langue>.html.twig) plutôt que des clés de traduction.
 */
final class LegalController extends AbstractController
{
    public function __construct(
        #[Autowire('%app.contact_email%')] private readonly string $contactEmail,
    ) {
    }

    #[Route('/mentions-legales', name: 'app_mentions_legales')]
    public function mentions(Request $request): Response
    {
        return $this->page('mentions', $request);
    }

    #[Route('/confidentialite', name: 'app_confidentialite')]
    public function confidentialite(Request $request): Response
    {
        return $this->page('confidentialite', $request);
    }

    private function page(string $page, Request $request): Response
    {
        return $this->render(\sprintf('legal/%s.%s.html.twig', $page, $request->getLocale()), [
            'contact_email' => $this->contactEmail,
        ]);
    }
}
