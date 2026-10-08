<?php

namespace App\Controller\Admin;

use App\Entity\Passion;
use App\Form\TraductionsField;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ColorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;

final class PassionCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Passion::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Passion')
            ->setEntityLabelInPlural('Passions')
            ->setDefaultSort(['position' => 'ASC'])
            ->setHelp('index', 'Chaque passion est une nébuleuse autour du cerveau.');
    }

    public function configureFields(string $pageName): iterable
    {
        yield TextField::new('nom');
        yield ColorField::new('couleur')->setHelp('Couleur de la coquille de la nébuleuse');
        yield TextareaField::new('description');
        yield IntegerField::new('position')->setHelp('Ordre d\'affichage (croissant)');

        // Autres langues du site (framework.enabled_locales) : vide = le français s'affiche
        yield FormField::addFieldset('Traductions (facultatif)')->collapsible()->renderCollapsed();
        yield TraductionsField::new()->champs(['nom' => 'Nom', 'description' => 'Description'], ['description']);
    }
}
