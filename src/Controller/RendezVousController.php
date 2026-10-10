<?php

namespace App\Controller;

use App\Entity\Candidature;
use App\Entity\Creneau;
use App\Entity\LienRecruteur;
use App\Entity\StatutCandidature;
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
    public function __invoke(Request $request, EntityManagerInterface $entityManager, Notificateur $notificateur, TranslatorInterface $translator): Response
    {
        $lien = $this->lien($request, $entityManager);
        $creneaux = $entityManager->getRepository(Creneau::class);
        $reserve = $creneaux->createQueryBuilder('c')->where('c.lien = :lien AND c.debut >= :aujourdhui')->orderBy('c.debut')->setMaxResults(1)
            ->setParameter('lien', $lien)->setParameter('aujourdhui', new \DateTimeImmutable('today'))->getQuery()->getOneOrNullResult();
        // « jeudi 15 octobre 2026 à 14:00 » dans la langue de la page ; UTC : l'heure saisie (Paris) s'affiche telle quelle
        $date = \IntlDateFormatter::create($request->getLocale(), \IntlDateFormatter::FULL, \IntlDateFormatter::SHORT, 'UTC');
        $libres = $reserve ? [] : $creneaux->createQueryBuilder('c')->where('c.lien IS NULL AND c.debut > :limite')->orderBy('c.debut')
            ->setParameter('limite', new \DateTimeImmutable('+12 hours'))->getQuery()->getResult();

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
            // Une seule requête, conditionnée à « encore libre » : deux recruteurs ne prennent jamais le même créneau
            $pris = $entityManager->getConnection()->executeStatement(
                'UPDATE creneau SET lien_id = :lien, contact_nom = :nom, contact_email = :email WHERE id = :id AND lien_id IS NULL',
                ['lien' => $lien->getId(), 'nom' => trim($nom), 'email' => $email, 'id' => $creneau->getId()],
            );
            if (1 !== $pris) {
                $this->addFlash('error', $translator->trans('Ce créneau vient d’être pris : choisissez-en un autre.'));

                return $this->redirectToRoute('app_rendez_vous', ['pour' => $lien->getCode()]);
            }

            $candidature = $entityManager->getRepository(Candidature::class)->findOneBy(['lien' => $lien], ['envoyeeLe' => 'DESC']);
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
    public function ics(int $id, Request $request, EntityManagerInterface $entityManager, Agenda $agenda): Response
    {
        $lien = $this->lien($request, $entityManager);
        $creneau = $entityManager->find(Creneau::class, $id);
        if (!$creneau || $creneau->getLien() !== $lien) {
            throw $this->createNotFoundException();
        }

        return new Response($agenda->ics('creneau-'.$id, $creneau->getDebut(), 'Entretien avec Nicolas Cataluna', 'https://nicolascataluna.fr — nicolas.cataluna@proton.me'), Response::HTTP_OK, [
            'Content-Type' => 'text/calendar; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="entretien-nicolas-cataluna.ics"',
        ]);
    }

    private function lien(Request $request, EntityManagerInterface $entityManager): LienRecruteur
    {
        $code = $request->query->getString('pour');

        return ('' !== $code ? $entityManager->getRepository(LienRecruteur::class)->findOneBy(['code' => $code]) : null)
            ?? throw $this->createNotFoundException();
    }
}
