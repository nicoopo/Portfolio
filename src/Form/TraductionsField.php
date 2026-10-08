<?php

namespace App\Form;

use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface;
use EasyCorp\Bundle\EasyAdminBundle\Field\FieldTrait;
use Symfony\Contracts\Translation\TranslatableInterface;

/**
 * Champ EasyAdmin des traductions (trait Traduisible), saisi avec TraductionsType.
 * Un champ à lui : avec Field::new, EasyAdmin prendrait la colonne JSON pour un ArrayField.
 * Ex. TraductionsField::new()->champs(['nom' => 'Nom', 'description' => 'Description'], paragraphes: ['description'])
 */
final class TraductionsField implements FieldInterface
{
    use FieldTrait;

    public static function new(string $propertyName = 'traductions', TranslatableInterface|string|bool|null $label = false): self
    {
        return (new self())
            ->setProperty($propertyName)
            ->setLabel($label)
            ->setTemplateName('crud/field/text')
            ->setFormType(TraductionsType::class)
            ->onlyOnForms();
    }

    /**
     * @param array<string, string> $champs      champ de l'entité => libellé
     * @param list<string>          $paragraphes champs saisis sur plusieurs lignes
     */
    public function champs(array $champs, array $paragraphes = []): self
    {
        return $this->setFormTypeOptions(['champs' => $champs, 'paragraphes' => $paragraphes]);
    }
}
