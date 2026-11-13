<?php

namespace App;

/**
 * Les dates de la base (candidatures, entretiens, créneaux) sont saisies en heure de Paris et stockées sans fuseau ;
 * PHP tourne en UTC. « today », « +12 hours »… doivent donc se calculer à Paris, puis se rendre sans fuseau
 * (dans celui de PHP) pour se comparer à ces dates : sinon, entre minuit et 2 h, « aujourd'hui » est encore la veille.
 */
final class HeureParis
{
    public static function at(string $relatif = 'now'): \DateTimeImmutable
    {
        return new \DateTimeImmutable((new \DateTimeImmutable($relatif, new \DateTimeZone('Europe/Paris')))->format('Y-m-d H:i:s'));
    }
}
