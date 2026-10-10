<?php

namespace App\Tests;

use App\Entity\Competence;
use App\Entity\LienRecruteur;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class CvRecruteurTest extends WebTestCase
{
    /** CV ouvert depuis un lien recruteur : son accroche et ses points forts, jusque dans le PDF ; sans lien, le CV habituel */
    public function testLeCvSAdapteAuLienRecruteur(): void
    {
        $client = static::createClient();
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $lien = (new LienRecruteur())->setEntreprise('CV Test SA')->setAccroche('Votre stack Symfony est exactement la mienne.')
            ->addCompetence($entityManager->getRepository(Competence::class)->findOneBy(['nom' => 'Docker']));
        $entityManager->persist($lien);
        $entityManager->flush();
        $code = $lien->getCode();

        try {
            $client->request('GET', '/CV?pour='.$code);
            self::assertSelectorTextContains('body', 'Votre stack Symfony est exactement la mienne.');
            self::assertSelectorTextContains('body', 'Points forts pour ce poste : Docker');
            self::assertSelectorExists('[data-cv-download-url-value="/CV/download?pour='.$code.'"]');

            $client->request('GET', '/CV/download?pour='.$code.'&theme=light');
            self::assertResponseHeaderSame('Content-Type', 'application/pdf');

            $client->request('GET', '/CV');
            self::assertSelectorTextNotContains('body', 'Points forts pour ce poste');
            self::assertSelectorTextNotContains('body', 'Votre stack Symfony est exactement la mienne.');
        } finally {
            $entityManager = self::getContainer()->get(EntityManagerInterface::class);
            $entityManager->remove($entityManager->getRepository(LienRecruteur::class)->findOneBy(['code' => $code]));
            $entityManager->flush();
        }
    }
}
