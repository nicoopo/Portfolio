<?php

namespace App\Entity;

use App\Repository\ProjetRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Projet : une carte sur la page Projets, relié aux neurones des compétences qu'il utilise.
 */
#[ORM\Entity(repositoryClass: ProjetRepository::class)]
class Projet
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;

    /** Ancre de la carte : /projects#slug */
    #[ORM\Column(length: 50, unique: true)]
    private string $slug;

    #[ORM\Column(length: 100)]
    private string $titre;

    #[ORM\Column(type: Types::TEXT)]
    private string $description;

    /** Badge affiché sous la description */
    #[ORM\Column(length: 100)]
    private string $tech;

    /** Chemin sous assets/images/projets/ */
    #[ORM\Column(length: 100)]
    private string $image;

    /** Groupe sur la page Projets ; les groupes suivent l'ordre de leurs projets */
    #[ORM\Column(length: 50)]
    private string $categorie;

    #[ORM\Column]
    private int $position;

    /** @var Collection<int, Competence> */
    #[ORM\ManyToMany(targetEntity: Competence::class, inversedBy: 'projets')]
    #[ORM\OrderBy(['position' => 'ASC'])]
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
}
