<?php

namespace App\Entity;

use App\Repository\CategorieCompetenceRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Catégorie de compétences (Front-End, Back-End…) : un lobe du cerveau 3D.
 */
#[ORM\Entity(repositoryClass: CategorieCompetenceRepository::class)]
class CategorieCompetence
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50, unique: true)]
    private string $nom;

    /** Lobe où vivent ses neurones (voir ZONES dans assets/cerveau/neurons.js) */
    #[ORM\Column(length: 20)]
    private string $zone;

    #[ORM\Column(length: 7)]
    private string $couleur;

    #[ORM\Column]
    private int $position;

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
}
