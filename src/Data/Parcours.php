<?php

namespace App\Data;

/**
 * Parcours scolaire, du plus récent au plus ancien.
 * Affiché sur la page Univers (frise) et dans le cerveau 3D (souvenirs).
 *
 * - nom : nom court, unique parmi les noms du cerveau (sélection par nom dans la légende)
 */
final class Parcours
{
    public const LISTE = [
        [
            'nom' => 'Bachelor IPSSI',
            'dates' => '2025 - 2026',
            'intitule' => 'Bachelor IA, Développement Fullstack DevOps',
            'option' => null,
            'ecole' => 'IPSSI',
            'lieu' => 'Paris',
            'resultat' => 'En cours',
        ],
        [
            'nom' => 'BTS SIO',
            'dates' => '2022 - 2024',
            'intitule' => 'SIO : Services informatiques aux organisations',
            'option' => 'Option B S.L.A.M : Solutions logicielles et Applications Métiers',
            'ecole' => 'Lycée UFA Robert Schuman',
            'lieu' => 'Dugny, Seine-Saint-Denis',
            'resultat' => 'Obtention du BTS',
        ],
        [
            'nom' => 'Bac Pro SN',
            'dates' => '2019 - 2022',
            'intitule' => 'Baccalauréat Professionnel Systèmes Numériques',
            'option' => 'Option Réseaux Informatiques et Systèmes Communicants (RISC)',
            'ecole' => 'Lycée La Salle',
            'lieu' => 'Saint-Denis, Seine-Saint-Denis',
            'resultat' => 'Obtention du BAC',
        ],
        [
            'nom' => 'Brevet',
            'dates' => '2010 - 2019',
            'intitule' => 'Brevet des collèges',
            'option' => null,
            'ecole' => 'Lycée La Salle',
            'lieu' => 'Saint-Denis, Seine-Saint-Denis',
            'resultat' => 'Obtention du brevet',
        ],
    ];
}
