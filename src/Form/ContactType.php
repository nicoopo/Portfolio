<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Formulaire de la page Contact. Le champ `website` est un piège à robots (honeypot) :
 * masqué aux humains, rempli par les robots qui remplissent tout.
 */
final class ContactType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, [
                'label' => 'Nom',
                'constraints' => [new Assert\NotBlank(), new Assert\Length(max: 100)],
            ])
            ->add('email', EmailType::class, [
                'label' => 'E-mail',
                'constraints' => [new Assert\NotBlank(), new Assert\Email(), new Assert\Length(max: 180)],
            ])
            ->add('message', TextareaType::class, [
                'label' => 'Message',
                'constraints' => [new Assert\NotBlank(), new Assert\Length(min: 10, max: 5000)],
                'attr' => ['rows' => 6],
            ])
            ->add('website', TextType::class, [
                'label' => 'Ne pas remplir',
                'required' => false,
                'attr' => ['autocomplete' => 'off', 'tabindex' => '-1'],
                'row_attr' => ['class' => 'contact-honeypot', 'aria-hidden' => 'true'],
            ]);
    }
}
