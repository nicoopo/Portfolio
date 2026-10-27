<?php

namespace App\Controller;

use Sentry\Dsn;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\RateLimiter\RateLimiterFactoryInterface;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Erreurs JavaScript (SDK Sentry du navigateur, assets/scripts/erreurs.js) transmises à GlitchTip.
 * GlitchTip n'est joignable que depuis le serveur : le navigateur envoie ici, sur le site (option « tunnel » du SDK),
 * et on relaie vers le projet de SENTRY_DSN. Le DSN annoncé par le navigateur est ignoré : ce n'est pas un relais ouvert.
 * Route déclarée dans config/routes.yaml (hors du préfixe de langue).
 */
#[AsController]
final class MonitoringController
{
    private const TAILLE_MAX = 200_000; // octets ; un événement avec sa pile d'appels en fait quelques dizaines de milliers

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly RateLimiterFactoryInterface $erreursJsLimiter,
        #[Autowire(env: 'SENTRY_DSN')] private readonly string $dsn,
    ) {
    }

    public function __invoke(Request $request): Response
    {
        if ('' === $this->dsn) {
            return new Response(null, Response::HTTP_NO_CONTENT); // dev, tests : pas de GlitchTip
        }
        if ('same-origin' !== $request->headers->get('Sec-Fetch-Site', 'same-origin')) {
            return new Response(null, Response::HTTP_FORBIDDEN);
        }
        $enveloppe = $request->getContent();
        if ('' === $enveloppe || \strlen($enveloppe) > self::TAILLE_MAX) {
            return new Response(null, Response::HTTP_BAD_REQUEST);
        }
        if (!$this->erreursJsLimiter->create('tous')->consume()->isAccepted()) {
            return new Response(null, Response::HTTP_TOO_MANY_REQUESTS);
        }

        $dsn = Dsn::createFromString($this->dsn);
        try {
            $this->httpClient->request('POST', $dsn->getEnvelopeApiEndpointUrl(), [
                'headers' => [
                    'Content-Type' => 'application/x-sentry-envelope',
                    'X-Sentry-Auth' => 'Sentry sentry_version=7, sentry_key='.$dsn->getPublicKey(),
                ],
                'body' => $enveloppe,
                'timeout' => 5,
            ])->getStatusCode();
        } catch (ExceptionInterface) {
            // GlitchTip arrêté : l'erreur JS est perdue, le visiteur n'en sait rien
        }

        return new Response(null, Response::HTTP_NO_CONTENT);
    }
}
