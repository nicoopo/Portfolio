<?php

namespace App\Entity;

use App\Repository\DemandeContactRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Message reçu par le formulaire de contact, enregistré avant l'envoi de l'e-mail :
 * même si l'envoi échoue, il est consultable dans l'administration.
 * Données personnelles (nom, e-mail) : à supprimer sur demande (action « Supprimer » de l'admin).
 */
#[ORM\Entity(repositoryClass: DemandeContactRepository::class)]
#[ORM\Index(columns: ['recu_le'])]
class DemandeContact
{
    public const STATUT_EN_COURS = 'en_cours';
    public const STATUT_ENVOYE = 'envoye';
    public const STATUT_ECHEC = 'echec';

    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    private string $nom;

    #[ORM\Column(length: 180)]
    private string $email;

    #[ORM\Column(type: Types::TEXT)]
    private string $message;

    /** Langue de la page d'où vient le message (fr, en) */
    #[ORM\Column(length: 2)]
    private string $langue;

    #[ORM\Column(length: 10)]
    private string $statut = self::STATUT_EN_COURS;

    #[ORM\Column]
    private \DateTimeImmutable $recuLe;

    public function __construct(string $nom, string $email, string $message, string $langue)
    {
        $this->nom = $nom;
        $this->email = $email;
        $this->message = $message;
        $this->langue = $langue;
        $this->recuLe = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }
    public function getNom(): string { return $this->nom; }
    public function getEmail(): string { return $this->email; }
    public function getMessage(): string { return $this->message; }
    public function getLangue(): string { return $this->langue; }
    public function getStatut(): string { return $this->statut; }
    public function getRecuLe(): \DateTimeImmutable { return $this->recuLe; }

    public function marquerEnvoye(): void { $this->statut = self::STATUT_ENVOYE; }
    public function marquerEchec(): void { $this->statut = self::STATUT_ECHEC; }

    public function __toString(): string
    {
        return $this->nom.' <'.$this->email.'>';
    }
}
