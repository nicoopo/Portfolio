<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

/** Connexion à l'administration (formulaire d'EasyAdmin, traité par le pare-feu form_login). */
final class SecurityController extends AbstractController
{
    #[Route('/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        return $this->render('@EasyAdmin/page/login.html.twig', [
            'error' => $authenticationUtils->getLastAuthenticationError(),
            'last_username' => $authenticationUtils->getLastUsername(),
            'page_title' => 'Administration du portfolio',
            'csrf_token_intention' => 'authenticate',
            // Hors tableau de bord, le formulaire d'EasyAdmin renverrait sinon vers l'accueil
            'target_path' => $this->generateUrl('admin'),
            'username_label' => 'Identifiant',
            'password_label' => 'Mot de passe',
            'sign_in_label' => 'Se connecter',
        ]);
    }

    #[Route('/logout', name: 'app_logout')]
    public function logout(): never
    {
        throw new \LogicException('Intercepté par le pare-feu (logout).');
    }
}
