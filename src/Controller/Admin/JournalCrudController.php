<?php

namespace App\Controller\Admin;

use App\Entity\Journal;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;

/** Journal : lecture seule (un journal modifiable ne prouverait rien). */
final class JournalCrudController extends AbstractCrudController
{
    private const TYPES = [
        'Connexion' => Journal::CONNEXION,
        'Connexion refusée' => Journal::CONNEXION_REFUSEE,
        'Déconnexion' => Journal::DECONNEXION,
        'Création' => Journal::CREATION,
        'Modification' => Journal::MODIFICATION,
        'Suppression' => Journal::SUPPRESSION,
        'Contact envoyé' => Journal::CONTACT_ENVOYE,
        'Contact en échec' => Journal::CONTACT_ECHEC,
        'Robot bloqué' => Journal::SPAM_BLOQUE,
    ];

    public static function getEntityFqcn(): string
    {
        return Journal::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Entrée du journal')
            ->setEntityLabelInPlural('Journal')
            ->setDefaultSort(['date' => 'DESC'])
            ->setPaginatorPageSize(50)
            ->setHelp('index', 'Connexions, modifications du contenu et formulaire de contact. Les entrées de plus de 12 mois sont supprimées par `make journal-purge`.');
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions->disable(Action::NEW, Action::EDIT, Action::DELETE, Action::BATCH_DELETE);
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(ChoiceFilter::new('type')->setChoices(self::TYPES))
            ->add(DateTimeFilter::new('date'));
    }

    public function configureFields(string $pageName): iterable
    {
        yield DateTimeField::new('date')->setFormat('dd/MM/yyyy HH:mm:ss');
        yield ChoiceField::new('type')->setChoices(self::TYPES)->renderAsBadges([
            Journal::CONNEXION_REFUSEE => 'danger',
            Journal::CONTACT_ECHEC => 'danger',
            Journal::SUPPRESSION => 'warning',
            Journal::SPAM_BLOQUE => 'secondary',
        ]);
        yield TextField::new('message');
        yield TextField::new('utilisateur', 'Compte');
        yield TextField::new('ip', 'IP');
    }
}
