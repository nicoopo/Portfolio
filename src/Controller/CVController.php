<?php

namespace App\Controller;

use App\Repository\CentreInteretRepository;
use App\Repository\CvCompetenceRepository;
use App\Repository\CvProfilRepository;
use App\Repository\EtapeParcoursRepository;
use App\Repository\ExperienceRepository;
use App\Repository\LangueRepository;
use App\Repository\LienRecruteurRepository;
use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Contracts\Cache\CacheInterface;

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
        private readonly LienRecruteurRepository $liens,
        #[Target('cv_pdf.cache')] private readonly CacheInterface $cvPdfCache,
    ) {
    }

    #[Route('/CV', name: 'app_cv')]
    public function index(Request $request): Response
    {
        return $this->render('cv/index.html.twig', $this->contenu($request));
    }

    #[Route('/CV/download', name: 'app_cv_download')]
    public function download(Request $request): Response
    {
        return $this->pdf($request, 'attachment');
    }

    /** Le même PDF, affiché dans le navigateur au lieu d'être téléchargé */
    #[Route('/univers/cv-preview', name: 'app_univers_cv_preview')]
    public function preview(Request $request): Response
    {
        return $this->pdf($request, 'inline');
    }

    private function pdf(Request $request, string $disposition): Response
    {
        $theme = 'light' === $request->query->get('theme') ? 'light' : 'dark';

        $contenu = $this->contenu($request);
        // Un PDF par langue, thème et lien recruteur (code inconnu : le CV de tout le monde), gardé jusqu'à la prochaine
        // modification dans l'admin (App\EventListener\CvPdfListener)
        $cle = 'cv_'.$request->getLocale().'_'.$theme.'_'.($contenu['recruteur']?->getId() ?? 0);
        $pdf = $this->cvPdfCache->get($cle, function () use ($theme, $contenu): string {
            $options = new Options();
            $options->set('isHtml5ParserEnabled', true);
            $options->set('defaultFont', 'DejaVu Sans');

            $dompdf = new Dompdf($options);
            $dompdf->loadHtml($this->renderView('cv/pdf.html.twig', ['theme' => $theme] + $contenu));
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();

            return $dompdf->output();
        });

        // Ex. CV_Nicolas_Cataluna_clair.pdf, CV_Nicolas_Cataluna_en_light.pdf, CV_Nicolas_Cataluna_es_dark.pdf
        $filename = 'CV_Nicolas_Cataluna_'.$request->getLocale().'_'.$theme.'.pdf';
        if ('fr' === $request->getLocale()) {
            $filename = 'CV_Nicolas_Cataluna_'.('dark' === $theme ? 'sombre' : 'clair').'.pdf';
        }

        return new Response($pdf, Response::HTTP_OK, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => $disposition.'; filename="'.$filename.'"',
        ]);
    }

    /** Les données du CV, communes à la page et au PDF ; ?pour=code : CV adapté au lien recruteur (accroche, points forts) */
    private function contenu(Request $request): array
    {
        $ordre = ['position' => 'ASC'];

        return [
            'recruteur' => $this->liens->parCode($request->query->getString('pour')),
            'profil' => $this->profil->findOneBy([]) ?? throw $this->createNotFoundException('Profil du CV absent : lancer les migrations.'),
            'experiences' => $this->experiences->findBy([], $ordre),
            'competences' => $this->competences->findBy([], $ordre),
            'formations' => $this->parcours->findBy([], $ordre),
            'langues' => $this->langues->findBy([], $ordre),
            'interets' => $this->interets->findBy([], $ordre),
        ];
    }
}
