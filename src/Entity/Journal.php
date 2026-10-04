<?php

namespace App\Entity;

use App\Repository\JournalRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Journal des événements : connexions à l'administration, modifications du contenu, contact.
 * Contient des adresses IP (données personnelles) : purge par `make journal-purge` (12 mois).
 */
#[ORM\Entity(repositoryClass: JournalRepository::class)]
#[ORM\Index(columns: ['date'])]
#[ORM\Index(columns: ['type'])]
class Journal
{
    public const CONNEXION = 'connexion';
    public const CONNEXION_REFUSEE = 'connexion_refusee';
    public const DECONNEXION = 'deconnexion';
    public const CREATION = 'creation';
    public const MODIFICATION = 'modification';
    public const SUPPRESSION = 'suppression';
    public const CONTACT_ENVOYE = 'contact_envoye';
    public const CONTACT_ECHEC = 'contact_echec';
    public const SPAM_BLOQUE = 'spam_bloque';

    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private \DateTimeImmutable $date;

    #[ORM\Column(length: 20)]
    private string $type;

    #[ORM\Column(length: 255)]
    private string $message;

    /** Compte connecté (ou identifiant tenté, pour une connexion refusée) */
    #[ORM\Column(length: 50, nullable: true)]
    private ?string $utilisateur;

    #[ORM\Column(length: 45, nullable: true)]
    private ?string $ip;

    public function __construct(string $type, string $message, ?string $utilisateur, ?string $ip)
    {
        $this->date = new \DateTimeImmutable();
        $this->type = $type;
        $this->message = mb_substr($message, 0, 255);
        $this->utilisateur = null === $utilisateur ? null : mb_substr($utilisateur, 0, 50);
        $this->ip = $ip;
    }

    public function getId(): ?int { return $this->id; }
    public function getDate(): \DateTimeImmutable { return $this->date; }
    public function getType(): string { return $this->type; }
    public function getMessage(): string { return $this->message; }
    public function getUtilisateur(): ?string { return $this->utilisateur; }
    public function getIp(): ?string { return $this->ip; }
}
