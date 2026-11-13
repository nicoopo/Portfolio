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
        // Propriétés Unicode de PCRE plutôt que des plages : emoji affiché comme tel par défaut (⌚, 🚀), caractère suivi de
        // U+FE0F qui le force en emoji (✈️, #️⃣), drapeaux, liants. © ® ™ ★ ♟ seuls restent : la police les a.
        // Pas symfony/emoji : 37 Mo de données, et sa règle « strip » retire aussi © ® ™
        $sansEmoji = preg_replace('/\p{Emoji_Presentation}|\p{Emoji}\x{FE0F}\x{20E3}?|[\x{FE0F}\x{200D}\x{20E3}]|\p{Regional_Indicator}/u', '', (string) $texte);

        return trim(preg_replace('/ {2,}/', ' ', $sansEmoji)); // l'espace qui entourait l'emoji
    }
}
