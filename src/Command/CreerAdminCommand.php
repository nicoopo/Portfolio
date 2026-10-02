<?php

namespace App\Command;

use App\Entity\Utilisateur;
use App\Repository\UtilisateurRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Crée un compte d'administration, ou change son mot de passe s'il existe déjà.
 * Le mot de passe est demandé sans écho (jamais en argument : il finirait dans l'historique du shell).
 */
#[AsCommand('app:admin:create', 'Crée un compte de l\'administration (ou change son mot de passe)')]
final class CreerAdminCommand
{
    private const LONGUEUR_MIN = 12;

    public function __construct(
        private readonly UtilisateurRepository $utilisateurs,
        private readonly EntityManagerInterface $entityManager,
        private readonly UserPasswordHasherInterface $hasher,
    ) {
    }

    public function __invoke(SymfonyStyle $io, #[Argument('Identifiant de connexion')] string $identifiant = 'admin'): int
    {
        $motDePasse = $io->askHidden('Mot de passe ('.self::LONGUEUR_MIN.' caractères minimum)', function (?string $valeur): string {
            if (mb_strlen((string) $valeur) < self::LONGUEUR_MIN) {
                throw new \RuntimeException('Trop court : '.self::LONGUEUR_MIN.' caractères minimum.');
            }

            return $valeur;
        });

        $utilisateur = $this->utilisateurs->findOneBy(['identifiant' => $identifiant]);
        $nouveau = null === $utilisateur;
        $utilisateur ??= (new Utilisateur())->setIdentifiant($identifiant);
        $utilisateur->setMotDePasse($this->hasher->hashPassword($utilisateur, $motDePasse));

        $this->entityManager->persist($utilisateur);
        $this->entityManager->flush();

        $io->success($nouveau ? "Compte « $identifiant » créé." : "Mot de passe de « $identifiant » changé.");

        return Command::SUCCESS;
    }
}
