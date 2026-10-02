<?php

namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class ContactTest extends WebTestCase
{
    private const MESSAGE = [
        'contact[nom]' => 'Ada Lovelace',
        'contact[email]' => 'ada@example.com',
        'contact[message]' => 'Bonjour, une alternance vous intéresse ?',
    ];

    public function testLeMessageEstEnvoye(): void
    {
        $client = static::createClient();
        $client->request('GET', '/contact');
        $client->submitForm('Envoyer', self::MESSAGE);

        self::assertEmailCount(1);
        $email = self::getMailerMessage();
        self::assertEmailAddressContains($email, 'reply-to', 'ada@example.com');
        self::assertEmailTextBodyContains($email, 'une alternance vous intéresse');
        self::assertResponseRedirects('/contact#formulaire');

        $client->followRedirect();
        self::assertSelectorTextContains('.contact-flash--success', 'Merci');
    }

    public function testUnRobotNEnvoieRien(): void
    {
        $client = static::createClient();
        $client->request('GET', '/contact');
        $client->submitForm('Envoyer', self::MESSAGE + ['contact[website]' => 'https://spam.example']);

        self::assertEmailCount(0);
        self::assertResponseRedirects('/contact#formulaire'); // même réponse qu'un envoi réussi
    }

    public function testUnMessageInvalideEstRefuse(): void
    {
        $client = static::createClient();
        $client->request('GET', '/contact');
        $client->submitForm('Envoyer', ['contact[email]' => 'pas-un-email', 'contact[message]' => 'court'] + self::MESSAGE);

        self::assertEmailCount(0);
        self::assertResponseStatusCodeSame(422);
        self::assertSelectorExists('.contact-form ul li');
    }
}
