<?php

namespace App\Controller\Admin;

use App\Entity\Experience;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\UrlField;

final class ExperienceCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Experience::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Expérience')
            ->setEntityLabelInPlural('Expériences')
            ->setDefaultSort(['position' => 'ASC']);
    }

    public function configureFields(string $pageName): iterable
    {
        yield TextField::new('poste');
        yield TextField::new('entreprise');
        yield TextField::new('lieu')->hideOnIndex();
        yield TextField::new('periode', 'Période')->setHelp('Ex. « 2023 - 2024 »');
        yield TextField::new('contrat')->setHelp("Ex. « Contrat d'apprentissage », « Stage »");
        yield TextareaField::new('missions')->setHelp('Une mission par ligne')->hideOnIndex();
        yield UrlField::new('site')->setHelp("Facultatif : site de l'entreprise ou du produit auquel j'ai contribué (lien sur le CV)")->hideOnIndex();
        yield IntegerField::new('position')->setHelp("Ordre d'affichage (croissant)");

        // Version anglaise du site : vide = le français s'affiche
        yield FormField::addFieldset('Anglais (facultatif)')->collapsible()->renderCollapsed();
        yield TextField::new('posteEn', 'Poste')->hideOnIndex();
        yield TextField::new('periodeEn', 'Période')->hideOnIndex();
        yield TextField::new('contratEn', 'Contrat')->hideOnIndex();
        yield TextareaField::new('missionsEn', 'Missions')->hideOnIndex();
    }
}
