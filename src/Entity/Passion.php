<?php

namespace App\Entity;

use App\Repository\PassionRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Passion : une nébuleuse en orbite autour du cerveau 3D.
 */
#[ORM\Entity(repositoryClass: PassionRepository::class)]
class Passion
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50, unique: true)]
    private string $nom;

    #[ORM\Column(length: 7)]
    private string $couleur;

    #[ORM\Column(type: Types::TEXT)]
    private string $description;

    #[ORM\Column]
    private int $position;

    public function getId(): ?int { return $this->id; }
    public function getNom(): string { return $this->nom; }
    public function getCouleur(): string { return $this->couleur; }
    public function getDescription(): string { return $this->description; }
    public function getPosition(): int { return $this->position; }

    /** Données du cerveau 3D (data-brain-passions-value) */
    public function toArray(): array
    {
        return ['nom' => $this->nom, 'couleur' => $this->couleur, 'description' => $this->description];
    }
}
