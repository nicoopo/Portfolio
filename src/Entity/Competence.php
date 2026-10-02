<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Compétence : une carte sur la page Compétences, un neurone dans le cerveau 3D.
 */
#[ORM\Entity]
#[UniqueEntity('nom')]
class Competence
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;

    /** Unique : le cerveau sélectionne ses neurones par leur nom (légende, liens /cerveau#Nom) */
    #[Assert\NotBlank]
    #[Assert\Length(max: 50)]
    #[ORM\Column(length: 50, unique: true)]
    private string $nom;

    #[Assert\PositiveOrZero]
    #[ORM\Column]
    private int $position;

    // Traductions anglaises (facultatives : vides, le français s'affiche ; voir App\Twig\Traduction)
    #[Assert\Length(max: 50)]
    #[ORM\Column(length: 50, nullable: true)]
    private ?string $nomEn = null;

    /** Nullable en PHP seulement (formulaire laissé vide → message de validation), jamais en base */
    #[Assert\NotNull]
    #[ORM\ManyToOne(inversedBy: 'competences')]
    #[ORM\JoinColumn(nullable: false)]
    private ?CategorieCompetence $categorie = null;

    /** @var Collection<int, Projet> */
    #[ORM\ManyToMany(targetEntity: Projet::class, mappedBy: 'competences')]
    #[ORM\OrderBy(['position' => 'ASC'])]
    private Collection $projets;

    public function __construct()
    {
        $this->projets = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }
    public function getNom(): string { return $this->nom; }
    public function getPosition(): int { return $this->position; }
    public function getCategorie(): ?CategorieCompetence { return $this->categorie; }

    /** @return Collection<int, Projet> */
    public function getProjets(): Collection { return $this->projets; }

    public function setNom(?string $nom): static { $this->nom = $nom ?? ''; return $this; }
    public function setPosition(?int $position): static { $this->position = $position ?? 0; return $this; }
    public function setCategorie(?CategorieCompetence $categorie): static { $this->categorie = $categorie; return $this; }

    public function __toString(): string { return $this->nom; }

    public function getNomEn(): ?string { return $this->nomEn; }
    public function setNomEn(?string $nomEn): static { $this->nomEn = $nomEn ?: null; return $this; }
}
