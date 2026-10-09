<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Nombre de visiteurs qui ont trouvé un easter egg (aucune donnée personnelle : un compteur par découverte).
 * Incrémenté par DecouverteController, affiché dans le carnet du panneau Réglages.
 */
#[ORM\Entity]
class Decouverte
{
    /** Identifiants de assets/scripts/decouvertes.js, dans l'ordre du carnet */
    public const IDS = ['trou-noir', 'konami', 'constellation-n', 'constellation-lion', 'terminal'];

    #[ORM\Id, ORM\Column(length: 30)]
    private string $id;

    #[ORM\Column]
    private int $nombre = 0;

    public function getId(): string { return $this->id; }
    public function getNombre(): int { return $this->nombre; }
}
