<?php

namespace App\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Contracts\Service\ResetInterface;
use Twig\Attribute\AsTwigFunction;

/**
 * Content-Security-Policy : seuls les scripts du site (et les scripts en ligne portant le nonce
 * de la requête) peuvent s'exécuter. Un script injecté dans une page ne passerait pas.
 *
 * Les violations sont envoyées à /csp-report (CspReportController → logs, canal « csp »).
 */
#[AsEventListener]
final class ContentSecurityPolicyListener implements ResetInterface
{
    // Bloquante depuis l'issue #55, après une période en « Report-Only » sans violation en prod.
    // En cas de casse, revenir temporairement à 'Content-Security-Policy-Report-Only'.
    private const EN_TETE = 'Content-Security-Policy';

    private ?string $nonce = null;

    /** Même nom et même signature que NelmioSecurityBundle : EasyAdmin l'utilise s'il existe. */
    #[AsTwigFunction('csp_nonce')]
    public function nonce(string $usage = 'script'): string
    {
        return $this->nonce ??= base64_encode(random_bytes(18));
    }

    public function __invoke(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $event->getResponse()->headers->set(self::EN_TETE, implode('; ', [
            "default-src 'self'",
            // 'strict-dynamic' : ce que charge un script portant le nonce (modules de l'importmap, y compris les
            // modules « data: » qu'AssetMapper crée pour les imports CSS, contrôleurs Stimulus chargés à la demande)
            // hérite de sa confiance. Les navigateurs qui comprennent 'strict-dynamic' ignorent alors 'self' ;
            // les plus anciens, qui ne le comprennent pas, s'en servent à la place.
            "script-src 'self' 'nonce-{$this->nonce()}' 'strict-dynamic'",
            // Attributs style="" (couleurs de la légende du cerveau, EasyAdmin) : risque faible, contrairement aux scripts
            "style-src 'self' 'unsafe-inline'",
            "img-src 'self' data:",
            "frame-src 'self'",          // visionneuse PDF de /univers
            "frame-ancestors 'self'",
            "object-src 'none'",
            "base-uri 'self'",
            "form-action 'self'",
            'report-uri /csp-report',
        ]));
    }

    public function reset(): void
    {
        $this->nonce = null;
    }
}
