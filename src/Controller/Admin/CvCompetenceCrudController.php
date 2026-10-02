<?php

namespace App\Controller\Admin;

use App\Entity\CvCompetence;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

final class CvCompetenceCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return CvCompetence::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Bloc de compétences')
            ->setEntityLabelInPlural('Compétences du CV')
            ->setDefaultSort(['position' => 'ASC'])
            ->setHelp('index', 'Regroupements propres au CV, distincts des neurones du cerveau (menu « Compétences »).');
    }

    public function configureFields(string $pageName): iterable
    {
        yield TextField::new('titre');
        yield TextareaField::new('elements', 'Éléments')->setHelp('Séparés par des virgules');
        yield IntegerField::new('position')->setHelp("Ordre d'affichage (croissant)");

        // Version anglaise du site : vide = le français s'affiche
        yield FormField::addFieldset('Anglais (facultatif)')->collapsible()->renderCollapsed();
        yield TextField::new('titreEn', 'Titre')->hideOnIndex();
        yield TextareaField::new('elementsEn', 'Éléments')->hideOnIndex();
    }
}
