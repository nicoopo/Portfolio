<?php

namespace App\Controller\Admin;

use App\Entity\Creneau;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

/** Créneaux d'entretien proposés aux recruteurs (page /rendez-vous de leur lien) ; la réservation remplit le reste. */
final class CreneauCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Creneau::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Créneau')
            ->setEntityLabelInPlural('Créneaux d’entretien')
            ->setDefaultSort(['debut' => 'DESC'])
            ->setHelp('index', 'Chaque créneau dure une heure (heure de Paris). Un recruteur le réserve depuis son lien : l’entretien s’inscrit sur sa candidature et une alerte part sur le téléphone. Seuls les créneaux à plus de 12 heures sont proposés.');
    }

    public function configureFields(string $pageName): iterable
    {
        yield DateTimeField::new('debut', 'Début');
        yield AssociationField::new('lien', 'Réservé par')->hideOnForm()
            ->formatValue(fn ($lien) => $lien ?? 'libre');
        yield TextField::new('contactNom', 'Contact')->hideOnForm();
        yield EmailField::new('contactEmail', 'E-mail du contact')->hideOnForm();
    }
}
