<?php

namespace App\Controller\Admin;

use App\Entity\Projet;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

final class ProjetCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Projet::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Projet')
            ->setEntityLabelInPlural('Projets')
            ->setDefaultSort(['position' => 'ASC']);
    }

    public function configureFields(string $pageName): iterable
    {
        yield TextField::new('titre');
        yield TextField::new('slug')->setHelp('Ancre de la carte : /projects#slug (minuscules, chiffres, tirets)')->hideOnIndex();
        yield TextareaField::new('description')->hideOnIndex();
        yield TextField::new('tech', 'Badge')->setHelp('Technos affichées sous la description');
        yield TextField::new('image')->setHelp('Chemin sous assets/images/projets/, ex. symfony/portfolio-cerveau.jpg')->hideOnIndex();
        yield TextField::new('categorie', 'Groupe')->setHelp('Ex. « PHP / Symfony » : regroupe les projets sur la page Projets');
        yield AssociationField::new('competences', 'Compétences utilisées')
            ->setFormTypeOption('by_reference', false); // passe par addCompetence / removeCompetence
        yield IntegerField::new('position')->setHelp('Ordre d\'affichage (croissant)');

        // Version anglaise du site : vide = le français s'affiche
        yield FormField::addFieldset('Anglais (facultatif)')->collapsible()->renderCollapsed();
        yield TextField::new('titreEn', 'Titre')->hideOnIndex();
        yield TextareaField::new('descriptionEn', 'Description')->hideOnIndex();
        yield TextField::new('categorieEn', 'Groupe')->hideOnIndex();
    }
}
