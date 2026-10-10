<?php

namespace App\Tests;

use App\Entity\Candidature;
use App\Entity\Creneau;
use App\Entity\LienRecruteur;
use App\Entity\StatutCandidature;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class RendezVousTest extends WebTestCase
{
    /** Le recruteur réserve un créneau depuis son lien : l'entretien s'inscrit sur sa candidature, et il récupère le .ics */
    public function testUnRecruteurReserveUnCreneau(): void
    {
        $client = static::createClient();
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $lien = (new LienRecruteur())->setEntreprise('RDV Test SA');
        $candidature = (new Candidature())->setEntreprise('RDV Test SA')->setPoste('Dev')->setLien($lien);
        $libre = (new Creneau())->setDebut(new \DateTimeImmutable('+3 days 14:00'));
        $tropProche = (new Creneau())->setDebut(new \DateTimeImmutable('+1 hour'));
        foreach ([$lien, $candidature, $libre, $tropProche] as $entite) {
            $entityManager->persist($entite);
        }
        $entityManager->flush();
        [$code, $idLibre, $idProche, $idCandidature] = [$lien->getCode(), $libre->getId(), $tropProche->getId(), $candidature->getId()];

        try {
            $client->request('GET', '/rendez-vous');
            self::assertResponseStatusCodeSame(404); // sans lien recruteur

            $client->request('GET', '/?pour='.$code, server: ['HTTP_ACCEPT_LANGUAGE' => 'fr-FR']);
            self::assertSelectorExists('a[href="/rendez-vous?pour='.$code.'"]');

            $client->request('GET', '/rendez-vous?pour='.$code);
            self::assertSelectorExists('input[name="form[creneau]"][value="'.$idLibre.'"]');
            self::assertSelectorNotExists('input[name="form[creneau]"][value="'.$idProche.'"]'); // moins de 12 heures

            $client->submitForm('Réserver ce créneau', ['form[creneau]' => $idLibre, 'form[nom]' => 'Grace', 'form[email]' => 'grace@rdv.example']);
            self::assertResponseRedirects('/rendez-vous?pour='.$code);
            $client->followRedirect();
            self::assertSelectorTextContains('.contact-flash--success', 'Entretien prévu le');
            self::assertSelectorNotExists('form.contact-form'); // un seul rendez-vous par lien

            $entityManager = self::getContainer()->get(EntityManagerInterface::class);
            $candidature = $entityManager->find(Candidature::class, $idCandidature);
            self::assertSame(StatutCandidature::Entretien, $candidature->getStatut());
            self::assertEquals($entityManager->find(Creneau::class, $idLibre)->getDebut(), $candidature->getEntretienLe());
            self::assertSame('grace@rdv.example', $candidature->getEmail());
            self::assertSame('Grace', $entityManager->find(Creneau::class, $idLibre)->getContactNom());

            $client->request('GET', '/rendez-vous/'.$idLibre.'.ics?pour='.$code);
            self::assertResponseHeaderSame('Content-Type', 'text/calendar; charset=UTF-8');
            $client->request('GET', '/rendez-vous/'.$idProche.'.ics?pour='.$code);
            self::assertResponseStatusCodeSame(404); // pas le sien
        } finally {
            $entityManager = self::getContainer()->get(EntityManagerInterface::class);
            foreach ([[Creneau::class, $idLibre], [Creneau::class, $idProche], [Candidature::class, $idCandidature]] as [$classe, $id]) {
                $entityManager->remove($entityManager->find($classe, $id));
            }
            $entityManager->remove($entityManager->getRepository(LienRecruteur::class)->findOneBy(['code' => $code]));
            $entityManager->flush();
        }
    }
}
