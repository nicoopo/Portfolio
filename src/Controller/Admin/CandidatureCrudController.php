<?php

namespace App\Controller\Admin;

use App\Entity\Candidature;
use App\Entity\StatutCandidature;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\UrlField;

/** Candidatures d'alternance : triées par prochaine relance ; « Site ouvert » vient du lien recruteur associé. */
final class CandidatureCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Candidature::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Candidature')
            ->setEntityLabelInPlural('Candidatures')
            ->setDefaultSort(['relancerLe' => 'ASC', 'envoyeeLe' => 'DESC'])
            ->setHelp('index', 'Une candidature sans réponse est à relancer '.Candidature::RELANCE_JOURS.' jours après l’envoi ; passez-la en « Relancée » une fois fait, la date suivante se calcule seule.');
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters->add('statut');
    }

    public function configureFields(string $pageName): iterable
    {
        yield TextField::new('entreprise');
        yield TextField::new('poste');
        yield ChoiceField::new('statut')
            ->setChoices(array_combine(array_map(fn (StatutCandidature $s) => $s->libelle(), StatutCandidature::cases()), StatutCandidature::cases()))
            ->setFormTypeOption('choice_value', fn (?StatutCandidature $s) => $s?->value) // « envoyee » dans le formulaire, pas un numéro
            ->renderAsBadges([
                StatutCandidature::Envoyee->value => 'secondary',
                StatutCandidature::Relancee->value => 'info',
                StatutCandidature::Entretien->value => 'warning',
                StatutCandidature::Acceptee->value => 'success',
                StatutCandidature::Refusee->value => 'danger',
            ]);
        yield DateField::new('envoyeeLe', 'Envoyée le');
        yield DateField::new('relancerLe', 'À relancer le')->hideOnForm()
            ->formatValue(fn ($date, Candidature $candidature) => $date
                ? ($candidature->aRelancer() ? '⚠ ' : '').$date->format('d/m/Y')
                : '—');
        yield AssociationField::new('lien', 'Lien recruteur')->hideOnIndex()
            ->setHelp('Le lien envoyé avec cette candidature (Visiteurs → Liens recruteur)');
        yield TextField::new('lien', 'Site ouvert')->onlyOnIndex()
            ->formatValue(fn ($lien) => $lien ? ($lien->getVisites() > 0 ? 'oui ('.$lien->getVisites().')' : 'pas encore') : '—');
        yield UrlField::new('annonce')->hideOnIndex();
        yield TextareaField::new('notes')->hideOnIndex();
    }
}
