<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Candidature d'alternance, suivie dans l'administration (privée : rien sur le site public).
 * Tant qu'elle attend une réponse, une date de relance est calculée : RELANCE_JOURS après l'envoi ou la dernière relance.
 */
#[ORM\Entity]
class Candidature
{
    public const RELANCE_JOURS = 7;

    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;

    #[Assert\NotBlank]
    #[Assert\Length(max: 100)]
    #[ORM\Column(length: 100)]
    private string $entreprise = '';

    #[Assert\NotBlank]
    #[Assert\Length(max: 150)]
    #[ORM\Column(length: 150)]
    private string $poste = '';

    /** Annonce en ligne */
    #[Assert\Url(requireTld: true)]
    #[Assert\Length(max: 255)]
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $annonce = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private \DateTimeImmutable $envoyeeLe;

    #[ORM\Column(enumType: StatutCandidature::class, length: 20)]
    private StatutCandidature $statut = StatutCandidature::Envoyee;

    /** Vide une fois la réponse arrivée (entretien, acceptée, refusée) */
    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $relancerLe = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $notes = null;

    /** Lien recruteur envoyé avec la candidature : montre si l'entreprise a ouvert le site */
    #[ORM\ManyToOne]
    #[ORM\JoinColumn(onDelete: 'SET NULL')]
    private ?LienRecruteur $lien = null;

    public function __construct()
    {
        $this->envoyeeLe = new \DateTimeImmutable('today');
        $this->relancerLe = $this->envoyeeLe->modify('+'.self::RELANCE_JOURS.' days');
    }

    public function getId(): ?int { return $this->id; }
    public function getEntreprise(): string { return $this->entreprise; }
    public function getPoste(): string { return $this->poste; }
    public function getAnnonce(): ?string { return $this->annonce; }
    public function getEnvoyeeLe(): \DateTimeImmutable { return $this->envoyeeLe; }
    public function getStatut(): StatutCandidature { return $this->statut; }
    public function getRelancerLe(): ?\DateTimeImmutable { return $this->relancerLe; }
    public function getNotes(): ?string { return $this->notes; }
    public function getLien(): ?LienRecruteur { return $this->lien; }

    public function setEntreprise(?string $entreprise): static { $this->entreprise = $entreprise ?? ''; return $this; }
    public function setPoste(?string $poste): static { $this->poste = $poste ?? ''; return $this; }
    public function setAnnonce(?string $annonce): static { $this->annonce = $annonce ?: null; return $this; }
    public function setNotes(?string $notes): static { $this->notes = $notes ?: null; return $this; }
    public function setLien(?LienRecruteur $lien): static { $this->lien = $lien; return $this; }

    public function setEnvoyeeLe(?\DateTimeImmutable $envoyeeLe): static
    {
        $this->envoyeeLe = $envoyeeLe ?? new \DateTimeImmutable('today');
        if (StatutCandidature::Envoyee === $this->statut) {
            $this->relancerLe = $this->envoyeeLe->modify('+'.self::RELANCE_JOURS.' days');
        }

        return $this;
    }

    /** Relancée : prochaine relance dans RELANCE_JOURS ; réponse arrivée : plus de relance */
    public function setStatut(StatutCandidature $statut): static
    {
        if ($statut !== $this->statut) {
            $this->relancerLe = match ($statut) {
                StatutCandidature::Envoyee => $this->envoyeeLe->modify('+'.self::RELANCE_JOURS.' days'),
                StatutCandidature::Relancee => new \DateTimeImmutable('today +'.self::RELANCE_JOURS.' days'),
                default => null,
            };
        }
        $this->statut = $statut;

        return $this;
    }

    public function aRelancer(): bool
    {
        return null !== $this->relancerLe && $this->relancerLe <= new \DateTimeImmutable('today');
    }

    public function __toString(): string { return $this->entreprise.' — '.$this->poste; }
}
