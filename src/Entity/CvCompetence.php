<?php

namespace App\Entity;

use App\Repository\CvCompetenceRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Bloc de compétences du CV (regroupements propres au CV, distincts des neurones du cerveau).
 */
#[ORM\Entity(repositoryClass: CvCompetenceRepository::class)]
class CvCompetence
{
    use Traduisible;

    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;

    #[Assert\NotBlank]
    #[Assert\Length(max: 100)]
    #[ORM\Column(length: 100)]
    private string $titre = '';

    /** Séparés par des virgules */
    #[Assert\NotBlank]
    #[ORM\Column(type: Types::TEXT)]
    private string $elements = '';

    #[Assert\PositiveOrZero]
    #[ORM\Column]
    private int $position = 0;

    public function getId(): ?int { return $this->id; }
    public function getTitre(): string { return $this->titre; }
    public function setTitre(?string $titre): static { $this->titre = $titre ?? ''; return $this; }
    public function getElements(): string { return $this->elements; }
    public function setElements(?string $elements): static { $this->elements = $elements ?? ''; return $this; }
    public function getPosition(): int { return $this->position; }
    public function setPosition(?int $position): static { $this->position = $position ?? 0; return $this; }

    public function __toString(): string { return $this->titre; }
}
