<?php

namespace App\Controller;

use App\Entity\Journal;
use App\Entity\MessageLivreOr;
use App\Form\LivreOrType;
use App\Service\Journaliste;
use App\Service\Notificateur;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\RateLimiter\RateLimiterFactoryInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Livre d'or : chaque message approuvé dans l'administration devient une étoile du ciel de la page.
 * Anti-spam : champ piège (comme Contact) et limite par adresse IP (config/packages/rate_limiter.yaml).
 */
final class LivreOrController extends AbstractController
{
    #[Route('/livre-d-or', name: 'app_livre_or')]
    public function __invoke(
        Request $request,
        EntityManagerInterface $entityManager,
        TranslatorInterface $translator,
        Journaliste $journaliste,
        RateLimiterFactoryInterface $livreOrLimiter,
        Notificateur $notificateur,
    ): Response {
        $form = $this->createForm(LivreOrType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            if ($data['website']) {
                $journaliste->noter(Journal::SPAM_BLOQUE, 'Robot bloqué par le champ piège du livre d’or');
            } elseif (!$livreOrLimiter->create($request->getClientIp())->consume()->isAccepted()) {
                $this->addFlash('error', $translator->trans('Beaucoup de messages d’un coup : réessayez dans une heure.'));

                return $this->redirectToRoute('app_livre_or', ['_fragment' => 'signer']);
            } else {
                $entityManager->persist(new MessageLivreOr(trim($data['prenom']), trim($data['message']), $request->getLocale()));
                $entityManager->flush();
                $notificateur->prevenir('Livre d’or : nouveau message à modérer', trim($data['prenom']).' : '.trim($data['message']), 'star');
            }
            // Même réponse pour un robot : il ne sait pas qu'il a été repéré
            $this->addFlash('success', $translator->trans('Merci ! Votre étoile apparaîtra dans le ciel dès que je l’aurai lue.'));

            return $this->redirectToRoute('app_livre_or', ['_fragment' => 'signer']);
        }

        return $this->render('livre_or/index.html.twig', [
            'form' => $form,
            'messages' => $entityManager->getRepository(MessageLivreOr::class)->findBy(['approuve' => true], ['creeLe' => 'DESC']),
        ]);
    }
}
