<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Message du livre d'or (/livre-d-or) : une étoile du ciel de la page, une fois approuvé dans l'administration.
 * Ni e-mail ni adresse IP enregistrés : un prénom et un texte court.
 */
#[ORM\Entity]
class MessageLivreOr
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private \DateTimeImmutable $creeLe;

    #[ORM\Column]
    private bool $approuve = false;

    public function __construct(
        #[ORM\Column(length: 40)]
        private string $prenom,
        #[ORM\Column(length: 280)]
        private string $message,
        #[ORM\Column(length: 5)]
        private string $langue,
    ) {
        $this->creeLe = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }
    public function getPrenom(): string { return $this->prenom; }
    public function getMessage(): string { return $this->message; }
    public function getLangue(): string { return $this->langue; }
    public function getCreeLe(): \DateTimeImmutable { return $this->creeLe; }
    public function isApprouve(): bool { return $this->approuve; }
    public function setApprouve(bool $approuve): static { $this->approuve = $approuve; return $this; }

    public function __toString(): string { return $this->prenom; }
}
