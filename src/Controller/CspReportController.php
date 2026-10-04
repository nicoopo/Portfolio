<?php

namespace App\Controller;

use Monolog\Attribute\WithMonologChannel;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;

/**
 * Le navigateur signale ici ce que la Content-Security-Policy a bloqué (ou aurait bloqué en Report-Only).
 * Route déclarée dans config/routes.yaml (hors du préfixe de langue). Lecture des rapports en prod :
 *   docker logs portfolio_php_prod 2>&1 | grep '"channel":"csp"'
 */
#[AsController]
#[WithMonologChannel('csp')]
final class CspReportController
{
    public function __construct(private readonly LoggerInterface $logger)
    {
    }

    public function __invoke(Request $request): Response
    {
        // Envoyé par n'importe quel navigateur : on ne garde que les champs utiles, tronqués
        $rapport = json_decode(substr($request->getContent(), 0, 10_000), true)['csp-report'] ?? null;
        if (\is_array($rapport)) {
            $this->logger->warning('Violation de la Content-Security-Policy', array_map(
                static fn ($valeur) => mb_substr((string) $valeur, 0, 300),
                array_intersect_key($rapport, array_flip(['document-uri', 'violated-directive', 'effective-directive', 'blocked-uri', 'source-file', 'line-number'])),
            ));
        }

        return new Response(null, Response::HTTP_NO_CONTENT);
    }
}
