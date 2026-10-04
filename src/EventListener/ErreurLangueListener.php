<?php

namespace App\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;

/**
 * Une adresse inconnue n'a pas de route, donc pas de _locale : sans ça, /en/xyz afficherait la page 404 en français.
 * Avant ErrorListener (-128), qui rend la page d'erreur dans une sous-requête copiée de celle-ci.
 */
#[AsEventListener(priority: 0)]
final class ErreurLangueListener
{
    public function __invoke(ExceptionEvent $event): void
    {
        $chemin = $event->getRequest()->getPathInfo();
        if ('/en' === $chemin || str_starts_with($chemin, '/en/')) {
            $event->getRequest()->setLocale('en');
        }
    }
}
