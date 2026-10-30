<?php

namespace App\Controller;

use App\Entity\Creneau;
use App\Entity\LienRecruteur;
use App\Entity\StatutCandidature;
use App\Repository\CandidatureRepository;
use App\Repository\CreneauRepository;
use App\Repository\LienRecruteurRepository;
use App\Service\Agenda;
use App\Service\Notificateur;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Un recruteur réserve un créneau d'entretien depuis son lien (/rendez-vous?pour=code) : un seul par lien,
 * parmi ceux ouverts dans l'administration à plus de 12 heures. L'entretien s'inscrit sur la candidature liée.
 */
final class RendezVousController extends AbstractController
{
    #[Route('/rendez-vous', name: 'app_rendez_vous')]
    public function __invoke(Request $request, LienRecruteurRepository $liens, CreneauRepository $creneaux, CandidatureRepository $candidatures, EntityManagerInterface $entityManager, Notificateur $notificateur, TranslatorInterface $translator): Response
    {
        $lien = $this->lien($request, $liens);
        $reserve = $creneaux->reservePar($lien);
        // « jeudi 15 octobre 2026 à 14:00 » dans la langue de la page ; UTC : l'heure saisie (Paris) s'affiche telle quelle
        $date = \IntlDateFormatter::create($request->getLocale(), \IntlDateFormatter::FULL, \IntlDateFormatter::SHORT, 'UTC');
        $libres = $reserve ? [] : $creneaux->libres();

        $form = $this->createFormBuilder()
            ->add('creneau', ChoiceType::class, [
                'label' => 'Créneau',
                'choices' => $libres,
                'choice_value' => fn (?Creneau $c) => $c?->getId(),
                'choice_label' => fn (Creneau $c) => $date->format($c->getDebut()),
                'choice_translation_domain' => false,
                'expanded' => true,
                'constraints' => [new Assert\NotNull()],
            ])
            ->add('nom', TextType::class, ['label' => 'Nom', 'constraints' => [new Assert\NotBlank(), new Assert\Length(max: 100)]])
            ->add('email', EmailType::class, ['label' => 'E-mail', 'constraints' => [new Assert\NotBlank(), new Assert\Email(), new Assert\Length(max: 180)]])
            ->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var Creneau $creneau */
            ['creneau' => $creneau, 'nom' => $nom, 'email' => $email] = $form->getData();
            if (!$creneaux->reserver($creneau, $lien, trim($nom), $email)) {
                // Refusé parce que ce lien vient de réserver (double-clic) : pas d'erreur, la page montre sa réservation
                if (!$creneaux->reservePar($lien)) {
                    $this->addFlash('error', $translator->trans('Ce créneau vient d’être pris : choisissez-en un autre.'));
                }

                return $this->redirectToRoute('app_rendez_vous', ['pour' => $lien->getCode()]);
            }

            $candidature = $candidatures->findOneBy(['lien' => $lien], ['envoyeeLe' => 'DESC']);
            if ($candidature) {
                $candidature->setEntretienLe($creneau->getDebut());
                $candidature->getEmail() ?? $candidature->setEmail($email);
                if ($candidature->getStatut()->enAttente()) {
                    $candidature->setStatut(StatutCandidature::Entretien);
                }
                $entityManager->flush();
            }
            $notificateur->prevenir('Entretien réservé', $lien->getEntreprise().' ('.trim($nom).', '.$email.') : '.$creneau->getDebut()->format('d/m/Y à H:i'), 'calendar');

            return $this->redirectToRoute('app_rendez_vous', ['pour' => $lien->getCode()]);
        }

        return $this->render('rendez_vous/index.html.twig', [
            'lien' => $lien,
            'reserve' => $reserve,
            'reserve_le' => $reserve ? $date->format($reserve->getDebut()) : null,
            'libres' => $libres,
            'form' => $form,
        ]);
    }

    /** Le créneau réservé, pour l'agenda du recruteur ; seulement avec le lien qui l'a réservé */
    #[Route('/rendez-vous/{id}.ics', name: 'app_rendez_vous_ics', requirements: ['id' => '\d+'])]
    public function ics(int $id, Request $request, LienRecruteurRepository $liens, CreneauRepository $creneaux, Agenda $agenda): Response
    {
        $lien = $this->lien($request, $liens);
        $creneau = $creneaux->find($id);
        if (!$creneau || $creneau->getLien() !== $lien) {
            throw $this->createNotFoundException();
        }

        return new Response($agenda->ics('creneau-'.$id, $creneau->getDebut(), 'Entretien avec Nicolas Cataluna', 'https://nicolascataluna.fr — '.$this->getParameter('app.contact_email')), Response::HTTP_OK, [
            'Content-Type' => 'text/calendar; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="entretien-nicolas-cataluna.ics"',
        ]);
    }

    private function lien(Request $request, LienRecruteurRepository $liens): LienRecruteur
    {
        return $liens->parCode($request->query->getString('pour')) ?? throw $this->createNotFoundException();
    }
}
