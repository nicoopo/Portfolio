<?php

namespace App\Tests;

use App\Service\Notificateur;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class NotificateurTest extends TestCase
{
    /** Envoyé seulement après la réponse, au bon sujet, avec titre et émoji ; une panne de ntfy ne lève rien */
    public function testLaNotificationPartApresLaReponse(): void
    {
        $requetes = [];
        $client = new MockHttpClient(function (string $methode, string $url, array $options) use (&$requetes) {
            $requetes[] = [$methode, $url, $options['body'], $options['headers']];

            return new MockResponse('', ['http_code' => 500]);
        });
        $notificateur = new Notificateur($client, new NullLogger(), 'https://ntfy.sh/', 'sujet-secret');

        $notificateur->prevenir('Lien recruteur ouvert', 'Acme vient d’ouvrir ton site', 'eyes');
        self::assertSame([], $requetes); // pas pendant la requête du visiteur

        $notificateur->envoyer();
        self::assertCount(1, $requetes);
        [$methode, $url, $corps, $entetes] = $requetes[0];
        self::assertSame(['POST', 'https://ntfy.sh/sujet-secret', 'Acme vient d’ouvrir ton site'], [$methode, $url, $corps]);
        self::assertContains('Title: Lien recruteur ouvert', $entetes);
        self::assertContains('Tags: eyes', $entetes);

        $notificateur->envoyer();
        self::assertCount(1, $requetes); // file vidée
    }

    /** Boutons : en-tête Actions de ntfy, un POST par bouton, notification effacée après le tap */
    public function testLesBoutonsDeviennentDesActionsNtfy(): void
    {
        $entetes = [];
        $client = new MockHttpClient(function (string $methode, string $url, array $options) use (&$entetes) {
            $entetes = $options['headers'];

            return new MockResponse('');
        });
        $notificateur = new Notificateur($client, new NullLogger(), 'https://ntfy.sh', 'sujet');

        $notificateur->prevenir('Livre d’or', 'Ada : bravo', 'star', ['Approuver' => 'https://exemple.fr/a?_hash=x', 'Supprimer' => 'https://exemple.fr/s?_hash=y']);
        $notificateur->envoyer();

        self::assertContains('Actions: http, Approuver, https://exemple.fr/a?_hash=x, method=POST, clear=true; http, Supprimer, https://exemple.fr/s?_hash=y, method=POST, clear=true', $entetes);
    }

    public function testSansSujetRienNePart(): void
    {
        $client = new MockHttpClient(fn () => self::fail('aucune requête attendue'));
        $notificateur = new Notificateur($client, new NullLogger(), 'https://ntfy.sh', '');

        $notificateur->prevenir('Titre', 'Message', 'star');
        $notificateur->envoyer();
        $this->addToAssertionCount(1);
    }
}
