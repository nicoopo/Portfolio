<?php

namespace App\Controller\Admin;

use App\Entity\Candidature;
use App\Entity\StatutCandidature;
use App\Repository\CvProfilRepository;
use Doctrine\ORM\EntityManagerInterface;
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
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
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
        $export = Action::new('exporter', 'Exporter (CSV)', 'fa fa-file-csv')->linkToCrudAction('exporter')->createAsGlobalAction();
        $agenda = Action::new('agenda', 'Agenda (.ics)', 'fa fa-calendar-plus')->linkToCrudAction('agenda')
            ->displayIf(fn (Candidature $c) => null !== $c->getEntretienLe());
        $relancer = Action::new('relancer', 'Relancer', 'fa fa-paper-plane')->linkToCrudAction('relancer')
            ->displayIf(fn (Candidature $c) => null !== $c->getEmail() && $c->getStatut()->enAttente());

        foreach ([Crud::PAGE_INDEX, Crud::PAGE_EDIT] as $page) {
            $actions->add($page, $lettre)->add($page, $agenda)->add($page, $relancer);
        }

        return $actions->add(Crud::PAGE_INDEX, $export);
    }

    /** Entretien en .ics : s'ouvre dans l'agenda (heure de Paris, une heure par défaut) */
    #[AdminRoute('/{id}/entretien.ics')]
    public function agenda(Candidature $candidature): Response
    {
        $debut = $candidature->getEntretienLe() ?? throw $this->createNotFoundException('Pas de date d’entretien.');
        $texte = fn (string $t) => str_replace(["\\", ';', ',', "\r\n", "\n"], ['\\\\', '\\;', '\\,', '\\n', '\\n'], $t); // RFC 5545
        $ics = implode("\r\n", [
            'BEGIN:VCALENDAR', 'VERSION:2.0', 'PRODID:-//nicolascataluna.fr//portfolio//FR', 'BEGIN:VEVENT',
            'UID:candidature-'.$candidature->getId().'@nicolascataluna.fr',
            'DTSTAMP:'.gmdate('Ymd\THis\Z'),
            'DTSTART;TZID=Europe/Paris:'.$debut->format('Ymd\THis'),
            'DTEND;TZID=Europe/Paris:'.$debut->modify('+1 hour')->format('Ymd\THis'),
            'SUMMARY:'.$texte('Entretien '.$candidature->getEntreprise().' — '.$candidature->getPoste()),
            'DESCRIPTION:'.$texte(trim(($candidature->getAnnonce() ?? '')."\n".($candidature->getNotes() ?? ''))),
            'BEGIN:VALARM', 'TRIGGER:-PT1H', 'ACTION:DISPLAY', 'DESCRIPTION:Entretien dans une heure', 'END:VALARM',
            'END:VEVENT', 'END:VCALENDAR', '',
        ]);

        return new Response($ics, Response::HTTP_OK, [
            'Content-Type' => 'text/calendar; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="entretien-'.(new AsciiSlugger())->slug($candidature->getEntreprise())->lower().'.ics"',
        ]);
    }

    /** Passe la candidature en « Relancée » et ouvre la messagerie avec la relance déjà écrite */
    #[AdminRoute('/{id}/relancer')]
    public function relancer(Candidature $candidature, EntityManagerInterface $entityManager): Response
    {
        if (null === $candidature->getEmail()) {
            throw $this->createNotFoundException('Pas d’e-mail de recruteur.');
        }
        $lien = $candidature->getLien()
            ? "\n\nVous pouvez retrouver mon portfolio et mon CV ici : ".$this->generateUrl('app_home', ['pour' => $candidature->getLien()->getCode(), '_locale' => 'fr'], UrlGeneratorInterface::ABSOLUTE_URL)
            : '';
        $corps = "Bonjour,\n\nLe ".$candidature->getEnvoyeeLe()->format('d/m/Y').', je vous ai adressé ma candidature pour le poste de '
            .$candidature->getPoste()." en alternance. Je me permets de revenir vers vous : ce poste m'intéresse toujours beaucoup, "
            .'et je serais ravi d’échanger avec vous à ce sujet.'.$lien."\n\nCordialement,\nNicolas Cataluna";

        $candidature->setStatut(StatutCandidature::Relancee);
        $entityManager->flush();

        return $this->redirect('mailto:'.rawurlencode($candidature->getEmail())
            .'?subject='.rawurlencode('Relance — candidature '.$candidature->getPoste()).'&body='.rawurlencode($corps));
    }

    /** Toutes les candidatures, pour un tableur : séparateur « ; » et BOM UTF-8, comme Excel les attend en français */
    #[AdminRoute('/export')]
    public function exporter(EntityManagerInterface $entityManager): Response
    {
        $csv = fopen('php://temp', 'r+');
        fwrite($csv, "\u{FEFF}");
        fputcsv($csv, ['Entreprise', 'Poste', 'Statut', 'Envoyée le', 'Réponse le', 'À relancer le', 'Site ouvert', 'Annonce', 'Notes'], ';', escape: '');
        $date = fn (?\DateTimeImmutable $d) => $d?->format('d/m/Y') ?? '';
        foreach ($entityManager->getRepository(Candidature::class)->findBy([], ['envoyeeLe' => 'DESC']) as $c) {
            fputcsv($csv, [
                $c->getEntreprise(), $c->getPoste(), $c->getStatut()->libelle(), $date($c->getEnvoyeeLe()), $date($c->getReponseLe()),
                $date($c->getRelancerLe()), $c->getLien() ? $c->getLien()->getVisites() : '', $c->getAnnonce(), $c->getNotes(),
            ], ';', escape: '');
        }
        rewind($csv);

        return new Response(stream_get_contents($csv), Response::HTTP_OK, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="candidatures_'.date('Y-m-d').'.csv"',
        ]);
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
        yield EmailField::new('email', 'E-mail du recruteur')->hideOnIndex()
            ->setHelp('Pour le bouton « Relancer » : votre messagerie s’ouvre avec la relance déjà écrite');
        yield DateTimeField::new('entretienLe', 'Entretien le')
            ->setHelp('Heure de Paris : rappel sur le téléphone la veille, et bouton « Agenda (.ics) »');
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
