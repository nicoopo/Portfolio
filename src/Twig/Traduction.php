<?php

namespace App\Twig;

use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Attribute\AsTwigFilter;

/**
 * Contenu de la base dans la langue de la page : la traduction de l'entité (trait Traduisible) si elle
 * existe dans cette langue, sinon le français.
 * Twig : {{ competence|loc('nom') }} ; PHP : $traduction->loc($competence, 'nom').
 */
final class Traduction
{
    public function __construct(private readonly RequestStack $requestStack)
    {
    }

    #[AsTwigFilter('loc')]
    public function loc(object $entite, string $champ): ?string
    {
        $langue = $this->requestStack->getCurrentRequest()?->getLocale();

        return $entite->getTraductions()[$langue][$champ] ?? $entite->{'get'.ucfirst($champ)}();
    }
}
