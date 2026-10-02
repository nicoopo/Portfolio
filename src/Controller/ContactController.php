<?php

namespace App\Controller;

use App\Form\ContactType;
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

final class ContactController extends AbstractController
{
    #[Route('/contact', name: 'app_contact')]
    public function index(
        Request $request,
        MailerInterface $mailer,
        LoggerInterface $logger,
        #[Autowire('%app.contact_email%')] string $contactEmail,
    ): Response {
        $form = $this->createForm(ContactType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            // Piège rempli : un robot. On fait comme si l'envoi avait réussi, sans rien envoyer.
            if (!$data['website']) {
                try {
                    $mailer->send((new Email())
                        ->from(new Address($contactEmail, 'Portfolio'))
                        ->to($contactEmail)
                        ->replyTo(new Address($data['email'], $data['nom']))
                        ->subject('Contact portfolio : '.$data['nom'])
                        ->text($data['message']."\n\n— ".$data['nom'].' <'.$data['email'].'>'));
                } catch (TransportExceptionInterface $e) {
                    $logger->error('Formulaire de contact : envoi impossible', ['exception' => $e]);
                    // Pas de redirection : le formulaire reste rempli, le message n'est pas perdu
                    $this->addFlash('error', 'L\'envoi a échoué. Réessayez plus tard, ou écrivez-moi directement à '.$contactEmail.'.');

                    return $this->render('contact/index.html.twig', ['form' => $form], new Response(status: Response::HTTP_SERVICE_UNAVAILABLE));
                }
            }

            $this->addFlash('success', 'Merci, votre message est bien parti ! Je vous réponds au plus vite.');

            // Redirection : recharger la page ne renvoie pas le message
            return $this->redirectToRoute('app_contact', ['_fragment' => 'formulaire']);
        }

        return $this->render('contact/index.html.twig', ['form' => $form]);
    }
}
