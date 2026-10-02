<?php

namespace App\Entity;

use App\Repository\EtapeParcoursRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Étape du parcours scolaire : la frise de la page Univers, un souvenir dans le cerveau 3D.
 */
#[ORM\Entity(repositoryClass: EtapeParcoursRepository::class)]
class EtapeParcours
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;

    /** Nom court, unique parmi les noms du cerveau (sélection par nom) */
    #[ORM\Column(length: 50, unique: true)]
    private string $nom;

    #[ORM\Column(length: 20)]
    private string $dates;

    #[ORM\Column(length: 150)]
    private string $intitule;

    /** Option du diplôme (BTS SIO option SLAM…) */
    #[ORM\Column(length: 150, nullable: true)]
    private ?string $specialite;

    #[ORM\Column(length: 100)]
    private string $ecole;

    #[ORM\Column(length: 100)]
    private string $lieu;

    #[ORM\Column(length: 100)]
    private string $resultat;

    /** 1 = le plus récent */
    #[ORM\Column]
    private int $position;

    public function getId(): ?int { return $this->id; }
    public function getNom(): string { return $this->nom; }
    public function getDates(): string { return $this->dates; }
    public function getIntitule(): string { return $this->intitule; }
    public function getSpecialite(): ?string { return $this->specialite; }
    public function getEcole(): string { return $this->ecole; }
    public function getLieu(): string { return $this->lieu; }
    public function getResultat(): string { return $this->resultat; }
    public function getPosition(): int { return $this->position; }

    /** Données du cerveau 3D (data-brain-souvenirs-value) */
    public function toArray(): array
    {
        return [
            'nom' => $this->nom,
            'dates' => $this->dates,
            'intitule' => $this->intitule,
            'option' => $this->specialite,
            'ecole' => $this->ecole,
            'lieu' => $this->lieu,
            'resultat' => $this->resultat,
        ];
    }
}
