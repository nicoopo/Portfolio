<?php

namespace App\Data;

/**
 * Source unique des projets : page Projets + panneau des neurones du cerveau 3D.
 *
 * - slug        : ancre sur la page Projets (/projects#slug)
 * - competences : noms exacts de Competences::CATEGORIES, relient le projet aux neurones
 */
final class Projets
{
    public const CATEGORIES = [
        'Java & JavaFX' => [
            [
                'slug' => 'todolist-javafx',
                'titre' => 'Todolist avec et sans interface',
                'description' => 'Application JavaFX permettant la gestion de tâches avec une base mysql.',
                'tech' => 'JavaFX, MYSQL, MVC',
                'image' => 'java/javafx_todo.gif',
                'competences' => ['Java / Spring', 'MySQL / PostgreSQL'],
            ],
            [
                'slug' => 'pendu',
                'titre' => 'Jeu du pendu',
                'description' => 'Version terminal  .',
                'tech' => 'Java',
                'image' => 'java/pendu.png',
                'competences' => ['Java / Spring'],
            ],
            [
                'slug' => 'poupee-russe',
                'titre' => 'Poupée russe',
                'description' => 'Version terminal  .',
                'tech' => 'Java, POO',
                'image' => 'java/poupee.png',
                'competences' => ['Java / Spring'],
            ],
            [
                'slug' => 'crud-java',
                'titre' => 'CRUD',
                'description' => 'CRUD Utilisateur',
                'tech' => 'Java, POO, MYSQL',
                'image' => 'java/crud.png',
                'competences' => ['Java / Spring', 'MySQL / PostgreSQL'],
            ],
        ],
        'PHP / Symfony' => [
            [
                'slug' => 'encaissements',
                'titre' => 'WEB consultation et collaboratif Encaissements',
                'description' => 'Progiciel pour la gestion de portefeuilles clients et d’investissements.',
                'tech' => 'Symfony, UX, MYSQL',
                'image' => 'symfony/crm_finance.png',
                'competences' => ['PHP / Symfony', 'Twig', 'MySQL / PostgreSQL'],
            ],
            [
                'slug' => 'plateforme-qcm',
                'titre' => 'Plateforme de QCM',
                'description' => 'Application web de QCM pour les formations CCA, avec authentification et suivi des scores.',
                'tech' => 'Symfony, Bootstrap, MySQL',
                'image' => 'symfony/qcm_app.gif',
                'competences' => ['PHP / Symfony', 'Twig', 'MySQL / PostgreSQL', 'Docker', 'Git / GitHub'],
            ],
            [
                'slug' => 'portfolio',
                'titre' => 'Portfolio',
                'description' => 'Application web de mon parcours',
                'tech' => 'Symfony/PHP, Twig/Html/CSS, MySQL',
                'image' => 'symfony/Portfolio.png',
                'competences' => ['PHP / Symfony', 'Twig', 'HTML5', 'CSS3 / SCSS', 'JavaScript / TypeScript', 'Docker', 'Git / GitHub'],
            ],
        ],
        'Réseau / Infra' => [
            [
                'slug' => 'topologie-cisco',
                'titre' => 'Topologie Cisco virtuelle',
                'description' => 'Mise en place d’un réseau complet sous Cisco Packet Tracer avec routage dynamique.',
                'tech' => 'Cisco, VLAN, OSPF',
                'image' => 'reseau/cisco_network.png',
                'competences' => ['Cisco', 'Packet tracer'],
            ],
            [
                'slug' => 'serveur-debian',
                'titre' => 'Serveur Web Debian',
                'description' => 'Déploiement complet d’un serveur Apache/PHP sécurisé sous Debian.',
                'tech' => 'Linux, Apache2, SSH',
                'image' => 'reseau/debian_server.png',
                'competences' => ['Linux / Windows', 'Bash / Terminal'],
            ],
        ],
    ];
}
