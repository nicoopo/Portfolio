<?php

namespace App\Controller\Admin;

use App\Entity\DemandeContact;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;

/** Messages du formulaire de contact : lecture et suppression seulement (on ne réécrit pas un message reçu). */
final class DemandeContactCrudController extends AbstractCrudController
{
    private const STATUTS = [
        'Envoyé par e-mail' => DemandeContact::STATUT_ENVOYE,
        'E-mail en échec' => DemandeContact::STATUT_ECHEC,
        'En cours' => DemandeContact::STATUT_EN_COURS,
    ];

    public static function getEntityFqcn(): string
    {
        return DemandeContact::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Demande de contact')
            ->setEntityLabelInPlural('Demandes de contact')
            ->setDefaultSort(['recuLe' => 'DESC'])
            ->setHelp('index', 'Données personnelles : supprimez une demande si son auteur le demande.');
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->disable(Action::NEW, Action::EDIT)
            ->add(Crud::PAGE_INDEX, Action::DETAIL);
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters->add(ChoiceFilter::new('statut')->setChoices(self::STATUTS));
    }

    public function configureFields(string $pageName): iterable
    {
        yield DateTimeField::new('recuLe', 'Reçu le');
        yield TextField::new('nom');
        yield EmailField::new('email');
        yield TextareaField::new('message')->onlyOnDetail();
        yield TextField::new('message', 'Aperçu')->onlyOnIndex()->setMaxLength(60);
        yield TextField::new('langue');
        yield ChoiceField::new('statut')->setChoices(self::STATUTS)->renderAsBadges([
            DemandeContact::STATUT_ENVOYE => 'success',
            DemandeContact::STATUT_ECHEC => 'danger',
            DemandeContact::STATUT_EN_COURS => 'secondary',
        ]);
    }
}
