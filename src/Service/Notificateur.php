<?php

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Notifications sur le téléphone via ntfy (https://ntfy.sh) : un lien recruteur ouvert, un message au livre d'or.
 * Sujet secret dans .env.local (NTFY_TOPIC) ; vide : rien n'est envoyé.
 * Envoyées après la réponse (kernel.terminate) : le visiteur n'attend jamais ntfy, et une panne de ntfy ne casse rien.
 */
final class Notificateur
{
    /** @var list<array{string, string, string, array<string, string>}> titre, message, étiquette, boutons */
    private array $enAttente = [];

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly LoggerInterface $logger,
        #[Autowire('%env(NTFY_SERVER)%')] private readonly string $serveur,
        #[Autowire('%env(NTFY_TOPIC)%')] private readonly string $sujet,
    ) {
    }

    /**
     * $etiquette : émoji ntfy (https://docs.ntfy.sh/emojis/), ex. « eyes », « star ».
     * $boutons : libellé => URL appelée en POST par l'appli ntfy (https://docs.ntfy.sh/publish/#http-action).
     *
     * @param array<string, string> $boutons
     */
    public function prevenir(string $titre, string $message, string $etiquette, array $boutons = []): void
    {
        if ('' !== $this->sujet) {
            $this->enAttente[] = [$titre, $message, $etiquette, $boutons];
        }
    }

    #[AsEventListener(KernelEvents::TERMINATE)]
    public function envoyer(): void
    {
        foreach ($this->enAttente as [$titre, $message, $etiquette, $boutons]) {
            try {
                $this->httpClient->request('POST', rtrim($this->serveur, '/').'/'.$this->sujet, [
                    'headers' => ['Title' => $titre, 'Tags' => $etiquette] + ($boutons ? ['Actions' => implode('; ', array_map(
                        fn (string $libelle, string $url) => "http, $libelle, $url, method=POST, clear=true",
                        array_keys($boutons),
                        $boutons,
                    ))] : []),
                    'body' => $message,
                    'timeout' => 3,
                ])->getStatusCode();
            } catch (ExceptionInterface $e) {
                $this->logger->warning('Notification ntfy non envoyée', ['exception' => $e, 'titre' => $titre]);
            }
        }
        $this->enAttente = [];
    }
}
