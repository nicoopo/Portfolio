<?php

namespace App\Entity;

use App\Repository\UtilisateurRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Compte de l'administration (/admin). Table « utilisateur » : « user » est réservé en PostgreSQL.
 * Premier compte : `make admin-create`.
 */
#[ORM\Entity(repositoryClass: UtilisateurRepository::class)]
#[UniqueEntity('identifiant')]
class Utilisateur implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;

    #[Assert\NotBlank]
    #[Assert\Length(max: 50)]
    #[ORM\Column(length: 50, unique: true)]
    private string $identifiant = '';

    /** Empreinte du mot de passe (jamais le mot de passe lui-même) */
    #[ORM\Column]
    private string $motDePasse = '';

    /** @var list<string> */
    #[ORM\Column(type: Types::JSON)]
    private array $roles = ['ROLE_ADMIN'];

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $derniereConnexion = null;

    public function getId(): ?int { return $this->id; }
    public function getIdentifiant(): string { return $this->identifiant; }
    public function getDerniereConnexion(): ?\DateTimeImmutable { return $this->derniereConnexion; }

    public function setIdentifiant(?string $identifiant): static { $this->identifiant = $identifiant ?? ''; return $this; }
    public function setMotDePasse(string $empreinte): static { $this->motDePasse = $empreinte; return $this; }
    public function setDerniereConnexion(\DateTimeImmutable $date): static { $this->derniereConnexion = $date; return $this; }

    // ------------------------------------------------
    // Sécurité Symfony
    // ------------------------------------------------

    public function getUserIdentifier(): string
    {
        return $this->identifiant;
    }

    public function getPassword(): string
    {
        return $this->motDePasse;
    }

    /** @return list<string> */
    public function getRoles(): array
    {
        return $this->roles;
    }

    /** Rien à effacer : l'entité ne garde jamais le mot de passe en clair */
    public function eraseCredentials(): void
    {
    }

    public function __toString(): string
    {
        return $this->identifiant;
    }
}
