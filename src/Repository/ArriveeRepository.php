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

    /** Enregistre une arrivée et efface celles de plus de Arrivee::JOURS jours */
    public function ajouter(string $fuseau, ?float $lat, ?float $lon): void
    {
        $this->connection->executeStatement(
            'INSERT INTO arrivee (moment, fuseau, ip_lat, ip_lon) VALUES (CURRENT_TIMESTAMP(0), :fuseau, :lat, :lon)',
            ['fuseau' => $fuseau, 'lat' => $lat, 'lon' => $lon],
        );
        $this->connection->executeStatement("DELETE FROM arrivee WHERE moment < CURRENT_TIMESTAMP - INTERVAL '".Arrivee::JOURS." days'");
    }

    /** @return list<array{id: int|string, fuseau: string, ip_lat: ?string, ip_lon: ?string}> arrivées d'identifiant > $depuis sur Arrivee::JOURS jours (2000 au plus) */
    public function depuis(int $depuis): array
    {
        return $this->connection->fetchAllAssociative(
            "SELECT id, fuseau, ip_lat, ip_lon FROM arrivee WHERE id > :depuis AND moment >= CURRENT_TIMESTAMP - INTERVAL '".Arrivee::JOURS." days' ORDER BY id LIMIT 2000",
            ['depuis' => $depuis],
        );
    }

    /** @return array<string, int> les 5 fuseaux horaires d'où l'on arrive le plus sur 30 jours => nombre d'arrivées */
    public function fuseaux(): array
    {
        return array_map('intval', $this->connection->fetchAllKeyValue(
            "SELECT fuseau, COUNT(*) AS total FROM arrivee WHERE moment >= CURRENT_TIMESTAMP - INTERVAL '".Arrivee::JOURS." days' GROUP BY fuseau ORDER BY total DESC, fuseau LIMIT 5",
        ));
    }
}
