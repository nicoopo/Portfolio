<?php

namespace App\Repository;

use App\Entity\Arrivee;
use Doctrine\DBAL\Connection;

/** Arrivées des visiteurs pour le globe de /stats (App\Entity\Arrivee), en SQL. */
final class ArriveeRepository
{
    public function __construct(private readonly Connection $connection)
    {
    }

    /** @return array<string, int> les 5 fuseaux horaires d'où l'on arrive le plus sur 30 jours => nombre d'arrivées */
    public function fuseaux(): array
    {
        return array_map('intval', $this->connection->fetchAllKeyValue(
            "SELECT fuseau, COUNT(*) AS total FROM arrivee WHERE moment >= CURRENT_TIMESTAMP - INTERVAL '".Arrivee::JOURS." days' GROUP BY fuseau ORDER BY total DESC, fuseau LIMIT 5",
        ));
    }
}
