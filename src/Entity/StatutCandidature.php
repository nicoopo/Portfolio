<?php

namespace App\Entity;

/** Où en est une candidature ; les deux premiers attendent une réponse, donc une relance (Candidature::$relancerLe). */
enum StatutCandidature: string
{
    case Envoyee = 'envoyee';
    case Relancee = 'relancee';
    case Entretien = 'entretien';
    case Acceptee = 'acceptee';
    case Refusee = 'refusee';

    public function libelle(): string
    {
        return match ($this) {
            self::Envoyee => 'Envoyée',
            self::Relancee => 'Relancée',
            self::Entretien => 'Entretien',
            self::Acceptee => 'Acceptée',
            self::Refusee => 'Refusée',
        };
    }

    public function enAttente(): bool
    {
        return \in_array($this, [self::Envoyee, self::Relancee], true);
    }
}
