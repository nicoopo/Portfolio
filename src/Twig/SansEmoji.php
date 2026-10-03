<?php

namespace App\Twig;

use Twig\Attribute\AsTwigFilter;

/**
 * Retire les emojis d'un texte : la police du PDF (DejaVu Sans, Dompdf) ne les a pas
 * et les affiche en carrés vides. La page web, elle, les garde.
 */
final class SansEmoji
{
    #[AsTwigFilter('sans_emoji')]
    public function retirer(?string $texte): string
    {
        // Pictogrammes, symboles divers, sélecteurs de variante et liants (✈️ = ✈ + U+FE0F)
        return trim(preg_replace('/[\x{1F000}-\x{1FAFF}\x{2600}-\x{27BF}\x{2B00}-\x{2BFF}\x{FE0F}\x{200D}]/u', '', (string) $texte));
    }
}
