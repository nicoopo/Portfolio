<?php

namespace App\Entity;

use App\Repository\CvProfilRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * En-tête et profil du CV (une seule ligne, modifiée dans l'administration).
 */
#[ORM\Entity(repositoryClass: CvProfilRepository::class)]
class CvProfil
{
    use Traduisible;

    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;

    /** Ex. « Développeur Fullstack » */
    #[Assert\NotBlank]
    #[Assert\Length(max: 100)]
    #[ORM\Column(length: 100)]
    private string $titre = '';

    /** Séparées par des virgules : « Curieux, Rigoureux, Autonome » */
    #[Assert\NotBlank]
    #[Assert\Length(max: 150)]
    #[ORM\Column(length: 150)]
    private string $qualites = '';

    #[Assert\NotBlank]
    #[ORM\Column(type: Types::TEXT)]
    private string $resume = '';

    /** Mise en avant sous le résumé, une ligne par phrase */
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $accroche = null;

    #[Assert\NotBlank]
    #[Assert\Length(max: 30)]
    #[ORM\Column(length: 30)]
    private string $telephone = '';

    #[Assert\NotBlank]
    #[Assert\Length(max: 180)]
    #[ORM\Column(length: 180)]
    private string $email = '';

    public function getId(): ?int { return $this->id; }
    public function getTitre(): string { return $this->titre; }
    public function setTitre(?string $titre): static { $this->titre = $titre ?? ''; return $this; }
    public function getQualites(): string { return $this->qualites; }
    public function setQualites(?string $qualites): static { $this->qualites = $qualites ?? ''; return $this; }
    public function getResume(): string { return $this->resume; }
    public function setResume(?string $resume): static { $this->resume = $resume ?? ''; return $this; }
    public function getAccroche(): ?string { return $this->accroche; }
    public function setAccroche(?string $accroche): static { $this->accroche = $accroche ?: null; return $this; }
    public function getTelephone(): string { return $this->telephone; }
    public function setTelephone(?string $telephone): static { $this->telephone = $telephone ?? ''; return $this; }
    public function getEmail(): string { return $this->email; }
    public function setEmail(?string $email): static { $this->email = $email ?? ''; return $this; }

    public function __toString(): string { return $this->titre; }
}
