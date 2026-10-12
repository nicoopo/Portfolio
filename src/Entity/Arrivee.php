<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Arrivée d'un visiteur sur le site, pour le globe de /stats (ArriveeController) : l'heure, le fuseau horaire du
 * navigateur (« Europe/Paris ») et le lieu approché d'après l'IP, arrondi à ~10 km. Pas l'IP elle-même, ni cookie,
 * ni identifiant : on ne peut pas relier deux arrivées.
 * Effacées au bout de 30 jours.
 */
#[ORM\Entity]
#[ORM\Index(columns: ['moment'])]
class Arrivee
{
    public const JOURS = 30;

    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $moment;

    /** Identifiant IANA, vérifié contre la liste de PHP */
    #[ORM\Column(length: 64)]
    private string $fuseau;

    /** Lieu d'après l'IP (base DB-IP Lite), au dixième de degré ; null sans base ou pour une IP inconnue */
    #[ORM\Column(nullable: true)]
    private ?float $ipLat = null;

    #[ORM\Column(nullable: true)]
    private ?float $ipLon = null;
}
