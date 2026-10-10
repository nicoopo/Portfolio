<?php

namespace App\Tests;

use App\Service\ActiviteGithub;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\JsonMockResponse;
use Symfony\Component\HttpClient\Response\MockResponse;

final class ActiviteGithubTest extends TestCase
{
    private static function depot(string $nom, bool $fork = false): array
    {
        return ['name' => $nom, 'description' => "Dépôt $nom", 'language' => 'PHP', 'html_url' => "https://github.com/nicoopo/$nom",
            'pushed_at' => '2026-10-09T12:00:00Z', 'fork' => $fork, 'archived' => false];
    }

    /** Dépôts sans les forks, une seule requête grâce au cache */
    public function testLesDepotsSontListesEtMisEnCache(): void
    {
        $requetes = 0;
        $client = new MockHttpClient(function (string $methode, string $url) use (&$requetes) {
            ++$requetes;
            self::assertStringStartsWith('https://api.github.com/users/nicoopo/repos?', $url);

            return new JsonMockResponse([self::depot('portfolio'), self::depot('copie', fork: true), self::depot('pendu')]);
        });
        $activite = new ActiviteGithub($client, new ArrayAdapter(), new NullLogger(), 'nicoopo');

        self::assertSame(['portfolio', 'pendu'], array_column($activite->depots(), 'nom'));
        $activite->depots();
        self::assertSame(1, $requetes);
    }

    /** GitHub en panne : liste vide, aucune exception (la page Projets s'affiche quand même) */
    public function testUnePanneDeGithubNeCasseRien(): void
    {
        $activite = new ActiviteGithub(new MockHttpClient(new MockResponse('', ['http_code' => 503])), new ArrayAdapter(), new NullLogger(), 'nicoopo');

        self::assertSame([], $activite->depots());
    }
}
