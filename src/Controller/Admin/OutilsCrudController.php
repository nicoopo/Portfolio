<?php

namespace App\Controller\Admin;

use App\Entity\Outils;
use App\Form\TraductionsField;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;

/** Page /uses : une seule ligne, qu'on modifie (ni création ni suppression). */
final class OutilsCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Outils::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud->setEntityLabelInSingular('Mes outils')->setEntityLabelInPlural('Mes outils');
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions->disable(Action::NEW, Action::DELETE, Action::BATCH_DELETE);
    }

    public function configureFields(string $pageName): iterable
    {
        yield TextareaField::new('contenu')
            ->setHelp('Markdown, une rubrique par intertitre : ## Matériel, ## Logiciels, ## Stack… La date de mise à jour de la page change à chaque enregistrement.')
            ->setNumOfRows(16)->hideOnIndex();
        yield DateTimeField::new('majLe', 'Mis à jour le')->hideOnForm();

        // Autres langues du site (framework.enabled_locales) : vide = le français s'affiche
        yield FormField::addFieldset('Traductions (facultatif)')->collapsible()->renderCollapsed();
        yield TraductionsField::new()->champs(['contenu' => 'Contenu (Markdown)'], ['contenu']);
    }
}
