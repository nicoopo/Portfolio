<?php

namespace App\Controller\Admin;

use App\Entity\Article;
use App\Form\TraductionsField;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use Symfony\Component\Validator\Constraints\Image;

final class ArticleCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Article::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Article')
            ->setEntityLabelInPlural('Articles')
            ->setDefaultSort(['publieLe' => 'DESC']);
    }

    public function configureFields(string $pageName): iterable
    {
        yield TextField::new('titre');
        yield TextField::new('slug')->setHelp('Adresse de l’article : /articles/slug (minuscules, chiffres, tirets)')->hideOnIndex();
        yield DateTimeField::new('publieLe', 'Publié le')
            ->setHelp('Vide : brouillon, invisible sur le site. Date future : publié automatiquement à cette date.');
        yield TextareaField::new('resume', 'Résumé')->setHelp('Une ou deux phrases : liste des articles, haut de l’article et aperçu de partage')->hideOnIndex();
        yield TextareaField::new('contenu')
            ->setHelp('Markdown : ## Intertitre, **gras**, *italique*, [lien](https://…), listes, `code`, blocs ``` … ```. Le HTML est affiché tel quel, pas interprété.')
            ->setNumOfRows(20)->hideOnIndex();
        // Même traitement que les images de projets : WebP, 1000 px de large au plus, nom tiré du contenu
        yield ImageField::new('image')
            ->setBasePath('uploads/articles')
            ->setUploadDir('public/uploads/articles')
            ->setUploadedFileNamePattern('[slug]-[contenthash].webp')
            ->setFormTypeOption('upload_new', ProjetCrudController::enregistrerEnWebp(...))
            ->setFileConstraints(new Image(
                maxSize: '3M',
                mimeTypes: ['image/jpeg', 'image/png', 'image/webp'],
                mimeTypesMessage: 'JPG, PNG ou WebP uniquement.',
            ))
            ->setRequired(false)
            ->setHelp('Facultative. JPG, PNG ou WebP, 3 Mo maximum : en haut de l’article et dans l’aperçu de partage.');

        // Autres langues du site (framework.enabled_locales) : vide = le français s'affiche
        yield FormField::addFieldset('Traductions (facultatif)')->collapsible()->renderCollapsed();
        yield TraductionsField::new()->champs(['titre' => 'Titre', 'resume' => 'Résumé', 'contenu' => 'Contenu (Markdown)'], ['resume', 'contenu']);
    }
}
