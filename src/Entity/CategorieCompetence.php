<?php

namespace App\Entity;

use App\Repository\CategorieCompetenceRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Catégorie de compétences (Front-End, Back-End…) : un lobe du cerveau 3D.
 */
#[ORM\Entity(repositoryClass: CategorieCompetenceRepository::class)]
#[UniqueEntity('nom')]
class CategorieCompetence
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;

    #[Assert\NotBlank]
    #[Assert\Length(max: 50)]
    #[ORM\Column(length: 50, unique: true)]
    private string $nom;

    /** Lobe où vivent ses neurones (voir ZONES dans assets/cerveau/neurons.js) */
    #[Assert\Choice(['frontal', 'parietal', 'temporal', 'occipital', 'limbique'])]
    #[ORM\Column(length: 20)]
    private string $zone;

    #[Assert\CssColor(Assert\CssColor::HEX_LONG)]
    #[ORM\Column(length: 7)]
    private string $couleur;

    #[Assert\PositiveOrZero]
    #[ORM\Column]
    private int $position;

    // Traductions anglaises (facultatives : vides, le français s'affiche ; voir App\Twig\Traduction)
    #[Assert\Length(max: 50)]
    #[ORM\Column(length: 50, nullable: true)]
    private ?string $nomEn = null;

    /** @var Collection<int, Competence> */
    #[ORM\OneToMany(targetEntity: Competence::class, mappedBy: 'categorie')]
    #[ORM\OrderBy(['position' => 'ASC'])]
    private Collection $competences;

    public function __construct()
    {
        $this->competences = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }
    public function getNom(): string { return $this->nom; }
    public function getZone(): string { return $this->zone; }
    public function getCouleur(): string { return $this->couleur; }
    public function getPosition(): int { return $this->position; }

    /** @return Collection<int, Competence> */
    public function getCompetences(): Collection { return $this->competences; }

    public function setNom(?string $nom): static { $this->nom = $nom ?? ''; return $this; }
    public function setZone(?string $zone): static { $this->zone = $zone ?? ''; return $this; }
    public function setCouleur(?string $couleur): static { $this->couleur = $couleur ?? ''; return $this; }
    public function setPosition(?int $position): static { $this->position = $position ?? 0; return $this; }

    public function __toString(): string { return $this->nom; }

    public function getNomEn(): ?string { return $this->nomEn; }
    public function setNomEn(?string $nomEn): static { $this->nomEn = $nomEn ?: null; return $this; }
}
