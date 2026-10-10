<?php

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Derniers dépôts publics mis à jour sur GitHub (page Projets), via l'API publique sans jeton.
 * Mis en cache une heure : une requête par heure au plus, loin de la limite de 60 par heure.
 * GitHub en panne : liste vide (la section disparaît), retentée dans 10 minutes.
 */
final class ActiviteGithub
{
    public const NOMBRE = 4;

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly CacheInterface $cache,
        private readonly LoggerInterface $logger,
        #[Autowire('%app.github_utilisateur%')] private readonly string $utilisateur,
    ) {
    }

    /** @return list<array{nom: string, description: ?string, langage: ?string, url: string, misAJour: string}> */
    public function depots(): array
    {
        if ('' === $this->utilisateur) {
            return [];
        }

        return $this->cache->get('activite_github_'.$this->utilisateur, function (ItemInterface $item): array {
            $item->expiresAfter(3600);
            try {
                $depots = $this->httpClient->request('GET', 'https://api.github.com/users/'.rawurlencode($this->utilisateur).'/repos', [
                    'query' => ['sort' => 'pushed', 'per_page' => 20],
                    'headers' => ['Accept' => 'application/vnd.github+json', 'User-Agent' => 'nicolascataluna.fr'],
                    'timeout' => 3,
                ])->toArray();
            } catch (ExceptionInterface $e) {
                $this->logger->warning('Activité GitHub indisponible', ['exception' => $e]);
                $item->expiresAfter(600);

                return [];
            }

            $depots = array_filter($depots, fn (array $d) => !$d['fork'] && !$d['archived']);

            return array_values(array_map(fn (array $d) => [
                'nom' => $d['name'],
                'description' => $d['description'],
                'langage' => $d['language'],
                'url' => $d['html_url'],
                'misAJour' => $d['pushed_at'],
            ], \array_slice($depots, 0, self::NOMBRE)));
        });
    }
}
