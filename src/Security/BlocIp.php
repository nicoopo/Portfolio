<?php

namespace App\Security;

use Symfony\Component\HttpFoundation\IpUtils;
use Symfony\Component\HttpFoundation\Request;

/**
 * Clé des limites anti-abus (contact, livre d'or, découvertes, connexion) : l'IPv4 entière, mais pour l'IPv6 son bloc /64.
 * Une box reçoit tout un /64 et peut changer d'adresse à volonté : compter par adresse IPv6 ne limiterait rien.
 */
final class BlocIp
{
    public static function de(Request $request): string
    {
        $ip = $request->getClientIp();

        return $ip ? IpUtils::anonymize($ip, 0, 8) : '';
    }
}
