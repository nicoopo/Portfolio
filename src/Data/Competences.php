<?php

namespace App\Data;

/**
 * Source unique des compétences : page Compétences + neurones du cerveau 3D.
 *
 * - zone    : lobe du cerveau où vivent les neurones (voir ZONES dans brain_controller.js)
 * - couleur : couleur des neurones de la catégorie
 */
final class Competences
{
    public const CATEGORIES = [
        'Front-End' => [
            'zone' => 'frontal',
            'couleur' => '#00d4ff',
            'competences' => ['HTML5', 'CSS3 / SCSS', 'JavaScript / TypeScript', 'Vue.js', 'React', 'Twig', 'Three.js'],
        ],
        'Back-End' => [
            'zone' => 'parietal',
            'couleur' => '#7f5af0',
            'competences' => ['PHP / Symfony', 'Java / Spring', 'Python', 'API REST', 'MySQL / PostgreSQL'],
        ],
        'Réseaux / Infra' => [
            'zone' => 'temporal',
            'couleur' => '#2cb67d',
            'competences' => ['Linux / Windows', 'Cisco', 'Packet tracer', 'Bash / Terminal', 'Virtualisation'],
        ],
        'Outils' => [
            'zone' => 'occipital',
            'couleur' => '#ff8906',
            'competences' => ['Git / GitHub', 'VS Code / JetBrains / Cursor', 'Docker', 'Figma', 'VirtualBox'],
        ],
        'Soft Skills' => [
            'zone' => 'limbique',
            'couleur' => '#f25f8c',
            'competences' => ['Travail en équipe', 'Autonomie', 'Rigueur', 'Curiosité', 'Bonne humeur 😄'],
        ],
    ];
}
