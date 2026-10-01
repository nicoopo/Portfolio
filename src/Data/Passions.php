<?php

namespace App\Data;

/**
 * Passions : une nébuleuse chacune autour du cerveau 3D.
 *
 * Les noms doivent être différents de ceux de Competences (sélection par nom dans la légende).
 */
final class Passions
{
    public const LISTE = [
        [
            'nom' => 'Espace',
            'couleur' => '#5b8cff',
            'description' => 'Astronomie, exploration spatiale, ce qui se passe au-delà de notre atmosphère.',
        ],
        [
            'nom' => 'Informatique',
            'couleur' => '#00d4ff',
            'description' => 'Comprendre comment les machines pensent, et construire avec elles.',
        ],
        [
            'nom' => 'Biotechnologie',
            'couleur' => '#2cb67d',
            'description' => 'Quand le vivant devient une technologie : génétique, bio-ingénierie.',
        ],
        [
            'nom' => 'Science',
            'couleur' => '#e4c35b',
            'description' => 'La curiosité méthodique : observer, douter, expérimenter.',
        ],
        [
            'nom' => 'Physique',
            'couleur' => '#a06cff',
            'description' => 'Les lois qui font tourner l’univers, de l’atome aux galaxies.',
        ],
        [
            'nom' => 'Médecine',
            'couleur' => '#f25f8c',
            'description' => 'Le corps humain, le cerveau, et la façon de les soigner.',
        ],
    ];
}
