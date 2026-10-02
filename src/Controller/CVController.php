<?php

namespace App\Controller;

use App\Repository\CentreInteretRepository;
use App\Repository\CvCompetenceRepository;
use App\Repository\CvProfilRepository;
use App\Repository\EtapeParcoursRepository;
use App\Repository\ExperienceRepository;
use App\Repository\LangueRepository;
use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/** CV en ligne et en PDF : le même contenu, lu en base (templates/cv/_contenu.html.twig). */
final class CVController extends AbstractController
{
    public function __construct(
        private readonly CvProfilRepository $profil,
        private readonly ExperienceRepository $experiences,
        private readonly CvCompetenceRepository $competences,
        private readonly EtapeParcoursRepository $parcours,
        private readonly LangueRepository $langues,
        private readonly CentreInteretRepository $interets,
    ) {
    }

    #[Route('/CV', name: 'app_cv')]
    public function index(): Response
    {
        return $this->render('cv/index.html.twig', $this->contenu());
    }

    #[Route('/CV/download', name: 'app_cv_download')]
    public function download(Request $request): Response
    {
        $theme = 'light' === $request->query->get('theme') ? 'light' : 'dark';

        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $options->set('isHtml5ParserEnabled', true);
        $options->set('defaultFont', 'DejaVu Sans');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($this->renderView('cv/pdf.html.twig', ['theme' => $theme] + $this->contenu()));
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        // Ex. CV_Nicolas_Cataluna_clair.pdf, CV_Nicolas_Cataluna_en_light.pdf
        $filename = 'en' === $request->getLocale()
            ? 'CV_Nicolas_Cataluna_en_'.$theme.'.pdf'
            : 'CV_Nicolas_Cataluna_'.('dark' === $theme ? 'sombre' : 'clair').'.pdf';

        return new Response($dompdf->output(), Response::HTTP_OK, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    /** Les données du CV, communes à la page et au PDF */
    private function contenu(): array
    {
        $ordre = ['position' => 'ASC'];

        return [
            'profil' => $this->profil->findOneBy([]) ?? throw $this->createNotFoundException('Profil du CV absent : lancer les migrations.'),
            'experiences' => $this->experiences->findBy([], $ordre),
            'competences' => $this->competences->findBy([], $ordre),
            'formations' => $this->parcours->findBy([], $ordre),
            'langues' => $this->langues->findBy([], $ordre),
            'interets' => $this->interets->findBy([], $ordre),
        ];
    }
}
