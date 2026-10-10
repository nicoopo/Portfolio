<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Lien personnalisé envoyé à un recruteur : /?pour=code. L'accueil salue l'entreprise et met en avant CV et contact ;
 * l'administration voit si le lien a été ouvert (visites hors administrateur connecté).
 */
#[ORM\Entity]
#[UniqueEntity('code')]
class LienRecruteur
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;

    /** Dans l'adresse : difficile à deviner, pour qu'on ne tombe pas sur le lien d'une autre entreprise */
    #[ORM\Column(length: 16, unique: true)]
    private string $code;

    #[Assert\NotBlank]
    #[Assert\Length(max: 100)]
    #[ORM\Column(length: 100)]
    private string $entreprise = '';

    #[Assert\Length(max: 150)]
    #[ORM\Column(length: 150, nullable: true)]
    private ?string $poste = null;

    /** Mot personnel affiché sous le bonjour */
    #[Assert\Length(max: 500)]
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $message = null;

    /** @var Collection<int, Projet> mis en avant sur l'accueil, dans l'ordre de la page Projets */
    #[Assert\Count(max: 3)]
    #[ORM\ManyToMany(targetEntity: Projet::class)]
    #[ORM\OrderBy(['position' => 'ASC'])]
    private Collection $projets;

    /** CV ouvert depuis ce lien : remplace l'accroche du profil */
    #[Assert\Length(max: 600)]
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $accroche = null;

    /** @var Collection<int, Competence> en tête du CV ouvert depuis ce lien (« Points forts pour ce poste ») */
    #[Assert\Count(max: 5)]
    #[ORM\ManyToMany(targetEntity: Competence::class)]
    #[ORM\OrderBy(['position' => 'ASC'])]
    private Collection $competences;

    #[ORM\Column]
    private int $visites = 0;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $premiereVisite = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $derniereVisite = null;

    public function __construct()
    {
        $this->code = bin2hex(random_bytes(5));
        $this->projets = new ArrayCollection();
        $this->competences = new ArrayCollection();
    }

    /** @return Collection<int, Competence> */
    public function getCompetences(): Collection { return $this->competences; }
    public function addCompetence(Competence $competence): static { if (!$this->competences->contains($competence)) { $this->competences->add($competence); } return $this; }
    public function removeCompetence(Competence $competence): static { $this->competences->removeElement($competence); return $this; }
    public function getAccroche(): ?string { return $this->accroche; }
    public function setAccroche(?string $accroche): static { $this->accroche = $accroche ?: null; return $this; }

    /** @return Collection<int, Projet> */
    public function getProjets(): Collection { return $this->projets; }
    public function addProjet(Projet $projet): static { if (!$this->projets->contains($projet)) { $this->projets->add($projet); } return $this; }
    public function removeProjet(Projet $projet): static { $this->projets->removeElement($projet); return $this; }

    public function visiter(): void
    {
        ++$this->visites;
        $this->derniereVisite = new \DateTimeImmutable();
        $this->premiereVisite ??= $this->derniereVisite;
    }

    public function getId(): ?int { return $this->id; }
    public function getCode(): string { return $this->code; }
    public function getEntreprise(): string { return $this->entreprise; }
    public function getPoste(): ?string { return $this->poste; }
    public function getMessage(): ?string { return $this->message; }
    public function getVisites(): int { return $this->visites; }
    public function getPremiereVisite(): ?\DateTimeImmutable { return $this->premiereVisite; }
    public function getDerniereVisite(): ?\DateTimeImmutable { return $this->derniereVisite; }

    public function setEntreprise(?string $entreprise): static { $this->entreprise = $entreprise ?? ''; return $this; }
    public function setPoste(?string $poste): static { $this->poste = $poste ?: null; return $this; }
    public function setMessage(?string $message): static { $this->message = $message ?: null; return $this; }

    public function __toString(): string { return $this->entreprise; }
}
