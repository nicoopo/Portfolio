<?php

namespace App\Service;

use App\Entity\Journal;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;

/** Écrit une entrée dans le journal, avec le compte connecté et l'IP de la requête en cours. */
final class Journaliste
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly RequestStack $requestStack,
        private readonly Security $security,
    ) {
    }

    /** $utilisateur : à préciser quand personne n'est (encore) connecté, ex. connexion refusée */
    public function noter(string $type, string $message, ?string $utilisateur = null): void
    {
        $this->entityManager->persist(new Journal(
            $type,
            $message,
            $utilisateur ?? $this->security->getUser()?->getUserIdentifier(),
            $this->requestStack->getCurrentRequest()?->getClientIp(),
        ));
        $this->entityManager->flush();
    }
}
