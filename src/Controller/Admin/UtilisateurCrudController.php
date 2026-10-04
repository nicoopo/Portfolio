<?php

namespace App\Controller\Admin;

use App\Entity\Utilisateur;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\KeyValueStore;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Comptes de l'administration. Le mot de passe se saisit deux fois dans un champ non relié à
 * l'entité ; seule son empreinte est enregistrée.
 */
final class UtilisateurCrudController extends AbstractCrudController
{
    private const LONGUEUR_MIN = 12;

    public function __construct(private readonly UserPasswordHasherInterface $hasher)
    {
    }

    public static function getEntityFqcn(): string
    {
        return Utilisateur::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Compte')
            ->setEntityLabelInPlural('Comptes')
            ->setDefaultSort(['identifiant' => 'ASC']);
    }

    public function configureActions(Actions $actions): Actions
    {
        // Supprimer son propre compte couperait l'accès : on ne le propose pas pour soi
        return $actions->update(Crud::PAGE_INDEX, Action::DELETE, fn (Action $action) => $action->displayIf(
            fn (Utilisateur $utilisateur) => $utilisateur->getUserIdentifier() !== $this->getUser()?->getUserIdentifier(),
        ));
    }

    public function configureFields(string $pageName): iterable
    {
        yield TextField::new('identifiant');
        yield TextField::new('nouveauMotDePasse', Crud::PAGE_NEW === $pageName ? 'Mot de passe' : 'Nouveau mot de passe')
            ->setFormType(RepeatedType::class)
            ->setFormTypeOptions([
                'type' => PasswordType::class,
                'mapped' => false,
                'required' => Crud::PAGE_NEW === $pageName,
                'first_options' => ['label' => Crud::PAGE_NEW === $pageName ? 'Mot de passe' : 'Nouveau mot de passe (vide = inchangé)', 'attr' => ['autocomplete' => 'new-password']],
                'second_options' => ['label' => 'Confirmation', 'attr' => ['autocomplete' => 'new-password']],
                'invalid_message' => 'Les deux mots de passe ne correspondent pas.',
                'constraints' => Crud::PAGE_NEW === $pageName
                    ? [new Assert\NotBlank(), new Assert\Length(min: self::LONGUEUR_MIN)]
                    : [new Assert\Length(min: self::LONGUEUR_MIN)],
            ])
            ->onlyOnForms();
        yield DateTimeField::new('derniereConnexion', 'Dernière connexion')->hideOnForm();
    }

    public function createNewFormBuilder(EntityDto $entityDto, KeyValueStore $formOptions, AdminContext $context): FormBuilderInterface
    {
        return $this->hacherMotDePasse(parent::createNewFormBuilder($entityDto, $formOptions, $context));
    }

    public function createEditFormBuilder(EntityDto $entityDto, KeyValueStore $formOptions, AdminContext $context): FormBuilderInterface
    {
        return $this->hacherMotDePasse(parent::createEditFormBuilder($entityDto, $formOptions, $context));
    }

    /** Après envoi du formulaire : mot de passe saisi → empreinte enregistrée (vide → inchangé) */
    private function hacherMotDePasse(FormBuilderInterface $builder): FormBuilderInterface
    {
        return $builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event): void {
            $motDePasse = $event->getForm()->get('nouveauMotDePasse')->getData();
            if ($motDePasse) {
                $utilisateur = $event->getData();
                $utilisateur->setMotDePasse($this->hasher->hashPassword($utilisateur, $motDePasse));
            }
        });
    }
}
