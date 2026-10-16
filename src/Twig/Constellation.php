<?php

namespace App\Twig;

use Twig\Attribute\AsTwigFunction;

/**
 * Page Compétences : position des étoiles d'une constellation (une par compétence) et traits qui les relient.
 * Spirale à l'angle d'or, tournée selon la catégorie pour que les constellations ne se ressemblent pas ;
 * chaque étoile est reliée à la plus proche des précédentes (comme un arbre couvrant minimal).
 */
final class Constellation
{
    public const LARGEUR = 320;
    public const HAUTEUR = 180;
    private const ANGLE_OR = 2.39996; // radians

    /** @return array{etoiles: list<array{float, float}>, liens: list<array{int, int}>} */
    #[AsTwigFunction('constellation')]
    public function constellation(int $nombre, int $graine): array
    {
        $etoiles = [];
        $pas = min(30, 0.42 * self::HAUTEUR / sqrt(max($nombre, 1))); // une grande catégorie tient dans le cadre
        for ($i = 0; $i < $nombre; ++$i) {
            $angle = $graine * 1.3 + $i * self::ANGLE_OR;
            $rayon = $pas * sqrt($i + 0.6);
            $etoiles[] = [round(self::LARGEUR / 2 + 1.6 * $rayon * cos($angle), 1), round(self::HAUTEUR / 2 + $rayon * sin($angle), 1)];
        }

        $liens = [];
        for ($i = 1; $i < $nombre; ++$i) {
            $distances = array_map(fn (array $e) => hypot($e[0] - $etoiles[$i][0], $e[1] - $etoiles[$i][1]), \array_slice($etoiles, 0, $i));
            $liens[] = [array_search(min($distances), $distances, true), $i];
        }

        return ['etoiles' => $etoiles, 'liens' => $liens];
    }
}
