<?php

namespace App\EventListener;

use App\Entity\Utilisateur;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;

/** Date de dernière connexion à l'administration, visible dans l'écran « Comptes ». */
#[AsEventListener]
final class DerniereConnexionListener
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    public function __invoke(LoginSuccessEvent $event): void
    {
        $utilisateur = $event->getUser();
        if ($utilisateur instanceof Utilisateur) {
            $utilisateur->setDerniereConnexion(new \DateTimeImmutable());
            $this->entityManager->flush();
        }
    }
}
