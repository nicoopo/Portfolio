<?php

namespace App\Entity;

use App\Repository\PassionRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Passion : une nébuleuse en orbite autour du cerveau 3D.
 */
#[ORM\Entity(repositoryClass: PassionRepository::class)]
#[UniqueEntity('nom')]
class Passion
{
    use Traduisible;

    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;

    #[Assert\NotBlank]
    #[Assert\Length(max: 50)]
    #[ORM\Column(length: 50, unique: true)]
    private string $nom;

    #[Assert\CssColor(Assert\CssColor::HEX_LONG)]
    #[ORM\Column(length: 7)]
    private string $couleur;

    #[Assert\NotBlank]
    #[ORM\Column(type: Types::TEXT)]
    private string $description;

    #[Assert\PositiveOrZero]
    #[ORM\Column]
    private int $position;

    public function getId(): ?int { return $this->id; }
    public function getNom(): string { return $this->nom; }
    public function getCouleur(): string { return $this->couleur; }
    public function getDescription(): string { return $this->description; }
    public function getPosition(): int { return $this->position; }

    public function setNom(?string $nom): static { $this->nom = $nom ?? ''; return $this; }
    public function setCouleur(?string $couleur): static { $this->couleur = $couleur ?? ''; return $this; }
    public function setDescription(?string $description): static { $this->description = $description ?? ''; return $this; }
    public function setPosition(?int $position): static { $this->position = $position ?? 0; return $this; }

    public function __toString(): string { return $this->nom; }
}
