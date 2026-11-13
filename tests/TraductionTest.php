<?php

namespace App\Tests;

use App\Entity\Maintenant;
use App\Entity\Utilisateur;
use App\Service\Journaliste;
use App\Service\Traducteur;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\AI\Platform\StructuredOutput\PlatformSubscriber;
use Symfony\AI\Platform\Test\MockPlatformFactory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;

/** Traduction automatique de l'admin (#423), avec une plateforme simulée : aucun appel à Anthropic */
final class TraductionTest extends WebTestCase
{
    /** @var list<array{input: mixed, options: array}> */
    private array $appels = [];

    /** Plateforme qui répond « [langue] texte » pour chaque champ demandé par le schéma */
    private function traducteur(): Traducteur
    {
        $dispatcher = new EventDispatcher();
        $dispatcher->addSubscriber(new PlatformSubscriber());
        $platform = MockPlatformFactory::createPlatform(function ($model, $input, array $options) {
            $this->appels[] = ['input' => $input, 'options' => $options];
            $schema = $options['response_format']['json_schema']['schema'];
            $reponse = [];
            foreach ($schema['properties'] as $langue => $objet) {
                foreach (array_keys($objet['properties']) as $champ) {
                    $reponse[$langue][$champ] = '['.$langue.'] '.$champ;
                }
            }

            return json_encode($reponse);
        }, eventDispatcher: $dispatcher);

        return new Traducteur($platform, self::getContainer()->get(Journaliste::class), ['fr', 'en', 'es']);
    }

    public function testRemplitSeulementLesTraductionsVides(): void
    {
        self::bootKernel();
        $maintenant = (new Maintenant())->setContenu("## J’apprends\n\nSymfony AI.");
        $maintenant->setTraductions(['en' => ['contenu' => 'Relue à la main']]);

        self::assertSame(['es'], $this->traducteur()->traduire($maintenant, ['contenu']));
        self::assertSame(['en' => ['contenu' => 'Relue à la main'], 'es' => ['contenu' => '[es] contenu']], $maintenant->getTraductions());
        // Seul l'espagnol est demandé, en sortie structurée
        self::assertSame(['es'], array_keys($this->appels[0]['options']['response_format']['json_schema']['schema']['properties']));

        // Tout est rempli : aucun appel
        self::assertSame([], $this->traducteur()->traduire($maintenant, ['contenu']));
        self::assertCount(1, $this->appels);
    }

    public function testRefuseUnTexteTropLong(): void
    {
        self::bootKernel();
        $this->expectException(\LengthException::class);
        $this->traducteur()->traduire((new Maintenant())->setContenu(str_repeat('a', Traducteur::LONGUEUR_MAX + 1)), ['contenu']);
    }

    public function testLeBoutonTraduireRemplitLaPageNow(): void
    {
        $client = static::createClient();
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $admin = $entityManager->getRepository(Utilisateur::class)->findOneBy(['identifiant' => 'admin-test'])
            ?? (new Utilisateur())->setIdentifiant('admin-test')->setMotDePasse('aucun-mot-de-passe');
        $entityManager->persist($admin);
        $entityManager->flush();
        $client->loginUser($admin);
        $client->disableReboot(); // garde la plateforme simulée d'une requête à l'autre
        self::getContainer()->set(Traducteur::class, $this->traducteur());

        $maintenant = $entityManager->getRepository(Maintenant::class)->findOneBy([]);
        $avant = $maintenant->getTraductions();
        $maintenant->setTraductions([]);
        $entityManager->flush();

        $client->request('GET', '/admin/maintenant/'.$maintenant->getId().'/edit');
        $client->clickLink('Traduire');
        $client->followRedirect();
        self::assertSelectorTextContains('.alert-success', 'Traduit en');

        $entityManager->clear();
        $maintenant = $entityManager->find(Maintenant::class, $maintenant->getId());
        self::assertSame('[en] contenu', $maintenant->getTraductions()['en']['contenu']);

        $maintenant->setTraductions($avant);
        $entityManager->flush();
    }
}
