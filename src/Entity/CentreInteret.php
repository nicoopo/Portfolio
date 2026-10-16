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
    use Traduisible;

    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;

    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    #[ORM\Column(length: 255)]
    private string $texte = '';

    #[Assert\PositiveOrZero]
    #[ORM\Column]
    private int $position = 0;

    public function getId(): ?int { return $this->id; }
    public function getTexte(): string { return $this->texte; }
    public function setTexte(?string $texte): static { $this->texte = $texte ?? ''; return $this; }
    public function getPosition(): int { return $this->position; }
    public function setPosition(?int $position): static { $this->position = $position ?? 0; return $this; }

    public function __toString(): string { return $this->texte; }
}
