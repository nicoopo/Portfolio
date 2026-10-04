<?php

namespace App\EventListener;

use App\Entity\Journal;
use App\Service\Journaliste;
use EasyCorp\Bundle\EasyAdminBundle\Event\AbstractLifecycleEvent;
use EasyCorp\Bundle\EasyAdminBundle\Event\AfterEntityDeletedEvent;
use EasyCorp\Bundle\EasyAdminBundle\Event\AfterEntityPersistedEvent;
use EasyCorp\Bundle\EasyAdminBundle\Event\AfterEntityUpdatedEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Event\LoginFailureEvent;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;
use Symfony\Component\Security\Http\Event\LogoutEvent;

/** Remplit le journal : connexions à l'administration et modifications du contenu (EasyAdmin). */
final class JournalListener
{
    /** Nom lisible des entités modifiables dans l'administration */
    private const ENTITES = [
        'CategorieCompetence' => 'Catégorie',
        'Competence' => 'Compétence',
        'Projet' => 'Projet',
        'Passion' => 'Passion',
        'EtapeParcours' => 'Étape du parcours',
        'Utilisateur' => 'Compte',
        'DemandeContact' => 'Demande de contact',
        'CvProfil' => 'Profil du CV',
        'Experience' => 'Expérience',
        'CvCompetence' => 'Compétences du CV',
        'Langue' => 'Langue',
        'CentreInteret' => "Centre d'intérêt",
    ];

    public function __construct(private readonly Journaliste $journaliste)
    {
    }

    #[AsEventListener]
    public function connexion(LoginSuccessEvent $event): void
    {
        $this->journaliste->noter(Journal::CONNEXION, 'Connexion à l\'administration', $event->getUser()->getUserIdentifier());
    }

    #[AsEventListener]
    public function connexionRefusee(LoginFailureEvent $event): void
    {
        $tente = $event->getPassport()?->getBadge(UserBadge::class)?->getUserIdentifier();
        $this->journaliste->noter(Journal::CONNEXION_REFUSEE, 'Connexion refusée : '.$event->getException()->getMessageKey(), $tente);
    }

    #[AsEventListener]
    public function deconnexion(LogoutEvent $event): void
    {
        $this->journaliste->noter(Journal::DECONNEXION, 'Déconnexion', $event->getToken()?->getUserIdentifier());
    }

    #[AsEventListener]
    public function creation(AfterEntityPersistedEvent $event): void
    {
        $this->noterContenu(Journal::CREATION, 'Création', $event);
    }

    #[AsEventListener]
    public function modification(AfterEntityUpdatedEvent $event): void
    {
        $this->noterContenu(Journal::MODIFICATION, 'Modification', $event);
    }

    #[AsEventListener]
    public function suppression(AfterEntityDeletedEvent $event): void
    {
        $this->noterContenu(Journal::SUPPRESSION, 'Suppression', $event);
    }

    /** Ex. « Modification — Compétence « Docker » » */
    private function noterContenu(string $type, string $action, AbstractLifecycleEvent $event): void
    {
        $entite = $event->getEntityInstance();
        $classe = (new \ReflectionClass($entite))->getShortName();
        $libelle = self::ENTITES[$classe] ?? $classe;
        $nom = $entite instanceof \Stringable ? ' « '.$entite.' »' : '';

        $this->journaliste->noter($type, $action.' — '.$libelle.$nom);
    }
}
