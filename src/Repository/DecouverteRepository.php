<?php

namespace App\Repository;

use Doctrine\DBAL\Connection;

/** Compteurs des easter eggs trouvés (App\Entity\Decouverte), en SQL : l'incrément se fait en une requête. */
final class DecouverteRepository
{
    public function __construct(private readonly Connection $connection)
    {
    }

    /** +1 à la découverte $id, en une requête : deux visiteurs à la même seconde comptent bien deux */
    public function compter(string $id): void
    {
        $this->connection->executeStatement(
            'INSERT INTO decouverte (id, nombre) VALUES (:id, 1) ON CONFLICT (id) DO UPDATE SET nombre = decouverte.nombre + 1',
            ['id' => $id],
        );
    }

    /** @return array<string, int> id => nombre de visiteurs qui l'ont trouvée */
    public function trouvees(): array
    {
        return array_map('intval', $this->connection->fetchAllKeyValue('SELECT id, nombre FROM decouverte'));
    }
}
