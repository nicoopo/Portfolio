<?php

namespace App\Controller\Admin;

use App\Entity\Projet;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\UrlField;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraints\Image;

final class ProjetCrudController extends AbstractCrudController
{
    private const LARGEUR_MAX = 1000;
    private const QUALITE_WEBP = 80;

    public static function getEntityFqcn(): string
    {
        return Projet::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Projet')
            ->setEntityLabelInPlural('Projets')
            ->setDefaultSort(['position' => 'ASC']);
    }

    public function configureFields(string $pageName): iterable
    {
        yield TextField::new('titre');
        yield TextField::new('slug')->setHelp('Adresse de la page du projet : /projects/slug (minuscules, chiffres, tirets)')->hideOnIndex();
        yield TextareaField::new('description')->setHelp('Résumé d’une phrase : carte de la page Projets et chapeau de la page du projet')->hideOnIndex();
        yield TextareaField::new('details', 'Texte détaillé')
            ->setHelp('Page du projet (/projects/slug) : contexte, ce que j’ai fait, difficultés, ce que j’en retiens. Une ligne vide = nouveau paragraphe. Vide : seule la description s’affiche.')
            ->setNumOfRows(12)->hideOnIndex();
        yield UrlField::new('depot', 'Code source')->setHelp('Ex. https://github.com/…')->hideOnIndex();
        yield UrlField::new('demo', 'Version en ligne')->hideOnIndex();
        yield TextField::new('tech', 'Technos')->setHelp('Séparées par des virgules : une pastille chacune');
        // Fichier rangé dans public/uploads/projets/ (volume Docker en prod, sauvegardé chaque nuit), nommé
        // <nom d'origine>-<empreinte du contenu> : une nouvelle image ne réutilise jamais l'adresse (et le cache) de l'ancienne.
        // Enregistré en WebP, 1000 px de large au plus, comme les images du dépôt
        yield ImageField::new('imageEnvoyee', 'Image')
            ->setBasePath('uploads/projets')
            ->setUploadDir('public/uploads/projets')
            ->setUploadedFileNamePattern('[slug]-[contenthash].webp')
            ->setFormTypeOption('upload_new', self::enregistrerEnWebp(...))
            ->setFileConstraints(new Image(
                maxSize: '3M',
                mimeTypes: ['image/jpeg', 'image/png', 'image/webp'],
                mimeTypesMessage: 'JPG, PNG ou WebP uniquement.',
            ))
            ->setRequired(false)
            ->setHelp('JPG, PNG ou WebP, 3 Mo maximum, enregistrée en WebP de 1000 px de large au plus. Prend la place de l’image du dépôt.');
        yield TextField::new('image', 'Image du dépôt')
            ->setHelp('Ancienne méthode, utilisée si aucune image n’est envoyée : chemin sous assets/images/projets/, ex. symfony/portfolio-cerveau.webp')
            ->hideOnIndex();
        yield TextField::new('categorie', 'Groupe')->setHelp('Ex. « PHP / Symfony » : regroupe les projets sur la page Projets');
        yield AssociationField::new('competences', 'Compétences utilisées')
            ->setFormTypeOption('by_reference', false); // passe par addCompetence / removeCompetence
        yield IntegerField::new('position')->setHelp('Ordre d\'affichage (croissant)');

        // Version anglaise du site : vide = le français s'affiche
        yield FormField::addFieldset('Anglais (facultatif)')->collapsible()->renderCollapsed();
        yield TextField::new('titreEn', 'Titre')->hideOnIndex();
        yield TextareaField::new('descriptionEn', 'Description')->hideOnIndex();
        yield TextareaField::new('detailsEn', 'Texte détaillé')->setNumOfRows(12)->hideOnIndex();
        yield TextField::new('categorieEn', 'Groupe')->hideOnIndex();
    }

    /** Remplace l'enregistrement d'EasyAdmin : réduit l'image à LARGEUR_MAX px et l'écrit en WebP (transparence gardée) */
    private static function enregistrerEnWebp(UploadedFile $fichier, string $dossier, string $nom): void
    {
        $image = imagecreatefromstring(file_get_contents($fichier->getPathname()))
            ?: throw new \RuntimeException('Image illisible : '.$fichier->getClientOriginalName());
        imagepalettetotruecolor($image); // PNG à palette : imagewebp n'accepte que les vraies couleurs
        if (imagesx($image) > self::LARGEUR_MAX) {
            $image = imagescale($image, self::LARGEUR_MAX);
        }
        imagealphablending($image, false);
        imagesavealpha($image, true);
        imagewebp($image, $dossier.$nom, self::QUALITE_WEBP);
    }
}
