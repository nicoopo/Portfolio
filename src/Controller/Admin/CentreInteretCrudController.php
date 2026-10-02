<?php

namespace App\Controller\Admin;

use App\Entity\CentreInteret;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

final class CentreInteretCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return CentreInteret::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular("Centre d'intérêt")
            ->setEntityLabelInPlural("Centres d'intérêt")
            ->setDefaultSort(['position' => 'ASC']);
    }

    public function configureFields(string $pageName): iterable
    {
        yield TextField::new('texte')->setHelp('Un emoji en tête est le bienvenu : « 🎵 Musique… »');
        yield IntegerField::new('position')->setHelp("Ordre d'affichage (croissant)");

        // Version anglaise du site : vide = le français s'affiche
        yield FormField::addFieldset('Anglais (facultatif)')->collapsible()->renderCollapsed();
        yield TextField::new('texteEn', 'Texte')->hideOnIndex();
    }
}
