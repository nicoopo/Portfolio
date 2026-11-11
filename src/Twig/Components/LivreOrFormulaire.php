<?php

namespace App\Twig\Components;

use App\Form\LivreOrType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\ComponentWithFormTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * Formulaire du livre d'or validé pendant la saisie (erreur d'un champ à sa sortie, compteur du message en direct).
 * Il ne fait que se redessiner : l'envoi reste un POST classique vers LivreOrController (champ piège, limite par IP,
 * CSRF), qui marche aussi sans JavaScript.
 */
#[AsLiveComponent]
final class LivreOrFormulaire extends AbstractController
{
    use ComponentWithFormTrait;
    use DefaultActionTrait;

    protected function instantiateForm(): FormInterface
    {
        // Action explicite : sinon le formulaire redessiné viserait l'adresse du rendu live (/_components/…)
        return $this->createForm(LivreOrType::class, options: ['action' => $this->generateUrl('app_livre_or')]);
    }

    /** Compté comme Assert\Length du message (LivreOrType) */
    public function getLongueurMessage(): int
    {
        return mb_strlen((string) ($this->formValues['message'] ?? ''));
    }
}
