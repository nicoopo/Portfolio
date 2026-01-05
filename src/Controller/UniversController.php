<?php

namespace App\Controller;

use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class UniversController extends AbstractController
{
    #[Route('/univers', name: 'app_univers')]
    public function index(): Response
    {
        return $this->render('univers/index.html.twig');
    }

    #[Route('/univers/cv-preview', name: 'app_univers_cv_preview')]
    public function previewCV(Request $request): Response
    {
        // Récupérer le thème (par défaut sombre)
        $theme = $request->query->get('theme', 'dark');

        // Options pour Dompdf
        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $options->set('isHtml5ParserEnabled', true);
        $options->set('defaultFont', 'DejaVu Sans');

        // Créer l'instance Dompdf
        $dompdf = new Dompdf($options);

        // Rendre le template avec le thème approprié
        $html = $this->renderView('cv/pdf.html.twig', [
            'theme' => $theme,
        ]);

        // Charger le HTML dans Dompdf
        $dompdf->loadHtml($html);

        // Définir le format de papier A4 portrait
        $dompdf->setPaper('A4', 'portrait');

        // Rendre le PDF
        $dompdf->render();

        // Retourner la réponse PDF pour affichage inline
        return new Response(
            $dompdf->output(),
            Response::HTTP_OK,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="CV_Preview.pdf"',
                'Cache-Control' => 'private, max-age=0, must-revalidate',
            ]
        );
    }
}
