<?php

namespace App\Tests;

use App\Twig\SansEmoji;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class CvTest extends WebTestCase
{
    public function testLeCvVientDeLaBaseDansChaqueLangue(): void
    {
        $client = static::createClient();

        $client->request('GET', '/CV');
        self::assertSelectorTextContains('.subtitle', 'Développeur Fullstack');
        self::assertAnySelectorTextContains('.section-title', 'Expériences Professionnelles');
        self::assertAnySelectorTextContains('.formation-status', 'mention Assez Bien'); // parcours partagé avec la page Univers

        $client->request('GET', '/en/CV');
        self::assertSelectorTextContains('.subtitle', 'Full-stack Developer');
        self::assertAnySelectorTextContains('.section-title', 'Work Experience');
        self::assertAnySelectorTextContains('.experience-title', 'IT Technician Intern');
    }

    public function testLeCvAnglaisSeTelechargeEnPdf(): void
    {
        $client = static::createClient();
        $client->request('GET', '/en/CV/download?theme=light');

        self::assertResponseHeaderSame('Content-Type', 'application/pdf');
        self::assertStringContainsString('CV_Nicolas_Cataluna_en_light.pdf', $client->getResponse()->headers->get('Content-Disposition'));
    }

    public function testLesEmojisSontRetiresPourLePdf(): void
    {
        $filtre = new SansEmoji();

        self::assertSame('Voyages : Portugal', $filtre->retirer('✈️ Voyages : Portugal'));
        self::assertSame('Musique', $filtre->retirer('🎵 Musique'));
        self::assertSame('Café — été', $filtre->retirer('Café — été')); // accents et tirets intacts
    }
}
