<?php

namespace App\Controller;

use App\Entity\DemandeContact;
use App\Form\ContactType;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

final class ContactController extends AbstractController
{
    #[Route('/contact', name: 'app_contact')]
    public function index(
        Request $request,
        MailerInterface $mailer,
        EntityManagerInterface $entityManager,
        LoggerInterface $logger,
        TranslatorInterface $translator,
        #[Autowire('%app.contact_email%')] string $contactEmail,
    ): Response {
        $form = $this->createForm(ContactType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            // Piège rempli : un robot. On fait comme si l'envoi avait réussi, sans rien enregistrer ni envoyer.
            if (!$data['website']) {
                // Enregistré d'abord : si l'e-mail ne part pas, le message reste dans l'administration
                $demande = new DemandeContact($data['nom'], $data['email'], $data['message'], $request->getLocale());
                $entityManager->persist($demande);
                $entityManager->flush();

                try {
                    $mailer->send((new Email())
                        ->from(new Address($contactEmail, 'Portfolio'))
                        ->to($contactEmail)
                        ->replyTo(new Address($data['email'], $data['nom']))
                        ->subject('Contact portfolio : '.$data['nom'])
                        ->text($data['message']."\n\n— ".$data['nom'].' <'.$data['email'].'>'));
                    $demande->marquerEnvoye();
                } catch (TransportExceptionInterface $e) {
                    $logger->error('Formulaire de contact : e-mail non envoyé (message conservé en base)', ['exception' => $e, 'demande' => $demande->getId()]);
                    $demande->marquerEchec();
                }
                $entityManager->flush();
            }

            $this->addFlash('success', $translator->trans('Merci, votre message est bien parti ! Je vous réponds au plus vite.'));

            // Redirection : recharger la page ne renvoie pas le message
            return $this->redirectToRoute('app_contact', ['_fragment' => 'formulaire']);
        }

        return $this->render('contact/index.html.twig', ['form' => $form]);
    }
}
