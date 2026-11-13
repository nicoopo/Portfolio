<?php

namespace App\Controller\Admin;

use App\Form\TraductionsField;
use App\Service\Traducteur;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminRoute;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\Response;

/**
 * Bouton « Traduire » de la page d'édition (#423) : remplit les traductions vides avec Traducteur,
 * pour les champs déclarés dans le TraductionsField du contrôleur. Agit sur la version enregistrée.
 */
trait ActionTraduire
{
    private function ajouterTraduire(Actions $actions): Actions
    {
        return $actions->add(Crud::PAGE_EDIT, Action::new('traduire', 'Traduire', 'fa fa-language')->linkToCrudAction('traduire')
            ->setHtmlAttributes(['title' => 'Remplit les traductions vides à partir du français enregistré']));
    }

    #[AdminRoute('/{id}/traduire')]
    public function traduire(int $id, Traducteur $traducteur, EntityManagerInterface $entityManager, AdminUrlGenerator $urls): Response
    {
        $entite = $entityManager->find(static::getEntityFqcn(), $id) ?? throw $this->createNotFoundException();
        $champs = [];
        foreach ($this->configureFields(Crud::PAGE_EDIT) as $champ) {
            if ($champ instanceof TraductionsField) {
                $champs = array_keys($champ->getAsDto()->getFormTypeOption('champs'));
            }
        }

        try {
            $langues = $traducteur->traduire($entite, $champs);
            $entityManager->flush();
            $this->addFlash('success', $langues ? 'Traduit en '.implode(', ', $langues).' : relisez avant de publier.' : 'Rien à traduire : toutes les traductions sont remplies.');
        } catch (\Throwable $e) {
            $this->addFlash('danger', 'Traduction impossible : '.$e->getMessage());
        }

        return $this->redirect($urls->setController(static::class)->setAction(Action::EDIT)->setEntityId($id)->generateUrl());
    }
}
