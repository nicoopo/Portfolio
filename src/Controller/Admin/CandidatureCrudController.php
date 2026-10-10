<?php

namespace App\Controller\Admin;

use App\Entity\Candidature;
use App\Entity\StatutCandidature;
use App\Repository\CvProfilRepository;
use Dompdf\Dompdf;
use Dompdf\Options;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminRoute;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\UrlField;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\String\Slugger\AsciiSlugger;

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

    public function configureActions(Actions $actions): Actions
    {
        $lettre = Action::new('lettre', 'Lettre PDF', 'fa fa-file-pdf')->linkToCrudAction('lettre');

        return $actions->add(Crud::PAGE_INDEX, $lettre)->add(Crud::PAGE_EDIT, $lettre);
    }

    /** Lettre de motivation au nom de l'entreprise et du poste, avec le lien recruteur s'il y en a un (templates/admin/lettre.html.twig) */
    #[AdminRoute('/{id}/lettre')]
    public function lettre(Candidature $candidature, CvProfilRepository $profils): Response
    {
        $lien = $candidature->getLien()
            ? $this->generateUrl('app_home', ['pour' => $candidature->getLien()->getCode(), '_locale' => 'fr'], UrlGeneratorInterface::ABSOLUTE_URL)
            : null;

        $options = new Options();
        $options->set('defaultFont', 'DejaVu Sans');
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($this->renderView('admin/lettre.html.twig', [
            'candidature' => $candidature,
            'profil' => $profils->findOneBy([]) ?? throw $this->createNotFoundException('Profil du CV absent.'),
            'lien' => $lien,
        ]));
        $dompdf->setPaper('A4');
        $dompdf->render();

        return new Response($dompdf->output(), Response::HTTP_OK, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="Lettre_Nicolas_Cataluna_'.(new AsciiSlugger())->slug($candidature->getEntreprise()).'.pdf"',
        ]);
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
