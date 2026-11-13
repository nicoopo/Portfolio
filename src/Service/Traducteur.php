<?php

namespace App\Service;

use App\Entity\Journal;
use Symfony\AI\Platform\Message\Message;
use Symfony\AI\Platform\Message\MessageBag;
use Symfony\AI\Platform\PlatformInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Traduit le français d'une entité (trait Traduisible) vers les autres langues du site, avec Claude (#423).
 * Ne remplit que les traductions vides : une traduction déjà saisie ou relue n'est jamais remplacée.
 * Appelé seulement depuis l'administration.
 */
final class Traducteur
{
    public const MODELE = 'claude-sonnet-5-5';
    /** Garde-fou de coût : texte français envoyé par appel, en caractères (un long article en fait ~15 000) */
    public const LONGUEUR_MAX = 30_000;

    /** @param list<string> $langues */
    public function __construct(
        private readonly PlatformInterface $platform,
        private readonly Journaliste $journaliste,
        #[Autowire('%kernel.enabled_locales%')] private readonly array $langues,
    ) {
    }

    /**
     * @param list<string> $champs champs de l'entité à traduire
     *
     * @return list<string> langues complétées (vide : rien à traduire)
     */
    public function traduire(object $entite, array $champs): array
    {
        $traductions = $entite->getTraductions();

        $francais = [];
        foreach ($champs as $champ) {
            $texte = $entite->{'get'.ucfirst($champ)}();
            if (\is_string($texte) && '' !== trim($texte)) {
                $francais[$champ] = $texte;
            }
        }

        // Par langue, les champs dont le français existe mais pas la traduction
        $manquants = [];
        foreach (array_diff($this->langues, ['fr']) as $langue) {
            if ($vides = array_keys(array_diff_key($francais, $traductions[$langue] ?? []))) {
                $manquants[$langue] = $vides;
            }
        }
        if (!$manquants) {
            return [];
        }

        $source = array_intersect_key($francais, array_flip(array_merge(...array_values($manquants))));
        if (mb_strlen(implode('', $source)) > self::LONGUEUR_MAX) {
            throw new \LengthException(\sprintf('Texte trop long pour une traduction automatique (%d caractères au plus).', self::LONGUEUR_MAX));
        }

        $resultat = $this->platform->invoke(self::MODELE, new MessageBag(
            Message::forSystem(
                'Tu traduis le contenu du portfolio d’un développeur web, écrit en français. Traduis chaque champ du JSON fourni '
                .'dans les langues demandées. Garde le ton, le tutoiement ou vouvoiement de l’original, les noms propres, les noms '
                .'de technologies et la syntaxe Markdown à l’identique (intertitres, listes, liens, code). Ne traduis ni les URL ni le code.'
            ),
            Message::ofUser(json_encode($source, \JSON_UNESCAPED_UNICODE | \JSON_THROW_ON_ERROR)),
        ), [
            'max_tokens' => 32_000,
            'response_format' => ['type' => 'json_schema', 'json_schema' => ['name' => 'traductions', 'strict' => true, 'schema' => self::schema($manquants)]],
        ])->asObject();

        foreach ($manquants as $langue => $vides) {
            foreach ($vides as $champ) {
                $texte = $resultat[$langue][$champ] ?? null;
                if (\is_string($texte) && '' !== trim($texte)) {
                    $traductions[$langue][$champ] = $texte;
                }
            }
        }
        $entite->setTraductions($traductions);

        $nom = $entite instanceof \Stringable ? ' « '.$entite.' »' : '';
        $this->journaliste->noter(Journal::MODIFICATION, 'Traduction automatique ('.implode(', ', array_keys($manquants)).') — '
            .(new \ReflectionClass($entite))->getShortName().$nom);

        return array_keys($manquants);
    }

    /** Un objet par langue, une chaîne par champ manquant */
    private static function schema(array $manquants): array
    {
        $objet = static fn (array $proprietes) => [
            'type' => 'object',
            'properties' => $proprietes,
            'required' => array_keys($proprietes),
            'additionalProperties' => false,
        ];

        return $objet(array_map(
            static fn (array $vides) => $objet(array_fill_keys($vides, ['type' => 'string'])),
            $manquants,
        ));
    }
}
