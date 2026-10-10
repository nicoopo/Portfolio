<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Créneau d'entretien ouvert dans l'administration, réservable par un recruteur depuis son lien (/rendez-vous?pour=code).
 * Heure de Paris telle que saisie (comme Candidature::$entretienLe), une heure.
 */
#[ORM\Entity]
#[ORM\Index(columns: ['debut'])]
class Creneau
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;

    #[Assert\NotNull]
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $debut = null;

    /** Réservé par ce lien (null : libre) ; la réservation se fait en une requête conditionnelle (RendezVousController) */
    #[ORM\ManyToOne]
    #[ORM\JoinColumn(onDelete: 'SET NULL')]
    private ?LienRecruteur $lien = null;

    /** Personne qui a réservé, pour la recontacter */
    #[ORM\Column(length: 100, nullable: true)]
    private ?string $contactNom = null;

    #[ORM\Column(length: 180, nullable: true)]
    private ?string $contactEmail = null;

    public function getId(): ?int { return $this->id; }
    public function getDebut(): ?\DateTimeImmutable { return $this->debut; }
    public function setDebut(?\DateTimeImmutable $debut): static { $this->debut = $debut; return $this; }
    public function getLien(): ?LienRecruteur { return $this->lien; }
    public function getContactNom(): ?string { return $this->contactNom; }
    public function getContactEmail(): ?string { return $this->contactEmail; }

    public function __toString(): string { return $this->debut?->format('d/m/Y H:i') ?? 'Créneau'; }
}
