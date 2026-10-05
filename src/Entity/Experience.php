<?php

namespace App\Entity;

use App\Repository\ExperienceRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Expérience professionnelle du CV.
 */
#[ORM\Entity(repositoryClass: ExperienceRepository::class)]
class Experience
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;

    #[Assert\NotBlank]
    #[Assert\Length(max: 100)]
    #[ORM\Column(length: 100)]
    private string $poste = '';

    #[Assert\NotBlank]
    #[Assert\Length(max: 100)]
    #[ORM\Column(length: 100)]
    private string $entreprise = '';

    #[Assert\NotBlank]
    #[Assert\Length(max: 100)]
    #[ORM\Column(length: 100)]
    private string $lieu = '';

    /** Ex. « 2023 - 2024 » */
    #[Assert\NotBlank]
    #[Assert\Length(max: 50)]
    #[ORM\Column(length: 50)]
    private string $periode = '';

    /** Ex. « Contrat d'apprentissage » */
    #[Assert\NotBlank]
    #[Assert\Length(max: 50)]
    #[ORM\Column(length: 50)]
    private string $contrat = '';

    /** Une mission par ligne */
    #[Assert\NotBlank]
    #[ORM\Column(type: Types::TEXT)]
    private string $missions = '';

    #[Assert\PositiveOrZero]
    #[ORM\Column]
    private int $position = 0;

    /** Site de l'entreprise ou du produit auquel j'ai contribué (facultatif, lien sur le CV) */
    #[Assert\Url(requireTld: true)]
    #[Assert\Length(max: 255)]
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $site = null;

    // Traductions anglaises (facultatives : vides, le français s'affiche ; voir App\Twig\Traduction)
    #[Assert\Length(max: 100)]
    #[ORM\Column(length: 100, nullable: true)]
    private ?string $posteEn = null;

    #[Assert\Length(max: 50)]
    #[ORM\Column(length: 50, nullable: true)]
    private ?string $periodeEn = null;

    #[Assert\Length(max: 50)]
    #[ORM\Column(length: 50, nullable: true)]
    private ?string $contratEn = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $missionsEn = null;

    public function getId(): ?int { return $this->id; }
    public function getPoste(): string { return $this->poste; }
    public function setPoste(?string $poste): static { $this->poste = $poste ?? ''; return $this; }
    public function getEntreprise(): string { return $this->entreprise; }
    public function setEntreprise(?string $entreprise): static { $this->entreprise = $entreprise ?? ''; return $this; }
    public function getLieu(): string { return $this->lieu; }
    public function setLieu(?string $lieu): static { $this->lieu = $lieu ?? ''; return $this; }
    public function getPeriode(): string { return $this->periode; }
    public function setPeriode(?string $periode): static { $this->periode = $periode ?? ''; return $this; }
    public function getContrat(): string { return $this->contrat; }
    public function setContrat(?string $contrat): static { $this->contrat = $contrat ?? ''; return $this; }
    public function getMissions(): string { return $this->missions; }
    public function setMissions(?string $missions): static { $this->missions = $missions ?? ''; return $this; }
    public function getPosition(): int { return $this->position; }
    public function setPosition(?int $position): static { $this->position = $position ?? 0; return $this; }
    public function getSite(): ?string { return $this->site; }
    public function setSite(?string $site): static { $this->site = $site ?: null; return $this; }

    public function getPosteEn(): ?string { return $this->posteEn; }
    public function setPosteEn(?string $posteEn): static { $this->posteEn = $posteEn ?: null; return $this; }
    public function getPeriodeEn(): ?string { return $this->periodeEn; }
    public function setPeriodeEn(?string $periodeEn): static { $this->periodeEn = $periodeEn ?: null; return $this; }
    public function getContratEn(): ?string { return $this->contratEn; }
    public function setContratEn(?string $contratEn): static { $this->contratEn = $contratEn ?: null; return $this; }
    public function getMissionsEn(): ?string { return $this->missionsEn; }
    public function setMissionsEn(?string $missionsEn): static { $this->missionsEn = $missionsEn ?: null; return $this; }

    public function __toString(): string { return $this->poste; }
}
