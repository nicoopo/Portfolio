<?php

namespace App\Tests;

use App\Entity\MessageLivreOr;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\UX\LiveComponent\Test\InteractsWithLiveComponents;

final class LivreOrFormulaireTest extends WebTestCase
{
    use InteractsWithLiveComponents;

    /** Rendu live : seul le champ quitté est validé, dans la langue de la page ; le compteur suit le message */
    public function testLeFormulaireSeValidePendantLaSaisie(): void
    {
        $client = static::createClient();
        $formulaire = $this->createLiveComponent('LivreOrFormulaire', client: $client)->setRouteLocale('en');
        // Comme dans le navigateur : formulaire invalide = composant redessiné avec ses erreurs, en 422 (l'aide de test lève l'exception)
        $client->catchExceptions(true);

        $formulaire->set('livre_or.message', 'ab')->set('validatedFields', ['livre_or.message']);
        self::assertSame(422, $formulaire->response()->getStatusCode());
        $html = (string) $formulaire->render();
        self::assertStringContainsString('2/280', $html);
        self::assertStringContainsString('too short', $html);
        self::assertStringNotContainsString('This value should not be blank', $html); // prénom pas encore quitté
        self::assertStringContainsString('action="/en/livre-d-or"', $html); // l'envoi reste le POST classique

        $formulaire->set('livre_or.message', 'Bravo pour le ciel');
        self::assertStringNotContainsString('too short', (string) $formulaire->render());
    }

    /** Sans JavaScript : le contrôleur refuse le message trop court et la page réaffiche l'erreur, rien n'est enregistré */
    public function testUnEnvoiRefuseReafficheLesErreurs(): void
    {
        $client = static::createClient(server: ['HTTP_ACCEPT_LANGUAGE' => 'fr-FR']);
        $avant = self::getContainer()->get(EntityManagerInterface::class)->getRepository(MessageLivreOr::class)->count([]);

        $client->request('GET', '/livre-d-or');
        $client->submitForm('Envoyer mon étoile', ['livre_or[prenom]' => 'Ada', 'livre_or[message]' => 'ab']);

        self::assertResponseStatusCodeSame(422);
        self::assertSelectorTextContains('.contact-form', 'trop court');
        self::assertInputValueSame('livre_or[prenom]', 'Ada');
        self::assertSame($avant, self::getContainer()->get(EntityManagerInterface::class)->getRepository(MessageLivreOr::class)->count([]));
    }
}
