<?php

namespace App\Controller;

use App\Entity\DemandeContact;
use App\Entity\Journal;
use App\Form\ContactType;
use App\Service\Journaliste;
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
use Symfony\Component\RateLimiter\RateLimiterFactoryInterface;
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
        Journaliste $journaliste,
        RateLimiterFactoryInterface $contactLimiter,
        #[Autowire('%app.contact_email%')] string $contactEmail,
    ): Response {
        $form = $this->createForm(ContactType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            // Limite par IP (config/packages/rate_limiter.yaml) : chaque message part par e-mail
            if (!$data['website'] && !$contactLimiter->create($request->getClientIp())->consume()->isAccepted()) {
                $this->addFlash('error', $translator->trans('Beaucoup de messages d’un coup : réessayez dans une heure.'));

                return $this->redirectToRoute('app_contact', ['_fragment' => 'formulaire']);
            }

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
                    $journaliste->noter(Journal::CONTACT_ENVOYE, 'Message de contact reçu et envoyé par e-mail — '.$data['nom']);
                } catch (TransportExceptionInterface $e) {
                    $logger->error('Formulaire de contact : e-mail non envoyé (message conservé en base)', ['exception' => $e, 'demande' => $demande->getId()]);
                    $demande->marquerEchec();
                    $journaliste->noter(Journal::CONTACT_ECHEC, 'Message de contact reçu, e-mail en échec (voir « Demandes de contact ») — '.$data['nom']);
                }
                $entityManager->flush();
            } else {
                $journaliste->noter(Journal::SPAM_BLOQUE, 'Robot bloqué par le champ piège du formulaire de contact');
            }

            $this->addFlash('success', $translator->trans('Merci, votre message est bien parti ! Je vous réponds au plus vite.'));

            // Redirection : recharger la page ne renvoie pas le message
            return $this->redirectToRoute('app_contact', ['_fragment' => 'formulaire']);
        }

        return $this->render('contact/index.html.twig', ['form' => $form]);
    }
}
