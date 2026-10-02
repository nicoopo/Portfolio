<?php

namespace App\Entity;

use App\Repository\LangueRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Langue parlée (CV).
 */
#[ORM\Entity(repositoryClass: LangueRepository::class)]
class Langue
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;

    #[Assert\NotBlank]
    #[Assert\Length(max: 50)]
    #[ORM\Column(length: 50)]
    private string $nom = '';

    /** Ex. « Courant », « B1 » */
    #[Assert\NotBlank]
    #[Assert\Length(max: 50)]
    #[ORM\Column(length: 50)]
    private string $niveau = '';

    #[Assert\PositiveOrZero]
    #[ORM\Column]
    private int $position = 0;

    // Traductions anglaises (facultatives : vides, le français s'affiche ; voir App\Twig\Traduction)
    #[Assert\Length(max: 50)]
    #[ORM\Column(length: 50, nullable: true)]
    private ?string $nomEn = null;

    #[Assert\Length(max: 50)]
    #[ORM\Column(length: 50, nullable: true)]
    private ?string $niveauEn = null;

    public function getId(): ?int { return $this->id; }
    public function getNom(): string { return $this->nom; }
    public function setNom(?string $nom): static { $this->nom = $nom ?? ''; return $this; }
    public function getNiveau(): string { return $this->niveau; }
    public function setNiveau(?string $niveau): static { $this->niveau = $niveau ?? ''; return $this; }
    public function getPosition(): int { return $this->position; }
    public function setPosition(?int $position): static { $this->position = $position ?? 0; return $this; }

    public function getNomEn(): ?string { return $this->nomEn; }
    public function setNomEn(?string $nomEn): static { $this->nomEn = $nomEn ?: null; return $this; }
    public function getNiveauEn(): ?string { return $this->niveauEn; }
    public function setNiveauEn(?string $niveauEn): static { $this->niveauEn = $niveauEn ?: null; return $this; }

    public function __toString(): string { return $this->nom; }
}
