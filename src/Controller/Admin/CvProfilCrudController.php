<?php

namespace App\Controller\Admin;

use App\Entity\CvProfil;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

/** En-tête et profil du CV : une seule ligne, qu'on modifie (ni création ni suppression). */
final class CvProfilCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return CvProfil::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud->setEntityLabelInSingular('Profil du CV')->setEntityLabelInPlural('Profil du CV');
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions->disable(Action::NEW, Action::DELETE, Action::BATCH_DELETE);
    }

    public function configureFields(string $pageName): iterable
    {
        yield TextField::new('titre')->setHelp('Ex. « Développeur Fullstack »');
        yield TextField::new('qualites', 'Qualités')->setHelp('Séparées par des virgules : « Curieux, Rigoureux, Autonome »');
        yield TextareaField::new('resume', 'Résumé')->hideOnIndex();
        yield TextareaField::new('accroche')->setHelp('Mise en avant sous le résumé, une ligne par phrase')->hideOnIndex();
        yield TextField::new('telephone', 'Téléphone');
        yield EmailField::new('email');

        // Version anglaise du site : vide = le français s'affiche
        yield FormField::addFieldset('Anglais (facultatif)')->collapsible()->renderCollapsed();
        yield TextField::new('titreEn', 'Titre')->hideOnIndex();
        yield TextField::new('qualitesEn', 'Qualités')->hideOnIndex();
        yield TextareaField::new('resumeEn', 'Résumé')->hideOnIndex();
        yield TextareaField::new('accrocheEn', 'Accroche')->hideOnIndex();
    }
}
