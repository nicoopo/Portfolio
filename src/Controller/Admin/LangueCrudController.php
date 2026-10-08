<?php

namespace App\Controller\Admin;

use App\Entity\Langue;
use App\Form\TraductionsField;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

final class LangueCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Langue::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Langue')
            ->setEntityLabelInPlural('Langues')
            ->setDefaultSort(['position' => 'ASC']);
    }

    public function configureFields(string $pageName): iterable
    {
        yield TextField::new('nom');
        yield TextField::new('niveau')->setHelp('Ex. « Courant », « B1 »');
        yield IntegerField::new('position')->setHelp("Ordre d'affichage (croissant)");

        // Autres langues du site (framework.enabled_locales) : vide = le français s'affiche
        yield FormField::addFieldset('Traductions (facultatif)')->collapsible()->renderCollapsed();
        yield TraductionsField::new()->champs(['nom' => 'Nom', 'niveau' => 'Niveau']);
    }
}
