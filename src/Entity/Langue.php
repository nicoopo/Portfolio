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
    use Traduisible;

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

    public function getId(): ?int { return $this->id; }
    public function getNom(): string { return $this->nom; }
    public function setNom(?string $nom): static { $this->nom = $nom ?? ''; return $this; }
    public function getNiveau(): string { return $this->niveau; }
    public function setNiveau(?string $niveau): static { $this->niveau = $niveau ?? ''; return $this; }
    public function getPosition(): int { return $this->position; }
    public function setPosition(?int $position): static { $this->position = $position ?? 0; return $this; }

    public function __toString(): string { return $this->nom; }
}
