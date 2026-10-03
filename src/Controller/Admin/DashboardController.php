<?php

namespace App\Controller\Admin;

use App\Entity\Competence;
use App\Entity\DemandeContact;
use App\Entity\Journal;
use App\Repository\DemandeContactRepository;
use App\Repository\JournalRepository;
use App\Repository\ProjetRepository;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;

/** Administration du contenu du portfolio (accès : ROLE_ADMIN, voir security.yaml). */
#[AdminDashboard(routePath: '/admin', routeName: 'admin')]
final class DashboardController extends AbstractDashboardController
{
    public function __construct(
        private readonly DemandeContactRepository $demandes,
        private readonly JournalRepository $journal,
        private readonly EntityManagerInterface $entityManager,
        private readonly ProjetRepository $projets,
    ) {
    }

    /** Tableau de bord : contact, sécurité, contenu, raccourcis */
    public function index(): Response
    {
        $debutDuMois = new \DateTimeImmutable('first day of this month midnight');

        return $this->render('admin/tableau_de_bord.html.twig', [
            'demandes_total' => $this->demandes->count([]),
            'demandes_mois' => $this->demandes->compterDepuis($debutDuMois),
            'demandes_echec' => $this->demandes->count(['statut' => DemandeContact::STATUT_ECHEC]),
            'connexions_refusees' => $this->journal->compterDepuis(Journal::CONNEXION_REFUSEE, new \DateTimeImmutable('-7 days')),
            'nb_competences' => $this->entityManager->getRepository(Competence::class)->count([]),
            'nb_projets' => $this->projets->count([]),
            'dernieres_demandes' => $this->demandes->findBy([], ['recuLe' => 'DESC'], 5),
            'dernier_journal' => $this->journal->findBy([], ['date' => 'DESC'], 8),
        ]);
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()->setTitle('Portfolio · Administration')->setLocales(['fr']);
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('Tableau de bord', 'fa fa-gauge');
        yield MenuItem::linkToUrl('Voir le site', 'fa fa-arrow-left', '/cerveau');
        yield MenuItem::section('Cerveau');
        yield MenuItem::linkTo(CategorieCompetenceCrudController::class, 'Catégories', 'fa fa-layer-group');
        yield MenuItem::linkTo(CompetenceCrudController::class, 'Compétences', 'fa fa-brain');
        yield MenuItem::linkTo(ProjetCrudController::class, 'Projets', 'fa fa-diagram-project');
        yield MenuItem::linkTo(PassionCrudController::class, 'Passions', 'fa fa-star');
        yield MenuItem::linkTo(EtapeParcoursCrudController::class, 'Parcours', 'fa fa-graduation-cap');
        yield MenuItem::section('CV');
        yield MenuItem::linkTo(CvProfilCrudController::class, 'Profil', 'fa fa-id-card');
        yield MenuItem::linkTo(ExperienceCrudController::class, 'Expériences', 'fa fa-briefcase');
        yield MenuItem::linkTo(CvCompetenceCrudController::class, 'Compétences du CV', 'fa fa-code');
        yield MenuItem::linkTo(LangueCrudController::class, 'Langues', 'fa fa-language');
        yield MenuItem::linkTo(CentreInteretCrudController::class, "Centres d'intérêt", 'fa fa-heart');
        yield MenuItem::section('Visiteurs');
        yield MenuItem::linkTo(DemandeContactCrudController::class, 'Demandes de contact', 'fa fa-envelope');
        yield MenuItem::section('Administration');
        yield MenuItem::linkTo(UtilisateurCrudController::class, 'Comptes', 'fa fa-user-shield');
        yield MenuItem::linkTo(JournalCrudController::class, 'Journal', 'fa fa-list-check');
        yield MenuItem::section();
        yield MenuItem::linkToLogout('Se déconnecter', 'fa fa-right-from-bracket');
    }
}
