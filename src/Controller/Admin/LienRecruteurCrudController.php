<?php

namespace App\Controller\Admin;

use App\Entity\LienRecruteur;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/** Liens recruteur : créés ici, à copier dans un e-mail ou une candidature ; les visites remontent toutes seules. */
final class LienRecruteurCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return LienRecruteur::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Lien recruteur')
            ->setEntityLabelInPlural('Liens recruteur')
            ->setDefaultSort(['id' => 'DESC']);
    }

    public function configureFields(string $pageName): iterable
    {
        yield TextField::new('entreprise')->setHelp('Affiché sur l’accueil : « Bonjour l’équipe de … »');
        yield TextField::new('poste')->setHelp('Facultatif : « … pour le poste de … »')->hideOnIndex();
        yield TextareaField::new('message', 'Mot personnel')->setHelp('Facultatif, affiché tel quel sous le bonjour')->hideOnIndex();
        yield TextField::new('code', 'Lien à envoyer')->hideOnForm()
            ->formatValue(fn (string $code) => $this->generateUrl('app_home', ['pour' => $code], UrlGeneratorInterface::ABSOLUTE_URL));
        yield IntegerField::new('visites')->hideOnForm();
        yield DateTimeField::new('premiereVisite', 'Ouvert le')->hideOnForm();
        yield DateTimeField::new('derniereVisite', 'Dernière visite')->hideOnForm()->hideOnIndex();
    }
}
