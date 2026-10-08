<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Contenu dans les autres langues que le français, par langue puis par champ :
 * ['en' => ['nom' => '…'], 'es' => ['nom' => '…']]. Champ absent : le français s'affiche (App\Twig\Traduction).
 * Saisie dans l'administration : App\Form\TraductionsType.
 */
trait Traduisible
{
    /** @var array<string, array<string, string>> */
    #[ORM\Column(type: Types::JSON, options: ['default' => '{}'])]
    private array $traductions = [];

    /** @return array<string, array<string, string>> */
    public function getTraductions(): array { return $this->traductions; }

    /** Les champs laissés vides sont retirés : la page retombe alors sur le français */
    public function setTraductions(array $traductions): static
    {
        $this->traductions = array_filter(array_map(
            static fn (?array $champs) => array_filter($champs ?? [], static fn (?string $texte) => null !== $texte && '' !== trim($texte)),
            $traductions,
        ));

        return $this;
    }
}
