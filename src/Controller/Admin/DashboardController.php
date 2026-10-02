<?php

namespace App\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;

/** Administration du contenu du portfolio (accès : ROLE_ADMIN, voir security.yaml). */
#[AdminDashboard(routePath: '/admin', routeName: 'admin')]
final class DashboardController extends AbstractDashboardController
{
    public function index(): Response
    {
        // Pas de page d'accueil à maintenir : on ouvre directement la liste des compétences
        return $this->redirectToRoute('admin_competence_index');
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()->setTitle('Portfolio · Administration')->setLocales(['fr']);
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToUrl('Voir le site', 'fa fa-arrow-left', '/cerveau');
        yield MenuItem::section('Cerveau');
        yield MenuItem::linkTo(CategorieCompetenceCrudController::class, 'Catégories', 'fa fa-layer-group');
        yield MenuItem::linkTo(CompetenceCrudController::class, 'Compétences', 'fa fa-brain');
        yield MenuItem::linkTo(ProjetCrudController::class, 'Projets', 'fa fa-diagram-project');
        yield MenuItem::linkTo(PassionCrudController::class, 'Passions', 'fa fa-star');
        yield MenuItem::linkTo(EtapeParcoursCrudController::class, 'Parcours', 'fa fa-graduation-cap');
        yield MenuItem::section('Administration');
        yield MenuItem::linkTo(UtilisateurCrudController::class, 'Comptes', 'fa fa-user-shield');
        yield MenuItem::section();
        yield MenuItem::linkToLogout('Se déconnecter', 'fa fa-right-from-bracket');
    }
}
