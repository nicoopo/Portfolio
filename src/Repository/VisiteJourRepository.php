<?php

namespace App\Repository;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;

/** Compteurs de pages vues (App\Entity\VisiteJour), en SQL : l'incrément se fait en une requête. */
final class VisiteJourRepository
{
    public function __construct(private readonly Connection $connection)
    {
    }

    /** +1 à la page du jour, en une requête : deux vues à la même seconde comptent bien deux */
    public function compter(string $chemin, string $langue, string $source): void
    {
        $this->connection->executeStatement(
            'INSERT INTO visite_jour (jour, chemin, langue, source, nombre) VALUES (CURRENT_DATE, :chemin, :langue, :source, 1)
             ON CONFLICT (jour, chemin, langue, source) DO UPDATE SET nombre = visite_jour.nombre + 1',
            ['chemin' => $chemin, 'langue' => $langue, 'source' => $source],
        );
    }

    /** @return array<string, int> pages vues des 30 derniers jours, aujourd'hui compris : 'YYYY-MM-DD' => nombre (0 sans visite) */
    public function vuesParJour(): array
    {
        $jours = [];
        for ($i = 29; $i >= 0; --$i) {
            $jours[(new \DateTimeImmutable("-$i days"))->format('Y-m-d')] = 0;
        }
        foreach ($this->connection->fetchAllKeyValue("SELECT to_char(jour, 'YYYY-MM-DD'), SUM(nombre) FROM visite_jour WHERE jour >= CURRENT_DATE - 29 GROUP BY jour") as $jour => $nombre) {
            $jours[$jour] = (int) $nombre;
        }

        return $jours;
    }

    /** Pages vues entre il y a $debut et il y a $fin jours, bornes comprises (0 : aujourd'hui) */
    public function vuesEntre(int $debut, int $fin): int
    {
        return (int) $this->connection->fetchOne("SELECT COALESCE(SUM(nombre), 0) FROM visite_jour WHERE jour BETWEEN CURRENT_DATE - $debut AND CURRENT_DATE - $fin");
    }

    public function total(): int
    {
        return (int) $this->connection->fetchOne('SELECT COALESCE(SUM(nombre), 0) FROM visite_jour');
    }

    /** Premier jour compté (YYYY-MM-DD), null sans aucune visite */
    public function depuis(): ?string
    {
        return $this->connection->fetchOne('SELECT MIN(jour) FROM visite_jour');
    }

    /**
     * Les valeurs de $colonne les plus vues sur les $jours derniers jours : « /cerveau » => 12, « /projects » => 8…
     * Sans les provenances $sansSources (« site » : la navigation interne) ; ex æquo dans l'ordre alphabétique.
     *
     * @param 'chemin'|'langue'|'source' $colonne
     * @param list<string>               $sansSources
     *
     * @return array<string, int>
     */
    public function classement(string $colonne, int $jours, ?int $limite = null, array $sansSources = []): array
    {
        if (!\in_array($colonne, ['chemin', 'langue', 'source'], true)) {
            throw new \InvalidArgumentException("Colonne inconnue : $colonne"); // nom de colonne écrit dans la requête
        }

        return array_map('intval', $this->connection->fetchAllKeyValue(
            "SELECT $colonne, SUM(nombre) AS total FROM visite_jour WHERE jour >= CURRENT_DATE - ".($jours - 1)
            .($sansSources ? ' AND source NOT IN (:sans)' : '')
            ." GROUP BY $colonne ORDER BY total DESC, $colonne".(null !== $limite ? " LIMIT $limite" : ''),
            $sansSources ? ['sans' => $sansSources] : [],
            $sansSources ? ['sans' => ArrayParameterType::STRING] : [],
        ));
    }
}
