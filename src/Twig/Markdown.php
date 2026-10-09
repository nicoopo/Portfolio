<?php

namespace App\Twig;

use League\CommonMark\GithubFlavoredMarkdownConverter;
use Twig\Attribute\AsTwigFilter;

/**
 * Markdown saisi dans l'administration → HTML : {{ article|loc('contenu')|markdown }}.
 * Le HTML brut est échappé et les liens javascript: / data: retirés : rien d'exécutable ne passe.
 */
final class Markdown
{
    private ?GithubFlavoredMarkdownConverter $convertisseur = null;

    #[AsTwigFilter('markdown', isSafe: ['html'])]
    public function markdown(string $texte): string
    {
        $this->convertisseur ??= new GithubFlavoredMarkdownConverter(['html_input' => 'escape', 'allow_unsafe_links' => false]);

        // Le titre de l'article est le seul <h1> de la page : un « # Titre » devient un <h2>
        return str_replace(['<h1>', '</h1>'], ['<h2>', '</h2>'], $this->convertisseur->convert($texte)->getContent());
    }
}
