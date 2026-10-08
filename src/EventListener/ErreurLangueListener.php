<?php

namespace App\EventListener;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;

/**
 * Une adresse inconnue n'a pas de route, donc pas de _locale : sans ça, /en/xyz afficherait la page 404 en français.
 * Avant ErrorListener (-128), qui rend la page d'erreur dans une sous-requête copiée de celle-ci.
 */
#[AsEventListener(priority: 0)]
final class ErreurLangueListener
{
    /** @param list<string> $langues */
    public function __construct(#[Autowire('%kernel.enabled_locales%')] private readonly array $langues)
    {
    }

    public function __invoke(ExceptionEvent $event): void
    {
        $prefixe = explode('/', $event->getRequest()->getPathInfo())[1] ?? '';
        if ('fr' !== $prefixe && \in_array($prefixe, $this->langues, true)) {
            $event->getRequest()->setLocale($prefixe);
        }
    }
}
