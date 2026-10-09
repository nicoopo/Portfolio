<?php

namespace App\Controller\Admin;

use App\Entity\MessageLivreOr;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

/** Livre d'or : modération. Un message n'apparaît sur le site qu'une fois « Approuvé » coché (interrupteur de la liste). */
final class MessageLivreOrCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return MessageLivreOr::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Message du livre d’or')
            ->setEntityLabelInPlural('Livre d’or')
            ->setDefaultSort(['approuve' => 'ASC', 'creeLe' => 'DESC']); // à modérer d'abord
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions->disable(Action::NEW, Action::EDIT);
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters->add('approuve');
    }

    public function configureFields(string $pageName): iterable
    {
        yield DateTimeField::new('creeLe', 'Reçu le');
        yield TextField::new('prenom', 'Prénom');
        yield TextField::new('message');
        yield TextField::new('langue');
        yield BooleanField::new('approuve', 'Approuvé'); // interrupteur cliquable dans la liste
    }
}
