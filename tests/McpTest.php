<?php

namespace App\Tests;

use App\Entity\CvProfil;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/** Serveur MCP du portfolio (/mcp, App\Mcp\Portfolio) : protocole complet, réponses traduites, rien de privé */
final class McpTest extends WebTestCase
{
    private KernelBrowser $client;
    private ?string $session = null;

    public function testUnAssistantInterrogeLeProfil(): void
    {
        $this->client = static::createClient();

        $init = $this->rpc('initialize', ['protocolVersion' => '2025-06-18', 'capabilities' => [], 'clientInfo' => ['name' => 'test', 'version' => '1']]);
        self::assertSame('nicolascataluna.fr', $init['result']['serverInfo']['name']);
        $this->session = $this->client->getResponse()->headers->get('Mcp-Session-Id');
        self::assertNotEmpty($this->session);
        $this->rpc('notifications/initialized');

        $outils = array_column($this->rpc('tools/list')['result']['tools'], null, 'name');
        self::assertSame(['profil', 'experiences', 'competences', 'projets', 'disponibilites'], array_keys($outils));
        foreach ($outils as $nom => $outil) {
            self::assertTrue($outil['annotations']['readOnlyHint'], $nom.' : lecture seule');
        }

        $profil = $this->outil('profil', ['langue' => 'en']);
        $telephone = self::getContainer()->get(EntityManagerInterface::class)->getRepository(CvProfil::class)->findOneBy([])?->getTelephone();
        self::assertSame('Nicolas Cataluna', $profil['nom']);
        self::assertStringEndsWith('/en/contact', $profil['liens']['contact']);
        if ($telephone) {
            self::assertStringNotContainsString($telephone, json_encode($profil, \JSON_UNESCAPED_UNICODE)); // pas de téléphone
        }

        foreach ($this->outil('projets', ['techno' => 'symfony']) as $projet) {
            self::assertContains('symfony', array_map('strtolower', $projet['technologies']));
        }
        self::assertIsInt($this->outil('disponibilites')['creneaux_libres']);

        // Langue hors liste : refusée par le schéma, pas une erreur PHP
        self::assertSame(-32602, $this->rpc('tools/call', ['name' => 'profil', 'arguments' => ['langue' => 'xx']])['error']['code']);
    }

    private function outil(string $nom, array $arguments = []): array
    {
        $resultat = $this->rpc('tools/call', ['name' => $nom, 'arguments' => (object) $arguments])['result'];
        self::assertFalse($resultat['isError'] ?? false, $nom);

        return json_decode($resultat['content'][0]['text'], true, flags: \JSON_THROW_ON_ERROR);
    }

    private function rpc(string $methode, ?array $params = null): ?array
    {
        $message = ['jsonrpc' => '2.0', 'method' => $methode] + (str_starts_with($methode, 'notifications/') ? [] : ['id' => 1]) + (null === $params ? [] : ['params' => $params]);
        $this->client->request('POST', '/mcp', server: [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json, text/event-stream',
        ] + ($this->session ? ['HTTP_MCP_SESSION_ID' => $this->session] : []), content: json_encode($message));

        return json_decode($this->client->getResponse()->getContent() ?: 'null', true);
    }
}
