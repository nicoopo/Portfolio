<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

/**
 * /sitemap.xml : les pages publiques, chacune en français et en anglais (hreflang).
 * Route déclarée dans config/routes.yaml : hors du préfixe de langue (pas de /en/sitemap.xml).
 */
final class SitemapController extends AbstractController
{
    private const PAGES = ['app_home', 'app_cerveau', 'app_competences', 'app_projects', 'app_univers', 'app_cv', 'app_contact'];

    public function __invoke(): Response
    {
        $response = $this->render('sitemap.xml.twig', ['pages' => self::PAGES, 'langues' => ['fr', 'en']]);
        $response->headers->set('Content-Type', 'application/xml; charset=UTF-8');

        return $response;
    }
}
