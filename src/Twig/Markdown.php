<?php

namespace App\Twig;

use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\GithubFlavoredMarkdownExtension;
use League\CommonMark\Extension\HeadingPermalink\HeadingPermalinkExtension;
use League\CommonMark\MarkdownConverter;
use Twig\Attribute\AsTwigFilter;
use Twig\Attribute\AsTwigFunction;

/**
 * Markdown saisi dans l'administration → HTML : {{ article|loc('contenu')|markdown }}.
 * Le HTML brut est échappé et les liens javascript: / data: retirés : rien d'exécutable ne passe.
 * Chaque titre reçoit un id tiré de son texte (« ## Bug n°1 » → id="bug-n1") : lien direct et sommaire.
 */
final class Markdown
{
    private ?MarkdownConverter $convertisseur = null;

    #[AsTwigFilter('markdown', isSafe: ['html'])]
    public function markdown(string $texte): string
    {
        if (null === $this->convertisseur) {
            $environnement = new Environment([
                'html_input' => 'escape',
                'allow_unsafe_links' => false,
                'heading_permalink' => ['insert' => 'none', 'apply_id_to_heading' => true, 'id_prefix' => ''],
            ]);
            $environnement->addExtension(new CommonMarkCoreExtension());
            $environnement->addExtension(new GithubFlavoredMarkdownExtension());
            $environnement->addExtension(new HeadingPermalinkExtension());
            $this->convertisseur = new MarkdownConverter($environnement);
        }

        // Le titre de l'article est le seul <h1> de la page : un « # Titre » devient un <h2>
        return str_replace(['<h1', '</h1>'], ['<h2', '</h2>'], $this->convertisseur->convert($texte)->getContent());
    }

    /**
     * Sommaire d'un HTML rendu par |markdown : ses <h2>, id => texte.
     *
     * @return array<string, string>
     */
    #[AsTwigFunction('sommaire')]
    public function sommaire(string $html): array
    {
        preg_match_all('#<h2 id="([^"]+)">(.*?)</h2>#s', $html, $titres);

        return array_combine($titres[1], array_map(fn (string $titre) => html_entity_decode(strip_tags($titre)), $titres[2]));
    }
}
