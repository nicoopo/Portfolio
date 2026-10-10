<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Page /uses : matériel, logiciels et stack (une seule ligne, modifiée dans l'administration, comme Maintenant).
 * La date affichée suit chaque enregistrement.
 */
#[ORM\Entity]
#[ORM\HasLifecycleCallbacks]
class Outils
{
    use Traduisible;

    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;

    /** Markdown (App\Twig\Markdown) */
    #[Assert\NotBlank]
    #[ORM\Column(type: Types::TEXT)]
    private string $contenu = '';

    #[ORM\Column]
    private \DateTimeImmutable $majLe;

    public function __construct()
    {
        $this->majLe = new \DateTimeImmutable();
    }

    #[ORM\PreUpdate]
    public function dater(): void
    {
        $this->majLe = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }
    public function getContenu(): string { return $this->contenu; }
    public function setContenu(?string $contenu): static { $this->contenu = $contenu ?? ''; return $this; }
    public function getMajLe(): \DateTimeImmutable { return $this->majLe; }

    public function __toString(): string { return 'Mes outils'; }
}
