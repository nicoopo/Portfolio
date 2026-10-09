<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Statistiques de visite (App\EventListener\StatistiquesListener) : un compteur par jour, page, langue et provenance.
 * Aucune donnée personnelle (ni IP, ni cookie, ni identifiant de visiteur) : des totaux, rien d'autre.
 */
#[ORM\Entity]
#[ORM\UniqueConstraint(columns: ['jour', 'chemin', 'langue', 'source'])]
class VisiteJour
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private \DateTimeImmutable $jour;

    /** Adresse sans le préfixe de langue : /projects/pendu */
    #[ORM\Column(length: 200)]
    private string $chemin;

    #[ORM\Column(length: 5)]
    private string $langue;

    /** Site d'où vient le visiteur (linkedin.com…), « direct » sans provenance, « site » en naviguant d'une page à l'autre */
    #[ORM\Column(length: 100)]
    private string $source;

    #[ORM\Column]
    private int $nombre = 0;
}
