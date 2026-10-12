<?php

namespace App\Controller;

use App\Entity\Arrivee;
use App\EventListener\StatistiquesListener;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\RateLimiter\RateLimiterFactoryInterface;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Globe des visites (/stats, globe_controller.js) : arrivee.js signale l'arrivée d'un visiteur avec le fuseau horaire
 * de son navigateur ; le globe lit les arrivées des 30 derniers jours, puis les nouvelles au fil de l'eau.
 * Le fuseau est placé sur le globe par PHP (DateTimeZone::getLocation) : la ville de référence du fuseau.
 */
final class ArriveeController extends AbstractController
{
    /**
     * Seulement depuis le site (Sec-Fetch-Site), ni robot ni administrateur. Limite commune à tous les visiteurs
     * (config/packages/rate_limiter.yaml), sans IP : contre un flot de fausses arrivées, pas contre une personne.
     */
    #[Route('/arrivees', name: 'app_arrivee', methods: ['POST'])]
    public function signaler(Request $request, Connection $connection, Security $security, RateLimiterFactoryInterface $arriveesLimiter): Response
    {
        if ('same-origin' !== $request->headers->get('Sec-Fetch-Site', 'same-origin')) {
            return new Response(null, Response::HTTP_FORBIDDEN);
        }
        $fuseau = self::actuel($request->getPayload()->getString('fuseau'));
        if (null === $fuseau || null === self::position($fuseau)) {
            return new Response(null, Response::HTTP_BAD_REQUEST);
        }
        $agent = $request->headers->get('User-Agent', '');
        if ('' === $agent || preg_match(StatistiquesListener::ROBOTS, $agent) || $security->isGranted('ROLE_ADMIN')) {
            return new Response(null, Response::HTTP_NO_CONTENT);
        }
        if (!$arriveesLimiter->create('tous')->consume()->isAccepted()) {
            return new Response(null, Response::HTTP_TOO_MANY_REQUESTS);
        }

        $connection->executeStatement('INSERT INTO arrivee (moment, fuseau) VALUES (CURRENT_TIMESTAMP(0), :fuseau)', ['fuseau' => $fuseau]);
        $connection->executeStatement("DELETE FROM arrivee WHERE moment < CURRENT_TIMESTAMP - INTERVAL '".Arrivee::JOURS." days'");

        return new Response(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * Arrivées postérieures à ?depuis= (identifiant de la dernière reçue), ou celles des 30 derniers jours.
     * Pas l'heure exacte : seulement l'ordre, la position et le fuseau.
     */
    #[Route('/arrivees', name: 'app_arrivees', methods: ['GET'])]
    public function lister(Request $request, Connection $connection): JsonResponse
    {
        $lignes = $connection->fetchAllAssociative(
            "SELECT id, fuseau FROM arrivee WHERE id > :depuis AND moment >= CURRENT_TIMESTAMP - INTERVAL '".Arrivee::JOURS." days' ORDER BY id LIMIT 2000",
            ['depuis' => $request->query->getInt('depuis')],
        );
        $arrivees = [];
        foreach ($lignes as $ligne) {
            if ($position = self::position($ligne['fuseau'])) {
                $arrivees[] = ['id' => (int) $ligne['id'], 'fuseau' => $ligne['fuseau'], ...$position];
            }
        }

        return new JsonResponse($arrivees);
    }

    /**
     * Nom actuel du fuseau, ou null s'il n'existe pas. Certains navigateurs donnent encore un ancien nom
     * (« Asia/Calcutta », « America/Montreal ») que PHP ne sait pas placer : on prend le fuseau actuel équivalent.
     */
    public static function actuel(string $fuseau): ?string
    {
        static $actuels = null;
        if (\in_array($fuseau, \DateTimeZone::listIdentifiers(), true)) {
            return $fuseau;
        }
        if (!\in_array($fuseau, \DateTimeZone::listIdentifiers(\DateTimeZone::ALL_WITH_BC), true)) {
            return null;
        }
        // Forme canonique (ICU) → fuseau actuel, calculé la première fois seulement
        $actuels ??= array_reduce(\DateTimeZone::listIdentifiers(), function (array $carte, string $nom) {
            $carte[\IntlTimeZone::getCanonicalID($nom)] ??= $nom;

            return $carte;
        }, []);

        return $actuels[\IntlTimeZone::getCanonicalID($fuseau)] ?? null;
    }

    /** Latitude et longitude du fuseau, ou null s'il n'existe pas (ou n'a pas de lieu, comme UTC) */
    public static function position(string $fuseau): ?array
    {
        if (!\in_array($fuseau, \DateTimeZone::listIdentifiers(), true)) {
            return null;
        }
        $lieu = (new \DateTimeZone($fuseau))->getLocation();
        if (!$lieu || '??' === $lieu['country_code']) {
            return null;
        }

        return ['lat' => round($lieu['latitude'], 2), 'lon' => round($lieu['longitude'], 2)];
    }
}
