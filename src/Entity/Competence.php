<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Compétence : une carte sur la page Compétences, un neurone dans le cerveau 3D.
 */
#[ORM\Entity]
class Competence
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;

    /** Unique : le cerveau sélectionne ses neurones par leur nom (légende, liens /cerveau#Nom) */
    #[ORM\Column(length: 50, unique: true)]
    private string $nom;

    #[ORM\Column]
    private int $position;

    #[ORM\ManyToOne(inversedBy: 'competences')]
    #[ORM\JoinColumn(nullable: false)]
    private CategorieCompetence $categorie;

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
    public function getCategorie(): CategorieCompetence { return $this->categorie; }

    /** @return Collection<int, Projet> */
    public function getProjets(): Collection { return $this->projets; }
}
