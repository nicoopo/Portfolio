<?php

namespace App\Twig;

use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Attribute\AsTwigFilter;

/**
 * Contenu de la base dans la langue de la page : en anglais, le champ « …En » de l'entité
 * (nomEn, descriptionEn…) s'il est rempli, sinon le français.
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
        $francais = $entite->{'get'.ucfirst($champ)}();
        if ('en' !== $this->requestStack->getCurrentRequest()?->getLocale()) {
            return $francais;
        }

        return $entite->{'get'.ucfirst($champ).'En'}() ?: $francais;
    }
}
