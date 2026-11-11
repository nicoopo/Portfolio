<?php

namespace App\Twig\Components;

use App\Form\ContactType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\ComponentWithFormTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * Formulaire de contact validé pendant la saisie (erreur d'un champ à sa sortie), comme LivreOrFormulaire.
 * Il ne fait que se redessiner : l'envoi reste un POST classique vers ContactController (champ piège, limite par IP,
 * CSRF, e-mail), qui marche aussi sans JavaScript.
 */
#[AsLiveComponent]
final class ContactFormulaire extends AbstractController
{
    use ComponentWithFormTrait;
    use DefaultActionTrait;

    protected function instantiateForm(): FormInterface
    {
        // Action explicite : sinon le formulaire redessiné viserait l'adresse du rendu live (/_components/…)
        return $this->createForm(ContactType::class, options: ['action' => $this->generateUrl('app_contact')]);
    }
}
