<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Projet Gestionnaire de serveur (Go), rédigé d'après la branche develop du dépôt ;
 * capture prise sur le serveur (tableau de bord, processus filtrés sur « docker »).
 */
final class Version20261005100000 extends AbstractMigration
{
    private const PROJET = [
        'slug' => 'gestionnaire-de-serveur',
        'titre' => 'Gestionnaire de serveur',
        'titre_en' => 'Server manager',
        'categorie' => 'Outils perso',
        'categorie_en' => 'Personal tools',
        'tech' => 'Go, WebSocket, gopacket, JWT, Docker',
        'position' => 12,
        'image' => 'outils/gestionnaire-de-serveur.webp',
        'depot' => 'https://github.com/nicoopo/Gestionnaire_de_Serveur/tree/develop',
        'description' => 'Tableau de bord web en Go pour surveiller et piloter une machine en temps réel : ressources, processus, services, réseau, matériel et fichiers.',
        'description_en' => 'Go web dashboard to monitor and control a machine in real time: resources, processes, services, network, hardware and files.',
        'details' => <<<'TXT'
            Un outil de supervision que j’ai écrit en Go : un serveur web qui tourne sur la machine à surveiller, sous Linux ou Windows, et montre dans le navigateur, en temps réel, tout ce qui s’y passe.

            Le tableau de bord affiche l’utilisation de chaque cœur du processeur, la mémoire, les disques et la carte graphique, avec un historique en graphique et des alertes quand un seuil est dépassé. On peut aussi lister et arrêter des processus, démarrer ou arrêter des services, suivre des journaux en direct, parcourir les disques (lecture, téléchargement, suppression) et voir l’inventaire du matériel : écrans, clavier, souris, carte graphique, caméra, imprimante. Un onglet capture le trafic réseau en direct, avec un filtre par protocole ou par adresse IP, le processus à l’origine de chaque connexion et la résolution DNS inverse.

            Côté technique, les mesures partent vers le navigateur par WebSocket, avec un hub et une goroutine par client, et l’accès est protégé par une authentification JWT. Le projet s’appuie sur gopsutil pour les mesures système, gopacket pour la capture réseau et WMI sous Windows pour le matériel, et il est conteneurisé avec Docker.

            La difficulté : un outil capable de lire et de supprimer des fichiers doit être sûr. L’explorateur refuse les chemins qui sortent des dossiers autorisés (path traversal), avec des tests unitaires dédiés, et chaque système d’exploitation demande son propre code pour les services et le matériel. J’en retiens beaucoup de Go concurrent (goroutines, WebSocket) et une bien meilleure idée de ce qui se passe sous le capot d’une machine.
            TXT,
        'details_en' => <<<'TXT'
            A monitoring tool I wrote in Go: a web server that runs on the machine to monitor, on Linux or Windows, and shows everything happening on it in the browser, in real time.

            The dashboard shows the load of each CPU core, memory, disks and the graphics card, with a history chart and alerts when a threshold is crossed. You can also list and kill processes, start or stop services, follow logs live, browse the disks (read, download, delete) and see the hardware inventory: screens, keyboard, mouse, graphics card, camera, printer. One tab captures network traffic live, with a filter by protocol or IP address, the process behind each connection and reverse DNS lookups.

            On the technical side, metrics are pushed to the browser over WebSocket, with a hub and one goroutine per client, and access is protected by JWT authentication. The project relies on gopsutil for system metrics, gopacket for network capture and WMI on Windows for hardware, and it is containerised with Docker.

            The challenge: a tool that can read and delete files has to be safe. The file explorer rejects paths that escape the allowed folders (path traversal), with dedicated unit tests, and each operating system needs its own code for services and hardware. What I take away: a lot of concurrent Go (goroutines, WebSocket) and a much better understanding of what happens under the hood of a machine.
            TXT,
    ];

    private const COMPETENCES = ['Linux / Windows', 'API REST', 'Docker', 'JavaScript / TypeScript', 'Curiosité'];

    public function getDescription(): string
    {
        return 'Contenu : projet Gestionnaire de serveur (Go)';
    }

    public function up(Schema $schema): void
    {
        $colonnes = array_keys(self::PROJET);
        $this->addSql(
            \sprintf('INSERT INTO projet (%s) VALUES (:%s)', implode(', ', $colonnes), implode(', :', $colonnes)),
            self::PROJET,
        );
        foreach (self::COMPETENCES as $competence) {
            $this->addSql(
                'INSERT INTO projet_competence (projet_id, competence_id) SELECT p.id, c.id FROM projet p, competence c WHERE p.slug = :slug AND c.nom = :nom',
                ['slug' => self::PROJET['slug'], 'nom' => $competence],
            );
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DELETE FROM projet_competence WHERE projet_id = (SELECT id FROM projet WHERE slug = 'gestionnaire-de-serveur')");
        $this->addSql("DELETE FROM projet WHERE slug = 'gestionnaire-de-serveur'");
    }
}
