<?php

namespace App\Entity;

use App\Repository\ProjetRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * Projet : une carte sur la page Projets, relié aux neurones des compétences qu'il utilise.
 */
#[ORM\Entity(repositoryClass: ProjetRepository::class)]
#[UniqueEntity('slug')]
class Projet
{
    use Traduisible;

    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;

    /** Adresse de la page du projet : /projects/slug (et ancre de sa carte : /projects#slug) */
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

    /** Image du dépôt (ancienne méthode) : chemin sous assets/images/projets/ */
    #[Assert\Length(max: 100)]
    #[ORM\Column(length: 100, nullable: true)]
    private ?string $image = null;

    /** Image envoyée depuis l'admin : nom du fichier dans public/uploads/projets/ (volume Docker en prod) */
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $imageEnvoyee = null;

    /** Groupe sur la page Projets ; les groupes suivent l'ordre de leurs projets */
    #[Assert\NotBlank]
    #[Assert\Length(max: 50)]
    #[ORM\Column(length: 50)]
    private string $categorie;

    #[Assert\PositiveOrZero]
    #[ORM\Column]
    private int $position;

    /** Année du projet (frise /projects/frise) ; vide : absent de la frise */
    #[Assert\Range(min: 2000, max: 2100)]
    #[ORM\Column(type: Types::SMALLINT, nullable: true)]
    private ?int $annee = null;

    /** Page du projet (/projects/slug) : texte long, paragraphes séparés par une ligne vide ; vide = description */
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $details = null;

    /** Code source (GitHub…) */
    #[Assert\Url(requireTld: true)]
    #[Assert\Length(max: 255)]
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $depot = null;

    /** Version en ligne */
    #[Assert\Url(requireTld: true)]
    #[Assert\Length(max: 255)]
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $demo = null;

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
    public function getImage(): ?string { return $this->image; }
    public function getImageEnvoyee(): ?string { return $this->imageEnvoyee; }

    /** Chemin à passer à asset() : l'image envoyée l'emporte sur celle du dépôt */
    public function getImageChemin(): ?string
    {
        return match (true) {
            null !== $this->imageEnvoyee => 'uploads/projets/'.$this->imageEnvoyee,
            null !== $this->image => 'images/projets/'.$this->image,
            default => null,
        };
    }

    #[Assert\Callback]
    public function validerImage(ExecutionContextInterface $context): void
    {
        if (null === $this->getImageChemin()) {
            $context->buildViolation('Envoyez une image, ou indiquez le chemin d’une image du dépôt.')
                ->atPath('imageEnvoyee')
                ->addViolation();
        }
    }
    public function getCategorie(): string { return $this->categorie; }
    public function getPosition(): int { return $this->position; }

    /** @return Collection<int, Competence> */
    public function getCompetences(): Collection { return $this->competences; }

    public function setSlug(?string $slug): static { $this->slug = $slug ?? ''; return $this; }
    public function setTitre(?string $titre): static { $this->titre = $titre ?? ''; return $this; }
    public function setDescription(?string $description): static { $this->description = $description ?? ''; return $this; }
    public function setTech(?string $tech): static { $this->tech = $tech ?? ''; return $this; }
    public function setImage(?string $image): static { $this->image = $image ?: null; return $this; }
    public function setImageEnvoyee(?string $imageEnvoyee): static { $this->imageEnvoyee = $imageEnvoyee ?: null; return $this; }
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

    public function getAnnee(): ?int { return $this->annee; }
    public function setAnnee(?int $annee): static { $this->annee = $annee; return $this; }
    public function getDetails(): ?string { return $this->details; }
    public function setDetails(?string $details): static { $this->details = $details ?: null; return $this; }
    public function getDepot(): ?string { return $this->depot; }
    public function setDepot(?string $depot): static { $this->depot = $depot ?: null; return $this; }
    public function getDemo(): ?string { return $this->demo; }
    public function setDemo(?string $demo): static { $this->demo = $demo ?: null; return $this; }

    public function __toString(): string { return $this->titre; }
}
