<?php

namespace App\Twig;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Twig\Attribute\AsTwigFunction;

/** Carnet des découvertes (base.html.twig) : {{ decouvertes_trouvees()['konami'] ?? 0 }} */
final class Decouvertes
{
    public function __construct(private readonly Connection $connection)
    {
    }

    /** @return array<string, int> id => nombre de visiteurs qui l'ont trouvée */
    #[AsTwigFunction('decouvertes_trouvees')]
    public function trouvees(): array
    {
        try {
            return array_map('intval', $this->connection->fetchAllKeyValue('SELECT id, nombre FROM decouverte'));
        } catch (Exception) {
            return []; // simple décor : sans base (page d'erreur), le carnet s'affiche sans les compteurs
        }
    }
}
