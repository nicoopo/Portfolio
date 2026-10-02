<?php

namespace App\Entity;

use App\Repository\CentreInteretRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Centre d'intérêt (CV).
 */
#[ORM\Entity(repositoryClass: CentreInteretRepository::class)]
class CentreInteret
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;

    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    #[ORM\Column(length: 255)]
    private string $texte = '';

    #[Assert\PositiveOrZero]
    #[ORM\Column]
    private int $position = 0;

    // Traductions anglaises (facultatives : vides, le français s'affiche ; voir App\Twig\Traduction)
    #[Assert\Length(max: 255)]
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $texteEn = null;

    public function getId(): ?int { return $this->id; }
    public function getTexte(): string { return $this->texte; }
    public function setTexte(?string $texte): static { $this->texte = $texte ?? ''; return $this; }
    public function getPosition(): int { return $this->position; }
    public function setPosition(?int $position): static { $this->position = $position ?? 0; return $this; }

    public function getTexteEn(): ?string { return $this->texteEn; }
    public function setTexteEn(?string $texteEn): static { $this->texteEn = $texteEn ?: null; return $this; }

    public function __toString(): string { return $this->texte; }
}
