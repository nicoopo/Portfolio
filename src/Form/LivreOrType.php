<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;

/** Livre d'or : prénom et message court. `website` : piège à robots, comme sur la page Contact (ContactType). */
final class LivreOrType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('prenom', TextType::class, [
                'label' => 'Prénom ou pseudo',
                'constraints' => [new Assert\NotBlank(), new Assert\Length(max: 40)],
            ])
            ->add('message', TextareaType::class, [
                'label' => 'Votre message (280 caractères)',
                'constraints' => [new Assert\NotBlank(), new Assert\Length(min: 3, max: 280)],
                'attr' => ['rows' => 3, 'maxlength' => 280],
            ])
            ->add('website', TextType::class, [
                'label' => 'Ne pas remplir',
                'required' => false,
                'attr' => ['autocomplete' => 'off', 'tabindex' => '-1'],
                'row_attr' => ['class' => 'contact-honeypot', 'aria-hidden' => 'true'],
            ]);
    }
}
