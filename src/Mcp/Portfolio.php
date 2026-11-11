<?php

namespace App\Mcp;

use App\Repository\CategorieCompetenceRepository;
use App\Repository\CreneauRepository;
use App\Repository\CvCompetenceRepository;
use App\Repository\CvProfilRepository;
use App\Repository\ExperienceRepository;
use App\Repository\ProjetRepository;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Capability\Attribute\Schema;
use Mcp\Schema\ToolAnnotations;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Serveur MCP du portfolio (/mcp, config/packages/mcp.yaml) : l'assistant IA d'un recruteur interroge le profil.
 * Lecture seule et données déjà publiques sur le site : ni téléphone, ni candidatures, ni demandes de contact,
 * ni liens recruteur. Aucune IA appelée ici. Traductions de l'admin, le français sinon (comme le filtre « loc »).
 */
final class Portfolio
{
    private const LANGUES = ['fr', 'en', 'es', 'de', 'it', 'pt'];

    public function __construct(
        private readonly CvProfilRepository $profil,
        private readonly ExperienceRepository $experiences,
        private readonly CvCompetenceRepository $cvCompetences,
        private readonly CategorieCompetenceRepository $categories,
        private readonly ProjetRepository $projets,
        private readonly CreneauRepository $creneaux,
        private readonly UrlGeneratorInterface $urls,
    ) {
    }

    #[McpTool(name: 'profil', description: 'Profil de Nicolas Cataluna, développeur PHP / Symfony : titre, accroche, résumé, qualités, e-mail et liens utiles (CV en PDF, contact, projets).', annotations: new ToolAnnotations(readOnlyHint: true))]
    public function profil(#[Schema(description: 'Langue de la réponse', enum: self::LANGUES)] string $langue = 'fr'): array
    {
        $profil = $this->profil->findOneBy([]);

        return [
            'nom' => 'Nicolas Cataluna',
            'titre' => $this->loc($profil, 'titre', $langue),
            'accroche' => $this->loc($profil, 'accroche', $langue),
            'resume' => $this->loc($profil, 'resume', $langue),
            'qualites' => $this->loc($profil, 'qualites', $langue),
            'email' => $profil?->getEmail(),
            'liens' => [
                'site' => $this->url('app_home', $langue),
                'cv_pdf' => $this->url('app_cv_download', $langue),
                'contact' => $this->url('app_contact', $langue),
                'projets' => $this->url('app_projects', $langue),
            ],
        ];
    }

    #[McpTool(name: 'experiences', description: 'Expériences professionnelles (alternance, stages, missions), de la plus récente à la plus ancienne.', annotations: new ToolAnnotations(readOnlyHint: true))]
    public function experiences(#[Schema(description: 'Langue de la réponse', enum: self::LANGUES)] string $langue = 'fr'): array
    {
        $liste = [];
        foreach ($this->experiences->findBy([], ['position' => 'ASC']) as $e) {
            $liste[] = [
                'poste' => $this->loc($e, 'poste', $langue),
                'entreprise' => $e->getEntreprise(),
                'lieu' => $this->loc($e, 'lieu', $langue),
                'periode' => $this->loc($e, 'periode', $langue),
                'contrat' => $this->loc($e, 'contrat', $langue),
                'missions' => $this->loc($e, 'missions', $langue),
                'site' => $e->getSite(),
            ];
        }

        return $liste;
    }

    #[McpTool(name: 'competences', description: 'Compétences techniques : par catégorie (langages, frameworks, outils…), avec les projets où chacune a servi, et le résumé du CV.', annotations: new ToolAnnotations(readOnlyHint: true))]
    public function competences(#[Schema(description: 'Langue de la réponse', enum: self::LANGUES)] string $langue = 'fr'): array
    {
        $categories = [];
        foreach ($this->categories->findBy([], ['position' => 'ASC']) as $categorie) {
            $categories[] = [
                'categorie' => $this->loc($categorie, 'nom', $langue),
                'competences' => array_values(array_map(fn ($c) => [
                    'nom' => $this->loc($c, 'nom', $langue),
                    'projets' => array_values(array_map(fn ($p) => $this->loc($p, 'titre', $langue), $c->getProjets()->toArray())),
                ], $categorie->getCompetences()->toArray())),
            ];
        }
        $cv = array_map(fn ($c) => ['titre' => $this->loc($c, 'titre', $langue), 'elements' => $this->loc($c, 'elements', $langue)],
            $this->cvCompetences->findBy([], ['position' => 'ASC']));

        return ['par_categorie' => $categories, 'resume_cv' => $cv];
    }

    #[McpTool(name: 'projets', description: 'Projets réalisés : titre, description, technologies, année, page du projet, code source et version en ligne. Filtrable par technologie.', annotations: new ToolAnnotations(readOnlyHint: true))]
    public function projets(
        #[Schema(description: 'Langue de la réponse', enum: self::LANGUES)] string $langue = 'fr',
        #[Schema(description: 'Ne garder que les projets utilisant cette technologie (ex. Symfony, Docker), insensible à la casse', maxLength: 50)] string $techno = '',
    ): array {
        $liste = [];
        foreach ($this->projets->findBy([], ['position' => 'ASC']) as $p) {
            if ('' !== $techno && false === mb_stripos($p->getTech(), $techno)) {
                continue;
            }
            $liste[] = [
                'titre' => $this->loc($p, 'titre', $langue),
                'description' => $this->loc($p, 'description', $langue),
                'technologies' => array_map('trim', explode(',', $p->getTech())),
                'groupe' => $this->loc($p, 'categorie', $langue),
                'annee' => $p->getAnnee(),
                'page' => $this->url('app_project', $langue, ['slug' => $p->getSlug()]),
                'code_source' => $p->getDepot(),
                'en_ligne' => $p->getDemo(),
            ];
        }

        return $liste;
    }

    #[McpTool(name: 'disponibilites', description: 'Nombre de créneaux d’entretien libres. La réservation se fait par le lien personnel envoyé avec chaque candidature ; sinon, passer par la page de contact.', annotations: new ToolAnnotations(readOnlyHint: true))]
    public function disponibilites(#[Schema(description: 'Langue des liens', enum: self::LANGUES)] string $langue = 'fr'): array
    {
        return ['creneaux_libres' => \count($this->creneaux->libres()), 'contact' => $this->url('app_contact', $langue)];
    }

    /** Comme le filtre Twig « loc » (Traduction), mais avec la langue demandée par l'outil plutôt que celle de la requête */
    private function loc(?object $entite, string $champ, string $langue): ?string
    {
        return null === $entite ? null : ($entite->getTraductions()[$langue][$champ] ?? $entite->{'get'.ucfirst($champ)}());
    }

    private function url(string $route, string $langue, array $parametres = []): string
    {
        $langue = \in_array($langue, self::LANGUES, true) ? $langue : 'fr';

        return $this->urls->generate($route, ['_locale' => $langue] + $parametres, UrlGeneratorInterface::ABSOLUTE_URL);
    }
}
