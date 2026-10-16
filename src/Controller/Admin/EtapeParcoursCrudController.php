<?php

namespace App\Controller\Admin;

use App\Entity\EtapeParcours;
use App\Form\TraductionsField;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

final class EtapeParcoursCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return EtapeParcours::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Étape du parcours')
            ->setEntityLabelInPlural('Parcours')
            ->setDefaultSort(['position' => 'ASC'])
            ->setHelp('index', 'Frise de la page Univers et souvenirs du cerveau. Position 1 = la plus récente.');
    }

    public function configureFields(string $pageName): iterable
    {
        yield TextField::new('nom')->setHelp('Nom court, affiché dans le cerveau (ex. « BTS SIO »)');
        yield TextField::new('dates')->setHelp('Ex. « 2022 - 2024 »');
        yield TextField::new('intitule', 'Intitulé');
        yield TextField::new('specialite', 'Option')->hideOnIndex();
        yield TextField::new('ecole', 'École');
        yield TextField::new('lieu')->hideOnIndex();
        yield TextField::new('resultat', 'Résultat');
        yield IntegerField::new('position');

        // Autres langues du site (framework.enabled_locales) : vide = le français s'affiche
        yield FormField::addFieldset('Traductions (facultatif)')->collapsible()->renderCollapsed();
        yield TraductionsField::new()->champs(['nom' => 'Nom', 'intitule' => 'Intitulé', 'specialite' => 'Option', 'resultat' => 'Résultat']);
    }
}
