<?php

namespace App\Form;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Intl\Languages;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Administration : traductions d'une entité (trait Traduisible), un bloc par langue du site hors français.
 * Option « champs » : ['nom' => 'Nom', …] ; « paragraphes » : champs saisis sur plusieurs lignes.
 * Un champ laissé vide affiche le français.
 */
final class TraductionsType extends AbstractType
{
    /** @param list<string> $langues */
    public function __construct(#[Autowire('%kernel.enabled_locales%')] private readonly array $langues)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        foreach (array_diff($this->langues, ['fr']) as $langue) {
            $bloc = $builder->create($langue, FormType::class, ['label' => ucfirst(Languages::getName($langue, 'fr')), 'required' => false]);
            foreach ($options['champs'] as $champ => $libelle) {
                $paragraphe = \in_array($champ, $options['paragraphes'], true);
                $bloc->add($champ, $paragraphe ? TextareaType::class : TextType::class, [
                    'label' => $libelle,
                    'required' => false,
                    'attr' => ['lang' => $langue] + ($paragraphe ? ['rows' => 6] : []),
                ]);
            }
            $builder->add($bloc);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired('champs')->setAllowedTypes('champs', 'array');
        $resolver->setDefaults(['paragraphes' => [], 'label' => false, 'required' => false]);
    }
}
