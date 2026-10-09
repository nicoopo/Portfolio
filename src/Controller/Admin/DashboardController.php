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
            'activite' => $this->activite(),
            'contacts_par_mois' => $this->contactsParMois(),
        ]);
    }

    /** Types du journal regroupés en séries ; l'ordre fixe la couleur (assets/admin_graphiques.js) */
    private const SERIES_JOURNAL = [
        'Connexions' => [Journal::CONNEXION, Journal::DECONNEXION],
        'Modifications' => [Journal::CREATION, Journal::MODIFICATION, Journal::SUPPRESSION],
        'Contacts' => [Journal::CONTACT_ENVOYE, Journal::CONTACT_ECHEC],
        'Spam bloqué' => [Journal::SPAM_BLOQUE],
        'Connexions refusées' => [Journal::CONNEXION_REFUSEE],
    ];

    /** Graphique : entrées du journal par jour sur 30 jours, par série */
    private function activite(): array
    {
        $jours = [];
        for ($i = 29; $i >= 0; --$i) {
            $jours[(new \DateTimeImmutable("-$i days"))->format('Y-m-d')] = 0;
        }
        $series = array_fill_keys(array_keys(self::SERIES_JOURNAL), $jours);
        foreach ($this->journal->typesDepuis(new \DateTimeImmutable('-29 days midnight')) as $entree) {
            foreach (self::SERIES_JOURNAL as $serie => $types) {
                if (\in_array($entree['type'], $types, true)) {
                    ++$series[$serie][$entree['date']->format('Y-m-d')];
                }
            }
        }

        return [
            'labels' => array_map(static fn (string $jour) => (new \DateTimeImmutable($jour))->format('d/m'), array_keys($jours)),
            'series' => array_map(static fn (string $serie, array $parJour) => ['label' => $serie, 'data' => array_values($parJour)], array_keys($series), $series),
        ];
    }

    /** Graphique : demandes de contact par mois sur 12 mois */
    private function contactsParMois(): array
    {
        $mois = [];
        for ($i = 11; $i >= 0; --$i) {
            $mois[(new \DateTimeImmutable("first day of -$i months"))->format('Y-m')] = 0;
        }
        foreach ($this->demandes->datesDepuis(new \DateTimeImmutable('first day of -11 months midnight')) as $date) {
            ++$mois[$date->format('Y-m')];
        }

        return [
            'labels' => array_map(static fn (string $m) => \IntlDateFormatter::formatObject(new \DateTimeImmutable($m.'-01'), 'MMM yy', 'fr'), array_keys($mois)),
            'series' => [['label' => 'Demandes', 'data' => array_values($mois)]],
        ];
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
        yield MenuItem::section('Blog');
        yield MenuItem::linkTo(ArticleCrudController::class, 'Articles', 'fa fa-newspaper');
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
