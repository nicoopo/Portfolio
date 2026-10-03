<?php

namespace App\Entity;

use App\Repository\ProjetRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Projet : une carte sur la page Projets, relié aux neurones des compétences qu'il utilise.
 */
#[ORM\Entity(repositoryClass: ProjetRepository::class)]
#[UniqueEntity('slug')]
class Projet
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;

    /** Ancre de la carte : /projects#slug */
    #[Assert\NotBlank]
    #[Assert\Length(max: 50)]
    #[Assert\Regex('/^[a-z0-9-]+$/', message: 'Minuscules, chiffres et tirets uniquement.')]
    #[ORM\Column(length: 50, unique: true)]
    private string $slug;

    #[Assert\NotBlank]
    #[Assert\Length(max: 100)]
    #[ORM\Column(length: 100)]
    private string $titre;

    #[Assert\NotBlank]
    #[ORM\Column(type: Types::TEXT)]
    private string $description;

    /** Badge affiché sous la description */
    #[Assert\NotBlank]
    #[Assert\Length(max: 100)]
    #[ORM\Column(length: 100)]
    private string $tech;

    /** Chemin sous assets/images/projets/ */
    #[Assert\NotBlank]
    #[Assert\Length(max: 100)]
    #[ORM\Column(length: 100)]
    private string $image;

    /** Groupe sur la page Projets ; les groupes suivent l'ordre de leurs projets */
    #[Assert\NotBlank]
    #[Assert\Length(max: 50)]
    #[ORM\Column(length: 50)]
    private string $categorie;

    #[Assert\PositiveOrZero]
    #[ORM\Column]
    private int $position;

    // Traductions anglaises (facultatives : vides, le français s'affiche ; voir App\Twig\Traduction)
    #[Assert\Length(max: 100)]
    #[ORM\Column(length: 100, nullable: true)]
    private ?string $titreEn = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $descriptionEn = null;

    #[Assert\Length(max: 50)]
    #[ORM\Column(length: 50, nullable: true)]
    private ?string $categorieEn = null;

    /** @var Collection<int, Competence> */
    #[ORM\ManyToMany(targetEntity: Competence::class, inversedBy: 'projets')]
    #[ORM\OrderBy(['position' => \SortDirection::Ascending])]
    private Collection $competences;

    public function __construct()
    {
        $this->competences = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }
    public function getSlug(): string { return $this->slug; }
    public function getTitre(): string { return $this->titre; }
    public function getDescription(): string { return $this->description; }
    public function getTech(): string { return $this->tech; }
    public function getImage(): string { return $this->image; }
    public function getCategorie(): string { return $this->categorie; }
    public function getPosition(): int { return $this->position; }

    /** @return Collection<int, Competence> */
    public function getCompetences(): Collection { return $this->competences; }

    public function setSlug(?string $slug): static { $this->slug = $slug ?? ''; return $this; }
    public function setTitre(?string $titre): static { $this->titre = $titre ?? ''; return $this; }
    public function setDescription(?string $description): static { $this->description = $description ?? ''; return $this; }
    public function setTech(?string $tech): static { $this->tech = $tech ?? ''; return $this; }
    public function setImage(?string $image): static { $this->image = $image ?? ''; return $this; }
    public function setCategorie(?string $categorie): static { $this->categorie = $categorie ?? ''; return $this; }
    public function setPosition(?int $position): static { $this->position = $position ?? 0; return $this; }

    public function addCompetence(Competence $competence): static
    {
        if (!$this->competences->contains($competence)) {
            $this->competences->add($competence);
        }

        return $this;
    }

    public function removeCompetence(Competence $competence): static
    {
        $this->competences->removeElement($competence);

        return $this;
    }

    public function getTitreEn(): ?string { return $this->titreEn; }
    public function setTitreEn(?string $titreEn): static { $this->titreEn = $titreEn ?: null; return $this; }
    public function getDescriptionEn(): ?string { return $this->descriptionEn; }
    public function setDescriptionEn(?string $descriptionEn): static { $this->descriptionEn = $descriptionEn ?: null; return $this; }
    public function getCategorieEn(): ?string { return $this->categorieEn; }
    public function setCategorieEn(?string $categorieEn): static { $this->categorieEn = $categorieEn ?: null; return $this; }

    public function __toString(): string { return $this->titre; }
}
