<?php

namespace App\Controller\Admin;

use App\Entity\CvCompetence;
use App\Form\TraductionsField;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;

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

        // Autres langues du site (framework.enabled_locales) : vide = le français s'affiche
        yield FormField::addFieldset('Traductions (facultatif)')->collapsible()->renderCollapsed();
        yield TraductionsField::new()->champs(['titre' => 'Titre', 'elements' => 'Éléments'], ['elements']);
    }
}
