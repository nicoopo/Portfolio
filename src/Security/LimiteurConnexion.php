<?php

namespace App\Security;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\RateLimiter\AbstractRequestRateLimiter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\RateLimiter\LimiterInterface;
use Symfony\Component\RateLimiter\RateLimiterFactoryInterface;
use Symfony\Component\Security\Http\SecurityRequestAttributes;

/**
 * Anti force brute de /login (security.yaml, login_throttling.limiter) : comme le limiteur par défaut de Symfony
 * (DefaultLoginRateLimiter, final), une limite par IP et une plus stricte par identifiant + IP, mais par bloc /64 en IPv6
 * (BlocIp) : sinon changer d'adresse dans son /64 donnerait des essais illimités.
 */
final class LimiteurConnexion extends AbstractRequestRateLimiter
{
    public function __construct(
        private readonly RateLimiterFactoryInterface $connexionIpLimiter,
        private readonly RateLimiterFactoryInterface $connexionCompteLimiter,
        #[Autowire('%kernel.secret%')] #[\SensitiveParameter] private readonly string $secret,
    ) {
    }

    /** Une connexion réussie efface la limite de ce compte, pas celle de l'IP (qui borne le nombre de comptes essayés) */
    public function reset(Request $request): void
    {
        $this->compte($request)->reset();
    }

    protected function getLimiters(Request $request): array
    {
        return [$this->connexionIpLimiter->create($this->hash(BlocIp::de($request))), $this->compte($request)];
    }

    private function compte(Request $request): LimiterInterface
    {
        $identifiant = mb_strtolower((string) $request->attributes->get(SecurityRequestAttributes::LAST_USERNAME, ''));

        return $this->connexionCompteLimiter->create($this->hash($identifiant.'-'.BlocIp::de($request)));
    }

    /** Ni IP ni identifiant en clair dans le cache des limiteurs */
    private function hash(string $donnee): string
    {
        return strtr(substr(base64_encode(hash_hmac('sha256', $donnee, $this->secret, true)), 0, 8), '/+', '._');
    }
}
