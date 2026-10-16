<?php

namespace App\Controller\Admin;

use App\Entity\Competence;
use App\Form\TraductionsField;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

final class CompetenceCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Competence::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Compétence')
            ->setEntityLabelInPlural('Compétences')
            ->setDefaultSort(['position' => 'ASC'])
            ->setHelp('index', 'Chaque compétence est un neurone du cerveau. Son nom doit être unique, aussi parmi les passions et les étapes du parcours.');
    }

    public function configureFields(string $pageName): iterable
    {
        yield TextField::new('nom');
        yield AssociationField::new('categorie', 'Catégorie');
        yield IntegerField::new('position')->setHelp('Ordre d\'affichage (croissant)');
        // Les projets se relient depuis l'écran Projets (côté propriétaire de la relation)
        yield AssociationField::new('projets')->onlyOnIndex();

        // Autres langues du site (framework.enabled_locales) : vide = le français s'affiche
        yield FormField::addFieldset('Traductions (facultatif)')->collapsible()->renderCollapsed();
        yield TraductionsField::new()->champs(['nom' => 'Nom']);
    }
}
