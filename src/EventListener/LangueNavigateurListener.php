<?php

namespace App\EventListener;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;

/**
 * Première visite sur / : redirige vers la langue du navigateur (Accept-Language) si le site l'a.
 * Le cookie « langue » est posé dès la première page vue, quelle qu'elle soit : ensuite plus aucune redirection,
 * donc un clic sur « Français » depuis /de/ reste en français. Sans Accept-Language (robots), on reste sur /.
 */
final class LangueNavigateurListener
{
    private const COOKIE = 'langue';

    /** @param list<string> $langues */
    public function __construct(#[Autowire('%kernel.enabled_locales%')] private readonly array $langues)
    {
    }

    #[AsEventListener]
    public function onRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();
        if (!$event->isMainRequest() || '/' !== $request->getPathInfo() || !$request->isMethodSafe() || $request->cookies->has(self::COOKIE)) {
            return;
        }

        $langue = $request->getPreferredLanguage($this->langues);
        if ('fr' !== $langue) {
            // Paramètres gardés : un lien recruteur (/?pour=…) doit survivre à la redirection
            $requete = $request->getQueryString();
            $reponse = new RedirectResponse($request->getUriForPath("/$langue/").($requete ? '?'.$requete : ''));
            $reponse->setVary(['Accept-Language', 'Cookie']);
            $event->setResponse($reponse);
        }
    }

    #[AsEventListener]
    public function onResponse(ResponseEvent $event): void
    {
        $request = $event->getRequest();
        if ($event->isMainRequest() && !$request->cookies->has(self::COOKIE) && $request->attributes->has('_locale')
            && !$event->getResponse()->isRedirection()) {
            $event->getResponse()->headers->setCookie(
                Cookie::create(self::COOKIE, $request->getLocale(), new \DateTimeImmutable('+1 year'), sameSite: Cookie::SAMESITE_LAX),
            );
        }
    }
}
