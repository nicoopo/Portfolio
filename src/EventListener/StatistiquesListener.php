<?php

namespace App\EventListener;

use Doctrine\DBAL\Connection;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Compte les pages vues du site public (App\Entity\VisiteJour) : pages HTML réussies, ni robots, ni administrateur.
 * Sans cookie ni IP : seulement page, langue et site de provenance, additionnés par jour.
 */
final class StatistiquesListener
{
    /** Aussi pour les liens recruteur (HomeController) : un aperçu de lien n'est pas une visite */
    public const ROBOTS = '/bot|crawl|spider|slurp|preview|facebookexternalhit|headless|lighthouse|curl|wget|python|axe-core/i';

    public function __construct(
        private readonly Connection $connection,
        private readonly Security $security,
    ) {
    }

    #[AsEventListener(KernelEvents::RESPONSE)]
    public function compter(ResponseEvent $event): void
    {
        $request = $event->getRequest();
        $response = $event->getResponse();
        $agent = $request->headers->get('User-Agent', '');
        if (!$event->isMainRequest() || !$request->isMethod('GET') || 200 !== $response->getStatusCode()
            || !str_starts_with($response->headers->get('Content-Type', 'text/html'), 'text/html')
            || !str_starts_with((string) $request->attributes->get('_route'), 'app_')
            || '' === $agent || preg_match(self::ROBOTS, $agent)
            || $this->security->isGranted('ROLE_ADMIN')) {
            return;
        }

        $langue = $request->getLocale();
        $chemin = substr(preg_replace('#^/'.preg_quote($langue, '#').'(?=/|$)#', '', $request->getPathInfo()) ?: '/', 0, 200);
        $provenance = parse_url((string) $request->headers->get('Referer'), \PHP_URL_HOST);
        $source = match (true) {
            !$provenance => 'direct',
            $provenance === $request->getHost() => 'site',
            default => substr(preg_replace('/^www\./', '', $provenance), 0, 100),
        };

        $this->connection->executeStatement(
            'INSERT INTO visite_jour (jour, chemin, langue, source, nombre) VALUES (CURRENT_DATE, :chemin, :langue, :source, 1)
             ON CONFLICT (jour, chemin, langue, source) DO UPDATE SET nombre = visite_jour.nombre + 1',
            ['chemin' => $chemin, 'langue' => $langue, 'source' => $source],
        );
    }
}
