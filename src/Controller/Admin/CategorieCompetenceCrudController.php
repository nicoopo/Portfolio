<?php

namespace App\Controller\Admin;

use App\Entity\CategorieCompetence;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ColorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

final class CategorieCompetenceCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return CategorieCompetence::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Catégorie')
            ->setEntityLabelInPlural('Catégories de compétences')
            ->setDefaultSort(['position' => 'ASC']);
    }

    /** Catégorie encore utilisée : refus expliqué (sinon la clé étrangère donne une page d'erreur) */
    public function deleteEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        $nombre = $entityInstance->getCompetences()->count();
        if ($nombre > 0) {
            $this->addFlash('danger', \sprintf('« %s » contient encore %d compétence(s) : déplacez-les ou supprimez-les d\'abord.', $entityInstance->getNom(), $nombre));

            return;
        }

        parent::deleteEntity($entityManager, $entityInstance);
    }

    public function configureFields(string $pageName): iterable
    {
        yield TextField::new('nom');
        yield ChoiceField::new('zone', 'Lobe du cerveau')->setChoices([
            'Frontal' => 'frontal',
            'Pariétal' => 'parietal',
            'Temporal' => 'temporal',
            'Occipital' => 'occipital',
            'Limbique' => 'limbique',
        ]);
        yield ColorField::new('couleur')->setHelp('Couleur des neurones de la catégorie');
        yield IntegerField::new('position')->setHelp('Ordre d\'affichage (croissant)');
    }
}
