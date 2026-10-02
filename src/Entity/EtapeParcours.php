<?php

namespace App\Entity;

use App\Repository\EtapeParcoursRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Étape du parcours scolaire : la frise de la page Univers, un souvenir dans le cerveau 3D.
 */
#[ORM\Entity(repositoryClass: EtapeParcoursRepository::class)]
#[UniqueEntity('nom')]
class EtapeParcours
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;

    /** Nom court, unique parmi les noms du cerveau (sélection par nom) */
    #[Assert\NotBlank]
    #[Assert\Length(max: 50)]
    #[ORM\Column(length: 50, unique: true)]
    private string $nom;

    #[Assert\NotBlank]
    #[Assert\Length(max: 20)]
    #[ORM\Column(length: 20)]
    private string $dates;

    #[Assert\NotBlank]
    #[Assert\Length(max: 150)]
    #[ORM\Column(length: 150)]
    private string $intitule;

    /** Option du diplôme (BTS SIO option SLAM…) */
    #[Assert\Length(max: 150)]
    #[ORM\Column(length: 150, nullable: true)]
    private ?string $specialite;

    #[Assert\NotBlank]
    #[Assert\Length(max: 100)]
    #[ORM\Column(length: 100)]
    private string $ecole;

    #[Assert\NotBlank]
    #[Assert\Length(max: 100)]
    #[ORM\Column(length: 100)]
    private string $lieu;

    #[Assert\NotBlank]
    #[Assert\Length(max: 100)]
    #[ORM\Column(length: 100)]
    private string $resultat;

    /** 1 = le plus récent */
    #[Assert\PositiveOrZero]
    #[ORM\Column]
    private int $position;

    // Traductions anglaises (facultatives : vides, le français s'affiche ; voir App\Twig\Traduction)
    #[Assert\Length(max: 50)]
    #[ORM\Column(length: 50, nullable: true)]
    private ?string $nomEn = null;

    #[Assert\Length(max: 150)]
    #[ORM\Column(length: 150, nullable: true)]
    private ?string $intituleEn = null;

    #[Assert\Length(max: 150)]
    #[ORM\Column(length: 150, nullable: true)]
    private ?string $specialiteEn = null;

    #[Assert\Length(max: 100)]
    #[ORM\Column(length: 100, nullable: true)]
    private ?string $resultatEn = null;

    public function getId(): ?int { return $this->id; }
    public function getNom(): string { return $this->nom; }
    public function getDates(): string { return $this->dates; }
    public function getIntitule(): string { return $this->intitule; }
    public function getSpecialite(): ?string { return $this->specialite; }
    public function getEcole(): string { return $this->ecole; }
    public function getLieu(): string { return $this->lieu; }
    public function getResultat(): string { return $this->resultat; }
    public function getPosition(): int { return $this->position; }

    public function setNom(?string $nom): static { $this->nom = $nom ?? ''; return $this; }
    public function setDates(?string $dates): static { $this->dates = $dates ?? ''; return $this; }
    public function setIntitule(?string $intitule): static { $this->intitule = $intitule ?? ''; return $this; }
    public function setSpecialite(?string $specialite): static { $this->specialite = $specialite; return $this; }
    public function setEcole(?string $ecole): static { $this->ecole = $ecole ?? ''; return $this; }
    public function setLieu(?string $lieu): static { $this->lieu = $lieu ?? ''; return $this; }
    public function setResultat(?string $resultat): static { $this->resultat = $resultat ?? ''; return $this; }
    public function setPosition(?int $position): static { $this->position = $position ?? 0; return $this; }

    public function getNomEn(): ?string { return $this->nomEn; }
    public function setNomEn(?string $nomEn): static { $this->nomEn = $nomEn ?: null; return $this; }
    public function getIntituleEn(): ?string { return $this->intituleEn; }
    public function setIntituleEn(?string $intituleEn): static { $this->intituleEn = $intituleEn ?: null; return $this; }
    public function getSpecialiteEn(): ?string { return $this->specialiteEn; }
    public function setSpecialiteEn(?string $specialiteEn): static { $this->specialiteEn = $specialiteEn ?: null; return $this; }
    public function getResultatEn(): ?string { return $this->resultatEn; }
    public function setResultatEn(?string $resultatEn): static { $this->resultatEn = $resultatEn ?: null; return $this; }

    public function __toString(): string { return $this->nom; }
}
