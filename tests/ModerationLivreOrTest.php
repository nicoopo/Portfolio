<?php

namespace App\Tests;

use App\Entity\MessageLivreOr;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\UriSigner;

final class ModerationLivreOrTest extends WebTestCase
{
    /** Bouton « Approuver » de l'alerte : lien signé accepté, lien modifié ou expiré refusé */
    public function testSeulUnLienSigneEtValideModere(): void
    {
        $client = static::createClient();
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $entityManager->persist($message = new MessageLivreOr('Ada', 'Bravo pour le site', 'fr'));
        $entityManager->flush();
        $id = $message->getId();
        // Même signataire que l'application (framework : uri_signer, clé kernel.secret)
        $signer = new UriSigner(self::getContainer()->getParameter('kernel.secret'));
        $url = 'http://localhost/livre-d-or/moderer/'.$id.'/approuver';

        $client->request('POST', $url);
        self::assertResponseStatusCodeSame(403); // pas signé

        $client->request('POST', str_replace('/approuver', '/supprimer', $signer->sign($url, new \DateTimeImmutable('+7 days'))));
        self::assertResponseStatusCodeSame(403); // signature d'une autre action

        $client->request('POST', $signer->sign($url, new \DateTimeImmutable('-1 minute')));
        self::assertResponseStatusCodeSame(403); // expiré

        $client->request('POST', $signer->sign($url, new \DateTimeImmutable('+7 days')));
        self::assertResponseIsSuccessful();
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        self::assertTrue($entityManager->find(MessageLivreOr::class, $id)->isApprouve());

        $client->request('POST', $signer->sign('http://localhost/livre-d-or/moderer/'.$id.'/supprimer', new \DateTimeImmutable('+7 days')));
        self::assertResponseIsSuccessful();
        self::assertNull(self::getContainer()->get(EntityManagerInterface::class)->find(MessageLivreOr::class, $id));
    }
}
