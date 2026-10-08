<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Contenu de la base en espagnol, allemand, italien et portugais (colonne « traductions »).
 * Chaque traduction vise un texte français précis, reconnu à son empreinte MD5 (fins de ligne Windows ramenées à LF) : un texte modifié
 * depuis l'administration n'est pas touché (il reste en français dans ces langues, à traduire dans l'admin).
 */
final class Version20261008190000 extends AbstractMigration
{
    private const LANGUES = ['es', 'de', 'it', 'pt'];

    /** [table, champ, md5 du texte français, [langue => traduction]] */
    private const TRADUCTIONS = [
        // Réseaux / Infra
        ['categorie_competence', 'nom', '766e284e3924514480621f8944ef2109', ['es' => 'Redes / Infra', 'de' => 'Netzwerke / Infra', 'it' => 'Reti / Infra', 'pt' => 'Redes / Infra']],
        // Outils
        ['categorie_competence', 'nom', '7a095a841640eb42c13cd4ccd2f34e7d', ['es' => 'Herramientas', 'de' => 'Tools', 'it' => 'Strumenti', 'pt' => 'Ferramentas']],
        // Soft Skills
        ['categorie_competence', 'nom', '777a598047f83d94505540ab7b46432b', ['es' => 'Habilidades interpersonales', 'de' => 'Soft Skills', 'it' => 'Soft skill', 'pt' => 'Soft skills']],
        // 🎵 Musique Latino, R&B, Création musicale assistée par ord…
        ['centre_interet', 'texte', '736893d5746373546486b5993914dcbc', ['es' => '🎵 Música latina, R&B, producción musical por ordenador', 'de' => '🎵 Latin-Musik, R&B, computergestützte Musikproduktion', 'it' => '🎵 Musica latina, R&B, produzione musicale al computer', 'pt' => '🎵 Música latina, R&B, produção musical assistida por computador']],
        // ✈️ Voyages : Portugal, Espagne, France et découverte de c…
        ['centre_interet', 'texte', 'a13ef979e643bc127bbbc4b91e02e0f0', ['es' => '✈️ Viajes: Portugal, España, Francia y descubrimiento de otras culturas', 'de' => '✈️ Reisen: Portugal, Spanien, Frankreich und andere Kulturen entdecken', 'it' => '✈️ Viaggi: Portogallo, Spagna, Francia e scoperta di altre culture', 'pt' => '✈️ Viagens: Portugal, Espanha, França e descoberta de outras culturas']],
        // 💪 Sport : Musculation, Basketball
        ['centre_interet', 'texte', 'c92d725e0a391d60c22cd4a6ccb94174', ['es' => '💪 Deporte: musculación, baloncesto', 'de' => '💪 Sport: Krafttraining, Basketball', 'it' => '💪 Sport: palestra, basket', 'pt' => '💪 Desporto: musculação, basquetebol']],
        // 🔭 Physique, Astronomie
        ['centre_interet', 'texte', 'bd29671d0e115e37ad4c0dcfb513eb48', ['es' => '🔭 Física, astronomía', 'de' => '🔭 Physik, Astronomie', 'it' => '🔭 Fisica, astronomia', 'pt' => '🔭 Física, astronomia']],
        // API REST
        ['competence', 'nom', '79bd51b01f671261d7b475ead35f7611', ['es' => 'API REST', 'de' => 'REST-API', 'it' => 'API REST', 'pt' => 'API REST']],
        // Virtualisation
        ['competence', 'nom', 'd1b266439ed6947fdc7d575c85979964', ['es' => 'Virtualización', 'de' => 'Virtualisierung', 'it' => 'Virtualizzazione', 'pt' => 'Virtualização']],
        // Travail en équipe
        ['competence', 'nom', '4169b112dfa2e03cae5aa4f628b45e7c', ['es' => 'Trabajo en equipo', 'de' => 'Teamarbeit', 'it' => 'Lavoro di squadra', 'pt' => 'Trabalho em equipa']],
        // Autonomie
        ['competence', 'nom', '129c62a9e7246e2885c7f0da7d44da66', ['es' => 'Autonomía', 'de' => 'Selbstständigkeit', 'it' => 'Autonomia', 'pt' => 'Autonomia']],
        // Rigueur
        ['competence', 'nom', '922bc09f68dc2f0c9576155c9032c383', ['es' => 'Rigor', 'de' => 'Sorgfalt', 'it' => 'Rigore', 'pt' => 'Rigor']],
        // Curiosité
        ['competence', 'nom', '6ca7c34258f66dad6ba4e1b7c449bb1a', ['es' => 'Curiosidad', 'de' => 'Neugier', 'it' => 'Curiosità', 'pt' => 'Curiosidade']],
        // Bonne humeur 😄
        ['competence', 'nom', 'fc0856cc7c13e71872a6959176161762', ['es' => 'Buen humor 😄', 'de' => 'Gute Laune 😄', 'it' => 'Buonumore 😄', 'pt' => 'Boa disposição 😄']],
        // Développement Fullstack & Mobile
        ['cv_competence', 'titre', '49f12719b663a8505aabec0b15914340', ['es' => 'Desarrollo full-stack y móvil', 'de' => 'Full-Stack- & Mobile-Entwicklung', 'it' => 'Sviluppo full-stack e mobile', 'pt' => 'Desenvolvimento full-stack e mobile']],
        // BDD & Hébergement
        ['cv_competence', 'titre', '92983710f252cef49ab862407bcfcb83', ['es' => 'Bases de datos y alojamiento', 'de' => 'Datenbanken & Hosting', 'it' => 'Database e hosting', 'pt' => 'Bases de dados e alojamento']],
        // Sécurité, Réseaux & Télécom
        ['cv_competence', 'titre', '1bb02e4cc7bcf59d0a756b7a83048b94', ['es' => 'Seguridad, redes y telecomunicaciones', 'de' => 'Sicherheit, Netzwerke & Telekommunikation', 'it' => 'Sicurezza, reti e telecomunicazioni', 'pt' => 'Segurança, redes e telecomunicações']],
        // Outils & DevOps
        ['cv_competence', 'titre', '05e898548b3973508ca3fedb091120f4', ['es' => 'Herramientas y DevOps', 'de' => 'Tools & DevOps', 'it' => 'Strumenti e DevOps', 'pt' => 'Ferramentas e DevOps']],
        // Informatique Générale
        ['cv_competence', 'titre', 'a4c7ae2f57c4e69046b352c47f7a2e1f', ['es' => 'Informática general', 'de' => 'Allgemeine IT', 'it' => 'Informatica generale', 'pt' => 'Informática geral']],
        // Cisco, 802.1x, Switch/Router, Protocoles domotiques, DOMO…
        ['cv_competence', 'elements', 'f0cdaf4332782563ee0a2da1722cf967', ['es' => 'Cisco, 802.1x, switch/router, protocolos domóticos, DOMOTICZ', 'de' => 'Cisco, 802.1x, Switch/Router, Smart-Home-Protokolle, DOMOTICZ', 'it' => 'Cisco, 802.1x, switch/router, protocolli domotici, DOMOTICZ', 'pt' => 'Cisco, 802.1x, switch/router, protocolos de domótica, DOMOTICZ']],
        // Pack Office, SST/PRAP
        ['cv_competence', 'elements', '906239b6082244b7c60cf2631d70d376', ['es' => 'Microsoft Office, primeros auxilios en el trabajo (SST/PRAP)', 'de' => 'Microsoft Office, Ersthelfer im Betrieb (SST/PRAP)', 'it' => 'Microsoft Office, primo soccorso sul lavoro (SST/PRAP)', 'pt' => 'Microsoft Office, primeiros socorros no trabalho (SST/PRAP)']],
        // Développeur Fullstack
        ['cv_profil', 'titre', '774a2714a1ecae2d189612bc8634499a', ['es' => 'Desarrollador full-stack', 'de' => 'Full-Stack-Entwickler', 'it' => 'Sviluppatore full-stack', 'pt' => 'Programador full-stack']],
        // Curieux, Rigoureux, Autonome
        ['cv_profil', 'qualites', '19460ad4691157837be4ef30e977fd97', ['es' => 'Curioso, riguroso, autónomo', 'de' => 'Neugierig, sorgfältig, selbstständig', 'it' => 'Curioso, rigoroso, autonomo', 'pt' => 'Curioso, rigoroso, autónomo']],
        // Développeur full-stack junior spécialisé Symfony, motivé …
        ['cv_profil', 'resume', '58ab0af3be6f7fbb5b0cccd86a88c35b', ['es' => 'Desarrollador full-stack junior especializado en Symfony, motivado por el diseño de soluciones web eficientes y los entornos conectados (domótica). Curioso, riguroso y apasionado por las nuevas tecnologías, busco contribuir a proyectos concretos relacionados con la innovación digital.', 'de' => 'Junior-Full-Stack-Entwickler mit Schwerpunkt Symfony, begeistert von leistungsfähigen Weblösungen und vernetzten Umgebungen (Smart Home). Neugierig, sorgfältig und begeistert von neuen Technologien möchte ich an konkreten Projekten im Bereich digitale Innovation mitwirken.', 'it' => 'Sviluppatore full-stack junior specializzato in Symfony, motivato dalla progettazione di soluzioni web performanti e dagli ambienti connessi (domotica). Curioso, rigoroso e appassionato di nuove tecnologie, desidero contribuire a progetti concreti legati all\'innovazione digitale.', 'pt' => 'Programador full-stack júnior especializado em Symfony, motivado pela conceção de soluções web eficientes e pelos ambientes conectados (domótica). Curioso, rigoroso e apaixonado pelas novas tecnologias, procuro contribuir para projetos concretos ligados à inovação digital.']],
        // 🎓 En alternance chez HabitatPresto depuis janvier 2026 : …
        ['cv_profil', 'accroche', 'f3a88bd23c5aa8628991e1a6e0e0f99e', ['es' => '🎓 En formación en alternancia en HabitatPresto desde enero de 2026: Bachelor en IA, Desarrollo full-stack y DevOps (título CDA) en IPSSI
📅 Ritmo: 3 semanas en la empresa / 1 semana de formación (12 meses)', 'de' => '🎓 Duales Studium bei HabitatPresto seit Januar 2026: Bachelor KI, Full-Stack-Entwicklung & DevOps (Abschluss CDA) an der IPSSI
📅 Rhythmus: 3 Wochen im Unternehmen / 1 Woche Ausbildung (12 Monate)', 'it' => '🎓 In apprendistato presso HabitatPresto da gennaio 2026: Bachelor in IA, Sviluppo full-stack e DevOps (titolo CDA) presso IPSSI
📅 Ritmo: 3 settimane in azienda / 1 settimana di formazione (12 mesi)', 'pt' => '🎓 Em alternância na HabitatPresto desde janeiro de 2026: Bachelor em IA, Desenvolvimento full-stack e DevOps (título CDA) no IPSSI
📅 Ritmo: 3 semanas na empresa / 1 semana em formação (12 meses)']],
        // Bachelor IPSSI
        ['etape_parcours', 'nom', '049b4cebe421c115bc02e826f5f767f3', ['es' => 'Bachelor IPSSI', 'de' => 'Bachelor IPSSI', 'it' => 'Bachelor IPSSI', 'pt' => 'Bachelor IPSSI']],
        // Bac Pro SN
        ['etape_parcours', 'nom', '86fccda035923ee8e6f0b142a299c5d1', ['es' => 'Bac Pro SN', 'de' => 'Bac Pro SN', 'it' => 'Bac Pro SN', 'pt' => 'Bac Pro SN']],
        // Bachelor IA, Développement Fullstack DevOps
        ['etape_parcours', 'intitule', '27af1cb39b250e5d6a28fbac4c2ac1a4', ['es' => 'Bachelor en IA, Desarrollo full-stack y DevOps', 'de' => 'Bachelor KI, Full-Stack-Entwicklung & DevOps', 'it' => 'Bachelor in IA, Sviluppo full-stack e DevOps', 'pt' => 'Bachelor em IA, Desenvolvimento full-stack e DevOps']],
        // SIO : Services informatiques aux organisations
        ['etape_parcours', 'intitule', '9aefbfb7f62cd82bc647a8d192626c4c', ['es' => 'BTS SIO: Servicios informáticos para organizaciones (diploma nacional de técnico superior, 2 años)', 'de' => 'BTS SIO: IT-Dienstleistungen für Organisationen (staatlicher Fachhochschulabschluss, 2 Jahre)', 'it' => 'BTS SIO: Servizi informatici per le organizzazioni (diploma tecnico superiore, 2 anni)', 'pt' => 'BTS SIO: Serviços informáticos para organizações (diploma técnico superior, 2 anos)']],
        // Baccalauréat Professionnel Systèmes Numériques
        ['etape_parcours', 'intitule', '5a1f51a1a414c19d0fbb2e7912490400', ['es' => 'Bachillerato profesional en Sistemas Digitales', 'de' => 'Berufsabitur Digitale Systeme', 'it' => 'Diploma professionale in Sistemi digitali', 'pt' => 'Bacharelato profissional em Sistemas Digitais']],
        // Brevet des collèges
        ['etape_parcours', 'intitule', '581c840ddd062c687bd3ba57ecdf4ff7', ['es' => 'Brevet des collèges (título de educación secundaria)', 'de' => 'Brevet des collèges (Abschluss der Sekundarstufe I)', 'it' => 'Brevet des collèges (licenza media)', 'pt' => 'Brevet des collèges (diploma do ensino básico)']],
        // Option B S.L.A.M : Solutions logicielles et Applications …
        ['etape_parcours', 'specialite', 'a9d8793614c97b92534792ac26f427e7', ['es' => 'Opción SLAM: Soluciones de software y aplicaciones empresariales', 'de' => 'Schwerpunkt SLAM: Softwarelösungen und Geschäftsanwendungen', 'it' => 'Indirizzo SLAM: Soluzioni software e applicazioni aziendali', 'pt' => 'Opção SLAM: Soluções de software e aplicações empresariais']],
        // Option Réseaux Informatiques et Systèmes Communicants (RISC)
        ['etape_parcours', 'specialite', 'd5a6171a8f11ff931a39801a91d43201', ['es' => 'Opción: Redes informáticas y sistemas comunicantes (RISC)', 'de' => 'Schwerpunkt: Computernetzwerke und kommunizierende Systeme (RISC)', 'it' => 'Indirizzo: Reti informatiche e sistemi di comunicazione (RISC)', 'pt' => 'Opção: Redes informáticas e sistemas comunicantes (RISC)']],
        // En cours
        ['etape_parcours', 'resultat', '7202b8ffa44d203b76371de10e54d0b8', ['es' => 'En curso', 'de' => 'Laufend', 'it' => 'In corso', 'pt' => 'Em curso']],
        // Obtention du BTS
        ['etape_parcours', 'resultat', 'd13d73ce501d04ee603ebdf7a9e18413', ['es' => 'Título obtenido', 'de' => 'Abschluss erworben', 'it' => 'Diploma conseguito', 'pt' => 'Diploma obtido']],
        // Obtention du BAC, mention Assez Bien
        ['etape_parcours', 'resultat', '7b632da6f62df5adf15ae629663c875c', ['es' => 'Bachillerato obtenido con mención (Assez Bien)', 'de' => 'Abitur bestanden mit Prädikat (Assez Bien)', 'it' => 'Diploma conseguito con menzione (Assez Bien)', 'pt' => 'Bacharelato obtido com menção (Assez Bien)']],
        // Obtention du brevet
        ['etape_parcours', 'resultat', 'd277afec8727f2994a1f5f141b46a52e', ['es' => 'Título obtenido', 'de' => 'Abschluss erworben', 'it' => 'Diploma conseguito', 'pt' => 'Diploma obtido']],
        // Développeur
        ['experience', 'poste', '230e9094fd299546bc02938c264ec47d', ['es' => 'Desarrollador', 'de' => 'Entwickler', 'it' => 'Sviluppatore', 'pt' => 'Programador']],
        // Stagiaire Technicien Informatique
        ['experience', 'poste', '7f5172ea3a3c0d57963728c792edff81', ['es' => 'Técnico informático en prácticas', 'de' => 'Praktikant IT-Techniker', 'it' => 'Tecnico informatico stagista', 'pt' => 'Técnico de informática estagiário']],
        // Développeur Back-end PHP
        ['experience', 'poste', '841042c686e0beb049f91bf771ec4aa9', ['es' => 'Desarrollador back-end PHP', 'de' => 'Back-End-Entwickler PHP', 'it' => 'Sviluppatore back-end PHP', 'pt' => 'Programador back-end PHP']],
        // Mai - Juin 2022
        ['experience', 'periode', '7fb45b0cb4405f3b70857fedeea02bed', ['es' => 'Mayo - junio de 2022', 'de' => 'Mai - Juni 2022', 'it' => 'Maggio - giugno 2022', 'pt' => 'Maio - junho de 2022']],
        // Janvier 2026 - aujourd'hui
        ['experience', 'periode', '2f30e85e39192af99d396062f6155875', ['es' => 'Enero de 2026 - actualidad', 'de' => 'Januar 2026 - heute', 'it' => 'Gennaio 2026 - oggi', 'pt' => 'Janeiro de 2026 - presente']],
        // Contrat d'apprentissage
        ['experience', 'contrat', '38f20d2e28ea24012053b4e866b58549', ['es' => 'Contrato de aprendizaje', 'de' => 'Ausbildungsvertrag', 'it' => 'Contratto di apprendistato', 'pt' => 'Contrato de aprendizagem']],
        // Stage
        ['experience', 'contrat', '64c6da2436465d11573858d46056b95d', ['es' => 'Prácticas', 'de' => 'Praktikum', 'it' => 'Tirocinio', 'pt' => 'Estágio']],
        // Alternance (CDA) · télétravail partiel
        ['experience', 'contrat', 'b80fba88a7da244ad5c3f11ad53a9df9', ['es' => 'Formación en alternancia (CDA) · teletrabajo parcial', 'de' => 'Duales Studium (CDA) · teilweise remote', 'it' => 'Apprendistato (CDA) · smart working parziale', 'pt' => 'Alternância (CDA) · teletrabalho parcial']],
        // Migration de sites web d'un noyau PHP vers Symfony (domai…
        ['experience', 'missions', '91dfd0337ad978b335f3eb1c90aecb2a', ['es' => 'Migración de sitios web de un núcleo PHP antiguo a Symfony (sector del cobro de pagos)
Creación de componentes en Symfony: exportación de datos, gráficos dinámicos, tablas de datos dinámicas
Colaboración en el desarrollo de un nuevo núcleo web', 'de' => 'Migration von Websites von einem alten PHP-Kern zu Symfony (Zahlungseingang)
Entwicklung von Symfony-Komponenten: Datenexport, dynamische Diagramme, dynamische Datentabellen
Mitarbeit an der Entwicklung eines neuen Web-Kerns', 'it' => 'Migrazione di siti web da un vecchio nucleo PHP a Symfony (settore degli incassi)
Realizzazione di componenti Symfony: esportazione dati, grafici dinamici, tabelle di dati dinamiche
Collaborazione allo sviluppo di un nuovo nucleo web', 'pt' => 'Migração de sites web de um núcleo PHP antigo para Symfony (área da cobrança de pagamentos)
Criação de componentes em Symfony: exportação de dados, gráficos dinâmicos, tabelas de dados dinâmicas
Colaboração no desenvolvimento de um novo núcleo web']],
        // Création d'un support Excel répertoriant l'ensemble des s…
        ['experience', 'missions', 'b3e55bfab9edbf660852575d01d29b63', ['es' => 'Creación de un inventario en Excel de todos los servidores (comprobación de obsolescencia y estado del hardware)
Puesta en marcha de flujos de transferencia y tratamiento de datos
Configuración de la aplicación domótica DOMOTICZ
Implementación de protocolos Wi-Fi y Bluetooth (IPv6, domótica)', 'de' => 'Erstellung einer Excel-Übersicht aller Server (Prüfung auf Veralterung und Hardwarezustand)
Einrichtung von Datenübertragungs- und Verarbeitungsabläufen
Konfiguration der Smart-Home-Anwendung DOMOTICZ
Implementierung von WLAN- und Bluetooth-Protokollen (IPv6, Smart Home)', 'it' => 'Creazione di un inventario Excel di tutti i server (verifica dell\'obsolescenza e dello stato dell\'hardware)
Messa in opera di flussi di trasferimento e trattamento dei dati
Configurazione dell\'applicazione domotica DOMOTICZ
Implementazione di protocolli Wi-Fi e Bluetooth (IPv6, domotica)', 'pt' => 'Criação de um inventário Excel de todos os servidores (verificação de obsolescência e estado do hardware)
Implementação de fluxos de transferência e tratamento de dados
Configuração da aplicação de domótica DOMOTICZ
Implementação de protocolos Wi-Fi e Bluetooth (IPv6, domótica)']],
        // Plateforme de mise en relation entre particuliers et prof…
        ['experience', 'missions', '89f73b1e614f42fb93b94b8fcf326f7c', ['es' => 'Plataforma que pone en contacto a particulares con profesionales de la construcción: desarrollo de funcionalidades back-end en una aplicación Laravel con arquitectura DDD
Adecuación al RGPD/CNIL del consentimiento al seguimiento de correos (formularios, sincronización con PostgreSQL, enlaces de baja, cabeceras de correo)
Filtrado de profesionales con certificación RGE en el proceso de solicitud de presupuesto
Sustitución de una tarea cron por un envío de correos programado en base de datos, cubierto por pruebas PHPUnit
Migración de módulos de administración de un antiguo back office Zend a la nueva administración
Ampliación del envío de SMS a particulares, marcado Schema.org JSON-LD (SEO), componentes AJAX de introducción rápida', 'de' => 'Plattform, die Privatpersonen mit Bauhandwerkern zusammenbringt: Entwicklung von Back-End-Funktionen in einer Laravel-Anwendung mit DDD-Architektur
DSGVO/CNIL-konforme Einwilligung zum E-Mail-Tracking (Formulare, PostgreSQL-Synchronisierung, Abmeldelinks, E-Mail-Header)
Filterung RGE-zertifizierter Handwerker im Ablauf der Angebotsanfrage
Ersatz eines Cronjobs durch einen in der Datenbank geplanten E-Mail-Versand, abgedeckt durch PHPUnit-Tests
Migration von Verwaltungsmodulen aus einem alten Zend-Backoffice in die neue Administration
Ausweitung des SMS-Versands an Privatpersonen, Schema.org-JSON-LD-Auszeichnung (SEO), AJAX-Komponenten zur Schnelleingabe', 'it' => 'Piattaforma che mette in contatto privati e professionisti dell\'edilizia: sviluppo di funzionalità back-end su un\'applicazione Laravel con architettura DDD
Adeguamento al GDPR/CNIL del consenso al tracciamento delle e-mail (moduli, sincronizzazione PostgreSQL, link di disiscrizione, intestazioni delle e-mail)
Filtro degli artigiani certificati RGE nel percorso di richiesta di preventivo
Sostituzione di un cron con un invio di e-mail pianificato nel database, coperto da test PHPUnit
Migrazione di moduli di amministrazione da un vecchio back office Zend alla nuova amministrazione
Estensione dell\'invio di SMS ai privati, markup Schema.org JSON-LD (SEO), componenti AJAX di inserimento rapido', 'pt' => 'Plataforma que põe em contacto particulares e profissionais da construção: desenvolvimento de funcionalidades back-end numa aplicação Laravel com arquitetura DDD
Conformidade com o RGPD/CNIL do consentimento ao rastreio de e-mails (formulários, sincronização com PostgreSQL, ligações de cancelamento da subscrição, cabeçalhos de e-mail)
Filtragem de profissionais com certificação RGE no percurso de pedido de orçamento
Substituição de uma tarefa cron por um envio de e-mails agendado na base de dados, coberto por testes PHPUnit
Migração de módulos de administração de um antigo back office Zend para a nova administração
Alargamento do envio de SMS a particulares, marcação Schema.org JSON-LD (SEO), componentes AJAX de introdução rápida']],
        // Français
        ['langue', 'nom', 'e0545693906575b04aa1650abd60faba', ['es' => 'Francés', 'de' => 'Französisch', 'it' => 'Francese', 'pt' => 'Francês']],
        // Portugais
        ['langue', 'nom', '292fba65c6fd3610931fb93fd1b012e8', ['es' => 'Portugués', 'de' => 'Portugiesisch', 'it' => 'Portoghese', 'pt' => 'Português']],
        // Anglais
        ['langue', 'nom', 'b82673617c50d2c2b79893656315994a', ['es' => 'Inglés', 'de' => 'Englisch', 'it' => 'Inglese', 'pt' => 'Inglês']],
        // Espagnol
        ['langue', 'nom', '6a77de34cb21ce61732b71a27825c63a', ['es' => 'Español', 'de' => 'Spanisch', 'it' => 'Spagnolo', 'pt' => 'Espanhol']],
        // Langue maternelle
        ['langue', 'niveau', 'a00f885aa335aedcff89bb4fc639a0a9', ['es' => 'Lengua materna', 'de' => 'Muttersprache', 'it' => 'Madrelingua', 'pt' => 'Língua materna']],
        // Courant
        ['langue', 'niveau', '13c457054987cc6f46e9cba46b39a6b8', ['es' => 'Fluido', 'de' => 'Fließend', 'it' => 'Fluente', 'pt' => 'Fluente']],
        // B1
        ['langue', 'niveau', 'c9512565ef6194ca664dc41ec0de7a53', ['es' => 'B1 (intermedio)', 'de' => 'B1 (Mittelstufe)', 'it' => 'B1 (intermedio)', 'pt' => 'B1 (intermédio)']],
        // A2
        ['langue', 'niveau', 'c6bdf6f65f3845da9085e9ae5790b494', ['es' => 'A2 (básico)', 'de' => 'A2 (Grundstufe)', 'it' => 'A2 (elementare)', 'pt' => 'A2 (elementar)']],
        // Espace
        ['passion', 'nom', 'a66f02c19f97a4f049ea17fc86c5fbc3', ['es' => 'Espacio', 'de' => 'Weltraum', 'it' => 'Spazio', 'pt' => 'Espaço']],
        // Informatique
        ['passion', 'nom', 'e3db3a20b7463de9c3d7bdf78fd852a1', ['es' => 'Informática', 'de' => 'Informatik', 'it' => 'Informatica', 'pt' => 'Informática']],
        // Biotechnologie
        ['passion', 'nom', '3fe0cedeb95ae1b8518283c2faedde79', ['es' => 'Biotecnología', 'de' => 'Biotechnologie', 'it' => 'Biotecnologia', 'pt' => 'Biotecnologia']],
        // Science
        ['passion', 'nom', '35bf1210e6727959a7a88a00462db959', ['es' => 'Ciencia', 'de' => 'Wissenschaft', 'it' => 'Scienza', 'pt' => 'Ciência']],
        // Physique
        ['passion', 'nom', 'cda371792a8e564f310b48ba0004f5eb', ['es' => 'Física', 'de' => 'Physik', 'it' => 'Fisica', 'pt' => 'Física']],
        // Médecine
        ['passion', 'nom', '650eb9a5f7bd9afe5b6d7f3cbcdec0e3', ['es' => 'Medicina', 'de' => 'Medizin', 'it' => 'Medicina', 'pt' => 'Medicina']],
        // Astronomie, exploration spatiale, ce qui se passe au-delà…
        ['passion', 'description', '2202bc87a6c269ae0bd5cb58c934668b', ['es' => 'Astronomía, exploración espacial, lo que ocurre más allá de nuestra atmósfera.', 'de' => 'Astronomie, Raumfahrt, alles, was jenseits unserer Atmosphäre geschieht.', 'it' => 'Astronomia, esplorazione spaziale, ciò che accade oltre la nostra atmosfera.', 'pt' => 'Astronomia, exploração espacial, o que acontece para lá da nossa atmosfera.']],
        // Comprendre comment les machines pensent, et construire av…
        ['passion', 'description', '0fa2f3e9eda57075560e9ad26278032c', ['es' => 'Entender cómo piensan las máquinas, y construir con ellas.', 'de' => 'Verstehen, wie Maschinen denken, und mit ihnen bauen.', 'it' => 'Capire come pensano le macchine, e costruire con loro.', 'pt' => 'Compreender como as máquinas pensam, e construir com elas.']],
        // Quand le vivant devient une technologie : génétique, bio-…
        ['passion', 'description', 'fb243a473b0a308b081c2eafca2071ac', ['es' => 'Cuando lo vivo se convierte en tecnología: genética, bioingeniería.', 'de' => 'Wenn Lebendiges zur Technologie wird: Genetik, Bioengineering.', 'it' => 'Quando il vivente diventa tecnologia: genetica, bioingegneria.', 'pt' => 'Quando o ser vivo se torna tecnologia: genética, bioengenharia.']],
        // La curiosité méthodique : observer, douter, expérimenter.
        ['passion', 'description', '494de70f1458f30144b0889ff2a16d6d', ['es' => 'La curiosidad metódica: observar, dudar, experimentar.', 'de' => 'Methodische Neugier: beobachten, zweifeln, experimentieren.', 'it' => 'La curiosità metodica: osservare, dubitare, sperimentare.', 'pt' => 'A curiosidade metódica: observar, duvidar, experimentar.']],
        // Les lois qui font tourner l’univers, de l’atome aux galax…
        ['passion', 'description', '397583372287ef816ba682b7187a3ced', ['es' => 'Las leyes que hacen girar el universo, del átomo a las galaxias.', 'de' => 'Die Gesetze, die das Universum bewegen, vom Atom bis zu den Galaxien.', 'it' => 'Le leggi che fanno girare l\'universo, dall\'atomo alle galassie.', 'pt' => 'As leis que fazem girar o universo, do átomo às galáxias.']],
        // Le corps humain, le cerveau, et la façon de les soigner.
        ['passion', 'description', 'ab2452af9c5e3434115e406136d169e9', ['es' => 'El cuerpo humano, el cerebro, y la manera de curarlos.', 'de' => 'Der menschliche Körper, das Gehirn und wie man sie heilt.', 'it' => 'Il corpo umano, il cervello, e il modo di curarli.', 'pt' => 'O corpo humano, o cérebro, e a forma de os tratar.']],
        // Alternance
        ['projet', 'categorie', '3668ae530e97646269bde3e9b1edd651', ['es' => 'Formación en alternancia', 'de' => 'Duales Studium', 'it' => 'Apprendistato', 'pt' => 'Alternância']],
        // Alternance : développement back-end de la plateforme qui …
        ['projet', 'description', '4bee4fe58d3998bf141c4bac71208566', ['es' => 'Formación en alternancia: desarrollo back-end de la plataforma que pone en contacto a particulares con profesionales de la construcción.', 'de' => 'Duales Studium: Back-End-Entwicklung der Plattform, die Privatpersonen mit Bauhandwerkern zusammenbringt.', 'it' => 'Apprendistato: sviluppo back-end della piattaforma che mette in contatto privati e professionisti dell\'edilizia.', 'pt' => 'Alternância: desenvolvimento back-end da plataforma que põe em contacto particulares e profissionais da construção.']],
        // Depuis janvier 2026, je suis développeur back-end PHP en …
        ['projet', 'details', '24cf533dfbc2c095547cc82aae549e4a', ['es' => 'Desde enero de 2026 soy desarrollador back-end PHP en alternancia en HabitatPresto, una plataforma que pone en contacto a particulares con profesionales de la construcción. La aplicación está escrita en Laravel con una arquitectura DDD (Domain-Driven Design), y trabajo en equipo sobre ramas compartidas, con revisiones de código y pruebas.

Mi tema más transversal: adecuar el seguimiento de correos al RGPD y a la CNIL. Una cuestión normativa que afecta a toda la aplicación, desde los formularios de consentimiento hasta la sincronización con la base PostgreSQL, los enlaces de baja y las cabeceras de los correos.

También sustituí una tarea cron por un envío de correos programado en base de datos, más fiable, cubierto por pruebas PHPUnit y validado por el desarrollador principal. Del lado del usuario, añadí el filtrado de profesionales con certificación RGE en el proceso de solicitud de presupuesto, con una regla de negocio que depende de un intervalo de tiempo.

Por último, migré módulos de administración de un antiguo back office Zend a la nueva administración, amplié el envío de SMS a particulares y añadí el marcado Schema.org para el SEO.

Lo que me llevo: trabajar en equipo sobre una base de código grande y existente. Entender antes de modificar, dividir el trabajo con claridad y probar. El código pertenece a la empresa, por eso no está publicado.', 'de' => 'Seit Januar 2026 bin ich im dualen Studium Back-End-PHP-Entwickler bei HabitatPresto, einer Plattform, die Privatpersonen mit Bauhandwerkern zusammenbringt. Die Anwendung ist in Laravel mit einer DDD-Architektur (Domain-Driven Design) geschrieben, und ich arbeite im Team auf gemeinsamen Branches, mit Code-Reviews und Tests.

Mein übergreifendstes Thema: das E-Mail-Tracking DSGVO- und CNIL-konform zu machen. Eine regulatorische Frage, die die ganze Anwendung betrifft, von den Einwilligungsformularen über die Synchronisierung mit der PostgreSQL-Datenbank bis zu den Abmeldelinks und den E-Mail-Headern.

Außerdem habe ich einen Cronjob durch einen in der Datenbank geplanten E-Mail-Versand ersetzt, der zuverlässiger ist, durch PHPUnit-Tests abgedeckt und vom Lead-Entwickler abgenommen wurde. Auf Nutzerseite habe ich im Ablauf der Angebotsanfrage einen Filter für RGE-zertifizierte Handwerker ergänzt, mit einer Geschäftsregel, die von einem Zeitfenster abhängt.

Schließlich habe ich Verwaltungsmodule aus einem alten Zend-Backoffice in die neue Administration migriert, den SMS-Versand an Privatpersonen ausgeweitet und Schema.org-Auszeichnungen für die Suchmaschinenoptimierung hinzugefügt.

Was ich mitnehme: im Team an einer großen bestehenden Codebasis arbeiten. Erst verstehen, dann ändern, die Arbeit sauber aufteilen und testen. Der Code gehört dem Unternehmen und ist daher nicht veröffentlicht.', 'it' => 'Da gennaio 2026 sono sviluppatore back-end PHP in apprendistato presso HabitatPresto, una piattaforma che mette in contatto privati e professionisti dell\'edilizia. L\'applicazione è scritta in Laravel con un\'architettura DDD (Domain-Driven Design), e lavoro in squadra su branch condivisi, con revisioni del codice e test.

Il mio tema più trasversale: adeguare il tracciamento delle e-mail al GDPR e alle regole della CNIL. Una questione normativa che tocca tutta l\'applicazione, dai moduli di consenso alla sincronizzazione con il database PostgreSQL, fino ai link di disiscrizione e alle intestazioni delle e-mail.

Ho anche sostituito un cron con un invio di e-mail pianificato nel database, più affidabile, coperto da test PHPUnit e approvato dal lead developer. Lato utente, ho aggiunto il filtro degli artigiani certificati RGE nel percorso di richiesta di preventivo, con una regola di business che dipende da una finestra temporale.

Infine, ho migrato moduli di amministrazione da un vecchio back office Zend alla nuova amministrazione, esteso l\'invio di SMS ai privati e aggiunto il markup Schema.org per la SEO.

Cosa mi porto via: lavorare in squadra su una grande base di codice esistente. Capire prima di modificare, suddividere il lavoro con ordine e testare. Il codice appartiene all\'azienda, quindi non è pubblicato.', 'pt' => 'Desde janeiro de 2026 sou programador back-end PHP em alternância na HabitatPresto, uma plataforma que põe em contacto particulares e profissionais da construção. A aplicação está escrita em Laravel com uma arquitetura DDD (Domain-Driven Design), e trabalho em equipa em branches partilhados, com revisões de código e testes.

O meu tema mais transversal: pôr o rastreio de e-mails em conformidade com o RGPD e a CNIL. Uma questão regulamentar que atravessa toda a aplicação, dos formulários de consentimento à sincronização com a base de dados PostgreSQL, às ligações de cancelamento da subscrição e aos cabeçalhos dos e-mails.

Também substituí uma tarefa cron por um envio de e-mails agendado na base de dados, mais fiável, coberto por testes PHPUnit e aprovado pelo programador principal. Do lado do utilizador, acrescentei a filtragem de profissionais com certificação RGE no percurso de pedido de orçamento, com uma regra de negócio que depende de uma janela temporal.

Por fim, migrei módulos de administração de um antigo back office Zend para a nova administração, alarguei o envio de SMS a particulares e acrescentei a marcação Schema.org para o SEO.

O que levo daqui: trabalhar em equipa numa base de código grande e já existente. Compreender antes de alterar, dividir bem o trabalho e testar. O código pertence à empresa, por isso não está publicado.']],
        // Todolist avec et sans interface
        ['projet', 'titre', '0f52cda761f593ff5e7ef3813c8bde81', ['es' => 'Lista de tareas con y sin interfaz', 'de' => 'To-do-Liste mit und ohne Oberfläche', 'it' => 'To-do list con e senza interfaccia', 'pt' => 'Lista de tarefas com e sem interface']],
        // Gestion de listes de tâches en Java, en version terminal …
        ['projet', 'description', 'fd81c6409e8aa131f6021d78f2b64cfe', ['es' => 'Gestor de listas de tareas en Java, primero en terminal y luego con una interfaz JavaFX, sobre una base MySQL.', 'de' => 'Aufgabenlisten-Verwaltung in Java, zuerst im Terminal, dann mit einer JavaFX-Oberfläche, auf einer MySQL-Datenbank.', 'it' => 'Gestione di liste di attività in Java, prima da terminale e poi con un\'interfaccia JavaFX, su un database MySQL.', 'pt' => 'Gestor de listas de tarefas em Java, primeiro no terminal e depois com uma interface JavaFX, sobre uma base MySQL.']],
        // Un projet que j’ai repris plusieurs fois, de plusieurs fa…
        ['projet', 'details', '0ddadb5fbb8ef6b0e2d98ab4c9866837', ['es' => 'Un proyecto que reconstruí varias veces, de varias maneras: un gestor de listas de tareas en Java, primero como programa de línea de comandos y después con una interfaz gráfica JavaFX.

Los datos están en una base MySQL: usuarios que se registran e inician sesión, listas (que pueden contener sublistas) compartidas entre varios usuarios, y tareas con un tipo, una descripción y un estado hecha / no hecha. El código está organizado en capas: el modelo, el acceso a la base de datos y la presentación.

Construirlo varias veces me permitió comparar enfoques: la misma lógica de negocio, presentada primero en un terminal y luego en una interfaz gráfica.', 'de' => 'Ein Projekt, das ich mehrmals und auf verschiedene Weise neu gebaut habe: eine Aufgabenlisten-Verwaltung in Java, zuerst als Kommandozeilenprogramm, dann mit einer grafischen JavaFX-Oberfläche.

Die Daten liegen in einer MySQL-Datenbank: Nutzer, die sich registrieren und anmelden, Listen (die Unterlisten enthalten können), die mehrere Nutzer teilen, und Aufgaben mit Typ, Beschreibung und Status erledigt / offen. Der Code ist in Schichten aufgeteilt: Modell, Datenbankzugriff und Anzeige.

Durch die mehrfache Umsetzung konnte ich Ansätze vergleichen: dieselbe Geschäftslogik, einmal im Terminal und einmal in einer grafischen Oberfläche.', 'it' => 'Un progetto che ho rifatto più volte, in più modi: un gestore di liste di attività in Java, prima come programma a riga di comando e poi con un\'interfaccia grafica JavaFX.

I dati sono in un database MySQL: utenti che si registrano e accedono, liste (che possono contenere sottoliste) condivise tra più utenti, e attività con un tipo, una descrizione e uno stato fatta / da fare. Il codice è diviso in livelli: il modello, l\'accesso al database e la visualizzazione.

Costruirlo più volte mi ha permesso di confrontare gli approcci: la stessa logica di business, presentata prima in un terminale e poi in un\'interfaccia grafica.', 'pt' => 'Um projeto que reconstruí várias vezes, de várias formas: um gestor de listas de tarefas em Java, primeiro como programa de linha de comandos e depois com uma interface gráfica JavaFX.

Os dados estão numa base MySQL: utilizadores que se registam e iniciam sessão, listas (que podem conter sublistas) partilhadas entre vários utilizadores, e tarefas com um tipo, uma descrição e um estado feita / por fazer. O código está organizado em camadas: o modelo, o acesso à base de dados e a apresentação.

Construí-lo várias vezes permitiu-me comparar abordagens: a mesma lógica de negócio, apresentada primeiro num terminal e depois numa interface gráfica.']],
        // Jeu du pendu
        ['projet', 'titre', 'b49feeb98cacb8850ec48ca7597a1b84', ['es' => 'El ahorcado', 'de' => 'Galgenmännchen', 'it' => 'L\'impiccato', 'pt' => 'Jogo da forca']],
        // Le jeu du pendu en ligne de commande : un joueur choisit …
        ['projet', 'description', 'ab8060a7d29063d6c188f7129c5ec751', ['es' => 'El ahorcado en línea de comandos: un jugador elige la palabra y el otro la adivina letra a letra.', 'de' => 'Galgenmännchen im Terminal: Ein Spieler wählt das Wort, der andere errät es Buchstabe für Buchstabe.', 'it' => 'L\'impiccato da riga di comando: un giocatore sceglie la parola, l\'altro la indovina lettera per lettera.', 'pt' => 'O jogo da forca na linha de comandos: um jogador escolhe a palavra e o outro adivinha-a letra a letra.']],
        // Un de mes premiers programmes en Java : le jeu du pendu, …
        ['projet', 'details', 'b1e7a0964b791aac1fca9ba8aad720f1', ['es' => 'Uno de mis primeros programas en Java: el ahorcado, jugado en el terminal. El primer jugador escribe la palabra que hay que adivinar y la confirma; el segundo tiene diez vidas para encontrarla.

En cada turno se elige proponer una letra o la palabra entera. El programa muestra la palabra con las letras ya encontradas, el historial de letras y palabras probadas, las vidas restantes y el dibujo del ahorcado, que crece con cada error. En modo fácil, la primera y la última letra se revelan desde el principio. Al terminar una partida se puede empezar otra.

El reto era que el juego resistiera a lo que escribe el usuario: volver a preguntar cuando la respuesta no es ni «letra» ni «palabra», ignorar mayúsculas y minúsculas, no quitar una vida por una letra ya probada y rechazar una palabra de longitud incorrecta.

Lo que me llevo: las bases de la algoritmia, con bucles, condiciones, arrays y la lectura de lo que escribe el usuario.', 'de' => 'Eines meiner ersten Java-Programme: Galgenmännchen, gespielt im Terminal. Der erste Spieler gibt das zu erratende Wort ein und bestätigt es; der zweite hat zehn Leben, um es zu finden.

In jeder Runde wählt man, ob man einen Buchstaben oder das ganze Wort rät. Das Programm zeigt das Wort mit den bereits gefundenen Buchstaben, die Liste der versuchten Buchstaben und Wörter, die verbleibenden Leben und die Galgenzeichnung, die mit jedem Fehler wächst. Im leichten Modus sind der erste und der letzte Buchstabe von Anfang an aufgedeckt. Nach einer Partie kann man eine neue beginnen.

Die Herausforderung bestand darin, das Spiel robust gegenüber Benutzereingaben zu machen: erneut fragen, wenn die Antwort weder „Buchstabe“ noch „Wort“ ist, Groß- und Kleinschreibung ignorieren, für einen bereits versuchten Buchstaben kein Leben abziehen und ein Wort mit falscher Länge ablehnen.

Was ich mitnehme: die Grundlagen der Algorithmik, mit Schleifen, Bedingungen, Arrays und dem Einlesen von Benutzereingaben.', 'it' => 'Uno dei miei primi programmi in Java: l\'impiccato, giocato nel terminale. Il primo giocatore scrive la parola da indovinare e la conferma; il secondo ha dieci vite per trovarla.

A ogni turno si sceglie se proporre una lettera o la parola intera. Il programma mostra la parola con le lettere già trovate, lo storico delle lettere e delle parole provate, le vite rimaste e il disegno dell\'impiccato, che cresce a ogni errore. In modalità facile, la prima e l\'ultima lettera sono rivelate fin dall\'inizio. Finita una partita, se ne può iniziare un\'altra.

La sfida era rendere il gioco robusto rispetto a ciò che scrive l\'utente: richiedere quando la risposta non è né «lettera» né «parola», ignorare maiuscole e minuscole, non togliere una vita per una lettera già provata e rifiutare una parola di lunghezza sbagliata.

Cosa mi porto via: le basi dell\'algoritmica, con cicli, condizioni, array e la lettura dell\'input dell\'utente.', 'pt' => 'Um dos meus primeiros programas em Java: o jogo da forca, jogado no terminal. O primeiro jogador escreve a palavra a adivinhar e confirma-a; o segundo tem dez vidas para a encontrar.

Em cada jogada escolhe-se propor uma letra ou a palavra inteira. O programa mostra a palavra com as letras já encontradas, o histórico de letras e palavras tentadas, as vidas restantes e o desenho da forca, que cresce a cada erro. No modo fácil, a primeira e a última letra são reveladas desde o início. No fim de uma partida, pode começar-se outra.

O desafio era tornar o jogo robusto àquilo que o utilizador escreve: voltar a perguntar quando a resposta não é nem «letra» nem «palavra», ignorar maiúsculas e minúsculas, não tirar uma vida por uma letra já tentada e recusar uma palavra com o comprimento errado.

O que levo daqui: as bases da algoritmia, com ciclos, condições, arrays e a leitura do que o utilizador escreve.']],
        // Poupée russe
        ['projet', 'titre', 'e24131152d26ceefc019c8ea6ed21be8', ['es' => 'Muñecas rusas', 'de' => 'Matroschka', 'it' => 'Matrioska', 'pt' => 'Bonecas russas']],
        // Modélisation objet de poupées russes qui s’ouvrent, se fe…
        ['projet', 'description', '8b936c2df1664d40992197b6949a365c', ['es' => 'Modelado orientado a objetos de muñecas rusas que se abren, se cierran y se encajan según su tamaño.', 'de' => 'Objektorientiertes Modell von Matroschkas, die sich öffnen, schließen und je nach Größe ineinanderstecken lassen.', 'it' => 'Modellazione a oggetti di matrioske che si aprono, si chiudono e si incastrano secondo la loro dimensione.', 'pt' => 'Modelação orientada a objetos de bonecas russas que se abrem, se fecham e se encaixam conforme o tamanho.']],
        // Un exercice de programmation orientée objet en Java : rep…
        ['projet', 'details', '251cc89a59bd7b4475cb596ba136e87c', ['es' => 'Un ejercicio de programación orientada a objetos en Java: modelar muñecas rusas y las reglas para encajarlas unas dentro de otras.

Cada muñeca conoce su tamaño, su estado (abierta o cerrada), la muñeca que la contiene y las que contiene. Una muñeca solo puede entrar en una muñeca más grande que no esté ella misma dentro de otra; una muñeca solo puede abrirse si no está encerrada en otra, y solo se puede sacar si su contenedora está abierta. Un programa principal encadena una serie de movimientos y muestra lo que contiene cada muñeca.

El reto era convertir estas reglas en métodos (abrir, cerrar, meterDentro, sacarDe) sin dejar nunca un estado incoherente, como una muñeca guardada en dos muñecas a la vez.

Lo que me llevo: la encapsulación. El estado de cada muñeca es privado, y solo los métodos de la propia clase pueden modificarlo, respetando las reglas.', 'de' => 'Eine Übung in objektorientierter Programmierung in Java: Matroschkas und die Regeln, nach denen sie ineinandergesteckt werden, modellieren.

Jede Puppe kennt ihre Größe, ihren Zustand (offen oder geschlossen), die Puppe, in der sie steckt, und die Puppen, die sie enthält. Eine Puppe passt nur in eine größere Puppe, die nicht selbst in einer anderen steckt; eine Puppe lässt sich nur öffnen, wenn sie nicht in einer anderen eingeschlossen ist, und nur herausnehmen, wenn ihre Hülle offen ist. Ein Hauptprogramm führt eine Reihe von Zügen aus und zeigt an, was jede Puppe enthält.

Die Herausforderung bestand darin, diese Regeln in Methoden (öffnen, schließen, hineinlegen, herausnehmen) zu übersetzen, ohne je einen widersprüchlichen Zustand zu erzeugen, etwa eine Puppe, die gleichzeitig in zwei Puppen liegt.

Was ich mitnehme: die Kapselung. Der Zustand jeder Puppe ist privat, und nur die Methoden der Klasse selbst können ihn ändern, unter Einhaltung der Regeln.', 'it' => 'Un esercizio di programmazione a oggetti in Java: modellare delle matrioske e le regole per incastrarle l\'una nell\'altra.

Ogni matrioska conosce la propria dimensione, il proprio stato (aperta o chiusa), la matrioska che la contiene e quelle che contiene. Una matrioska può entrare solo in una matrioska più grande che non sia a sua volta dentro un\'altra; può essere aperta solo se non è racchiusa in un\'altra, e può essere estratta solo se la sua contenitrice è aperta. Un programma principale esegue una serie di mosse e mostra cosa contiene ogni matrioska.

La sfida era tradurre queste regole in metodi (apri, chiudi, inserisciIn, estraiDa) senza mai lasciare uno stato incoerente, come una matrioska riposta in due matrioske contemporaneamente.

Cosa mi porto via: l\'incapsulamento. Lo stato di ogni matrioska è privato, e solo i metodi della classe stessa possono modificarlo, rispettando le regole.', 'pt' => 'Um exercício de programação orientada a objetos em Java: modelar bonecas russas e as regras para as encaixar umas nas outras.

Cada boneca conhece o seu tamanho, o seu estado (aberta ou fechada), a boneca que a contém e as que contém. Uma boneca só pode entrar numa boneca maior que não esteja ela própria dentro de outra; uma boneca só pode ser aberta se não estiver fechada dentro de outra, e só pode ser retirada se a que a contém estiver aberta. Um programa principal encadeia uma série de jogadas e mostra o que cada boneca contém.

O desafio era transformar estas regras em métodos (abrir, fechar, colocarDentro, retirarDe) sem nunca deixar um estado incoerente, como uma boneca guardada em duas bonecas ao mesmo tempo.

O que levo daqui: o encapsulamento. O estado de cada boneca é privado, e só os métodos da própria classe o podem alterar, respeitando as regras.']],
        // Application JavaFX de gestion des utilisateurs : inscript…
        ['projet', 'description', '8c0b6400759dbbcc806d11aaebbce209', ['es' => 'Aplicación JavaFX de gestión de usuarios: registro, inicio de sesión, contraseña olvidada y CRUD sobre una base MySQL.', 'de' => 'JavaFX-Anwendung zur Benutzerverwaltung: Registrierung, Anmeldung, Passwort vergessen und CRUD auf einer MySQL-Datenbank.', 'it' => 'Applicazione JavaFX di gestione degli utenti: registrazione, accesso, password dimenticata e CRUD su un database MySQL.', 'pt' => 'Aplicação JavaFX de gestão de utilizadores: registo, início de sessão, palavra-passe esquecida e CRUD sobre uma base MySQL.']],
        // Une application de bureau en JavaFX qui gère des comptes …
        ['projet', 'details', 'ff3fdc24457e1978b516f5285552a717', ['es' => 'Una aplicación de escritorio JavaFX que gestiona cuentas de usuario, conectada a una base MySQL mediante JDBC.

Uno puede registrarse, iniciar sesión y restablecer una contraseña olvidada gracias a un correo enviado por la aplicación. Una vez dentro, se ve la lista de usuarios y se pueden crear, modificar o eliminar: las cuatro operaciones básicas de un CRUD (Create, Read, Update, Delete). Las pantallas se describen en FXML y el acceso a la base pasa por una clase dedicada que usa consultas preparadas.

Lo que me llevo: cómo se estructura una aplicación con interfaz de usuario, separando pantallas, lógica y acceso a los datos.', 'de' => 'Eine JavaFX-Desktopanwendung zur Verwaltung von Benutzerkonten, über JDBC mit einer MySQL-Datenbank verbunden.

Man kann sich registrieren, anmelden und ein vergessenes Passwort über eine von der Anwendung verschickte E-Mail zurücksetzen. Nach der Anmeldung sieht man die Liste der Benutzer und kann sie anlegen, bearbeiten oder löschen: die vier Grundoperationen eines CRUD (Create, Read, Update, Delete). Die Bildschirme sind in FXML beschrieben, und der Datenbankzugriff läuft über eine eigene Klasse mit Prepared Statements.

Was ich mitnehme: wie eine Anwendung mit Benutzeroberfläche aufgebaut ist, mit sauberer Trennung von Bildschirmen, Logik und Datenzugriff.', 'it' => 'Un\'applicazione desktop JavaFX che gestisce gli account utente, collegata a un database MySQL tramite JDBC.

Ci si può registrare, accedere e reimpostare una password dimenticata grazie a un\'e-mail inviata dall\'applicazione. Una volta dentro, si vede l\'elenco degli utenti e li si può creare, modificare o eliminare: le quattro operazioni di base di un CRUD (Create, Read, Update, Delete). Le schermate sono descritte in FXML, e l\'accesso al database passa da una classe dedicata che usa query preparate.

Cosa mi porto via: come si struttura un\'applicazione con interfaccia utente, separando schermate, logica e accesso ai dati.', 'pt' => 'Uma aplicação de secretária JavaFX que gere contas de utilizador, ligada a uma base MySQL através de JDBC.

É possível registar-se, iniciar sessão e redefinir uma palavra-passe esquecida graças a um e-mail enviado pela aplicação. Depois de entrar, vê-se a lista de utilizadores e é possível criá-los, alterá-los ou eliminá-los: as quatro operações básicas de um CRUD (Create, Read, Update, Delete). Os ecrãs são descritos em FXML e o acesso à base passa por uma classe dedicada que usa consultas preparadas.

O que levo daqui: como se estrutura uma aplicação com interface de utilizador, separando ecrãs, lógica e acesso aos dados.']],
        // WEB consultation et collaboratif Encaissements
        ['projet', 'titre', '44fa89d1c5afcdba0174d5e2c4d874ce', ['es' => 'Web colaborativa de consulta de cobros', 'de' => 'Kollaborative Web-App für Zahlungseingänge', 'it' => 'Web collaborativa per la consultazione degli incassi', 'pt' => 'Web colaborativa de consulta de cobranças']],
        // Alternance
        ['projet', 'categorie', '3668ae530e97646269bde3e9b1edd651', ['es' => 'Formación en alternancia', 'de' => 'Duales Studium', 'it' => 'Apprendistato', 'pt' => 'Alternância']],
        // Première alternance : migration vers Symfony d’une applic…
        ['projet', 'description', 'f7a58c5437eb2e6d894251e0b5c97130', ['es' => 'Primera alternancia: migración a Symfony de una aplicación interna de seguimiento de cobros y de carteras de clientes.', 'de' => 'Erste duale Ausbildung: Migration einer internen Anwendung zur Verfolgung von Zahlungseingängen und Kundenportfolios zu Symfony.', 'it' => 'Primo apprendistato: migrazione a Symfony di un\'applicazione interna per il monitoraggio degli incassi e dei portafogli clienti.', 'pt' => 'Primeira alternância: migração para Symfony de uma aplicação interna de acompanhamento de cobranças e de carteiras de clientes.']],
        // Ma première alternance, en contrat d’apprentissage de 202…
        ['projet', 'details', '7276b5b34f8a3cc13feae76bf766e339', ['es' => 'Mi primera alternancia, como aprendiz de 2023 a 2024, en Tessi Encaissements en Nanterre, en el sector del cobro de pagos: una aplicación web interna para consultar y seguir los cobros recibidos y gestionar de forma colaborativa las carteras de clientes y de inversiones.

Participé en la migración de sitios web de un núcleo PHP antiguo a Symfony y creé componentes Symfony: exportación de datos, gráficos dinámicos y tablas de datos dinámicas, con Symfony UX. También colaboré en el desarrollo de un nuevo núcleo web.

Allí me especialicé en Symfony, que sigue siendo hoy mi framework favorito. El código pertenece a la empresa, por eso no está publicado.', 'de' => 'Meine erste duale Ausbildung, als Auszubildender von 2023 bis 2024, bei Tessi Encaissements in Nanterre, im Bereich Zahlungseingang: eine interne Webanwendung, um eingehende Zahlungen einzusehen und zu verfolgen und Kunden- und Anlageportfolios gemeinsam zu verwalten.

Ich habe an der Migration von Websites von einem alten PHP-Kern zu Symfony mitgewirkt und Symfony-Komponenten entwickelt: Datenexport, dynamische Diagramme und dynamische Datentabellen, mit Symfony UX. Außerdem habe ich an der Entwicklung eines neuen Web-Kerns mitgearbeitet.

Dort habe ich mich auf Symfony spezialisiert, bis heute mein Lieblings-Framework. Der Code gehört dem Unternehmen und ist daher nicht veröffentlicht.', 'it' => 'Il mio primo apprendistato, dal 2023 al 2024, presso Tessi Encaissements a Nanterre, nel settore degli incassi: un\'applicazione web interna per consultare e seguire i pagamenti in entrata e gestire in modo collaborativo i portafogli di clienti e di investimenti.

Ho partecipato alla migrazione di siti web da un vecchio nucleo PHP a Symfony e realizzato componenti Symfony: esportazione dati, grafici dinamici e tabelle di dati dinamiche, con Symfony UX. Ho anche contribuito allo sviluppo di un nuovo nucleo web.

È lì che mi sono specializzato in Symfony, ancora oggi il mio framework preferito. Il codice appartiene all\'azienda, quindi non è pubblicato.', 'pt' => 'A minha primeira alternância, como aprendiz de 2023 a 2024, na Tessi Encaissements em Nanterre, na área da cobrança de pagamentos: uma aplicação web interna para consultar e acompanhar os pagamentos recebidos e gerir de forma colaborativa as carteiras de clientes e de investimentos.

Participei na migração de sites web de um núcleo PHP antigo para Symfony e criei componentes Symfony: exportação de dados, gráficos dinâmicos e tabelas de dados dinâmicas, com Symfony UX. Também colaborei no desenvolvimento de um novo núcleo web.

Foi aí que me especializei em Symfony, que continua a ser hoje o meu framework preferido. O código pertence à empresa, por isso não está publicado.']],
        // Plateforme de QCM
        ['projet', 'titre', '8fc2929f5a3347cff4cb083ef472d21e', ['es' => 'Plataforma de tests', 'de' => 'Multiple-Choice-Plattform', 'it' => 'Piattaforma di quiz', 'pt' => 'Plataforma de testes']],
        // Plateforme web et mobile d’entraînement aux QCM pour les …
        ['projet', 'description', '4121fc2c5fbf885992d080998cde55be', ['es' => 'Plataforma web y móvil de entrenamiento con tests de opción múltiple para los alumnos de la formación Cabin Crew Attestation (CCA).', 'de' => 'Web- und Mobilplattform zum Üben von Multiple-Choice-Fragen für Teilnehmende der Ausbildung Cabin Crew Attestation (CCA).', 'it' => 'Piattaforma web e mobile di allenamento con quiz a risposta multipla per gli allievi della formazione Cabin Crew Attestation (CCA).', 'pt' => 'Plataforma web e móvel de treino com testes de escolha múltipla para os alunos da formação Cabin Crew Attestation (CCA).']],
        // Un projet commercial, réalisé pour un client à partir de …
        ['projet', 'details', '1e292fccd144f8923d92eee3021daa92', ['es' => 'Un proyecto comercial, realizado para un cliente a partir de su pliego de condiciones: una plataforma en la que los alumnos de la formación Cabin Crew Attestation (CCA), que se preparan para ser tripulantes de cabina, practican con un banco de unas 2500 preguntas clasificadas por módulo.

Los alumnos hacen tests por módulo, aleatorios o personalizados (preguntas falladas, preguntas marcadas), en modo entrenamiento o en modo examen. El examen de prueba reproduce las condiciones reales: 70 preguntas en 1 h 45, un 85 % para aprobar, y después un resumen por módulo con la corrección de cada pregunta. El acceso es de pago (pago en línea con Stripe), y una aplicación Android lleva la plataforma al móvil.

En lo técnico: Symfony 8 y PostgreSQL, Turbo para una navegación fluida, un back office para gestionar preguntas y cuentas, todo en Docker. Cada cambio pasa por la integración continua (pruebas automáticas) y un análisis de calidad SonarQube, y después se despliega automáticamente en un servidor de preproducción.

El reto era pasar de un pliego de condiciones a una aplicación que se pueda entregar y hacer evolucionar con confianza: dividir el trabajo en pasos pequeños y poner en marcha las pruebas, la preproducción y el seguimiento de la calidad que permiten cambiar el código sin romper lo que ya funciona.

Lo que me llevo: lo que implica un proyecto para un cliente real. Entender su negocio, priorizar con él y cuidar la fiabilidad tanto como las funcionalidades.', 'de' => 'Ein kommerzielles Projekt, für einen Kunden nach seinem Lastenheft umgesetzt: eine Plattform, auf der Teilnehmende der Ausbildung Cabin Crew Attestation (CCA), die Flugbegleiter werden wollen, mit einem Fragenkatalog von rund 2.500 nach Modulen sortierten Fragen üben.

Die Teilnehmenden absolvieren Quizze nach Modul, zufällig oder individuell zusammengestellt (falsch beantwortete oder markierte Fragen), im Übungs- oder im Prüfungsmodus. Die Probeprüfung bildet die echten Bedingungen nach: 70 Fragen in 1 Std. 45 Min., 85 % zum Bestehen, danach eine Auswertung pro Modul mit der Korrektur jeder Frage. Der Zugang ist kostenpflichtig (Online-Zahlung mit Stripe), und eine Android-App bringt die Plattform aufs Smartphone.

Technisch: Symfony 8 und PostgreSQL, Turbo für eine flüssige Navigation, ein Backoffice zur Verwaltung von Fragen und Konten, alles in Docker. Jede Änderung durchläuft die Continuous Integration (automatisierte Tests) und eine Qualitätsanalyse mit SonarQube und wird dann automatisch auf einen Pre-Production-Server ausgerollt.

Die Herausforderung: von einem Lastenheft zu einer Anwendung zu kommen, die man mit Vertrauen ausliefern und weiterentwickeln kann. Die Arbeit in kleine Schritte aufteilen und Tests, Pre-Production und Qualitätskontrolle einrichten, damit man den Code ändern kann, ohne Funktionierendes kaputtzumachen.

Was ich mitnehme: was ein Projekt für einen echten Kunden bedeutet. Sein Geschäft verstehen, mit ihm priorisieren und auf Zuverlässigkeit ebenso achten wie auf Funktionen.', 'it' => 'Un progetto commerciale, realizzato per un cliente a partire dal suo capitolato: una piattaforma su cui gli allievi della formazione Cabin Crew Attestation (CCA), che si preparano a diventare assistenti di volo, si esercitano su una banca di circa 2.500 domande ordinate per modulo.

Gli allievi fanno quiz per modulo, casuali o personalizzati (domande sbagliate, domande segnate), in modalità allenamento o esame. L\'esame simulato riproduce le condizioni reali: 70 domande in 1 h 45, 85 % per superarlo, poi un riepilogo per modulo con la correzione di ogni domanda. L\'accesso è a pagamento (pagamento online con Stripe), e un\'app Android porta la piattaforma sullo smartphone.

Sul piano tecnico: Symfony 8 e PostgreSQL, Turbo per una navigazione fluida, un back office per gestire domande e account, tutto in Docker. Ogni modifica passa dall\'integrazione continua (test automatici) e da un\'analisi di qualità SonarQube, poi viene distribuita automaticamente su un server di preproduzione.

La sfida era passare da un capitolato a un\'applicazione che si possa consegnare e far evolvere con fiducia: suddividere il lavoro in piccoli passi e mettere in piedi test, preproduzione e monitoraggio della qualità che permettono di cambiare il codice senza rompere ciò che già funziona.

Cosa mi porto via: cosa comporta un progetto per un vero cliente. Capire il suo mestiere, stabilire le priorità con lui e curare l\'affidabilità quanto le funzionalità.', 'pt' => 'Um projeto comercial, realizado para um cliente a partir do seu caderno de encargos: uma plataforma em que os alunos da formação Cabin Crew Attestation (CCA), que se preparam para ser tripulantes de cabine, treinam com um banco de cerca de 2500 perguntas organizadas por módulo.

Os alunos fazem testes por módulo, aleatórios ou personalizados (perguntas falhadas, perguntas marcadas), em modo de treino ou de exame. O exame simulado reproduz as condições reais: 70 perguntas em 1 h 45, 85 % para passar, seguido de um resumo por módulo com a correção de cada pergunta. O acesso é pago (pagamento online com Stripe), e uma aplicação Android leva a plataforma para o telemóvel.

Do lado técnico: Symfony 8 e PostgreSQL, Turbo para uma navegação fluida, um back office para gerir perguntas e contas, tudo em Docker. Cada alteração passa pela integração contínua (testes automáticos) e por uma análise de qualidade SonarQube, sendo depois implementada automaticamente num servidor de pré-produção.

O desafio era passar de um caderno de encargos a uma aplicação que se pode entregar e fazer evoluir com confiança: dividir o trabalho em pequenos passos e montar os testes, a pré-produção e o acompanhamento da qualidade que permitem alterar o código sem estragar o que já funciona.

O que levo daqui: o que implica um projeto para um cliente real. Compreender o seu negócio, definir prioridades com ele e cuidar da fiabilidade tanto como das funcionalidades.']],
        // Ce site : compétences, projets et parcours, à explorer da…
        ['projet', 'description', 'b5d3cb5fb57054740ea2965458a6ef89', ['es' => 'Este sitio: competencias, proyectos y trayectoria, para explorar en un cerebro 3D interactivo.', 'de' => 'Diese Website: Kenntnisse, Projekte und Werdegang, in einem interaktiven 3D-Gehirn zu entdecken.', 'it' => 'Questo sito: competenze, progetti e percorso, da esplorare in un cervello 3D interattivo.', 'pt' => 'Este site: competências, projetos e percurso, para explorar num cérebro 3D interativo.']],
        // Mon portfolio n'est pas une simple vitrine : c'est un pro…
        ['projet', 'details', 'dacb5769dfe97111bbfa1b5ce5ccd1d4', ['es' => 'Mi portfolio no es solo un escaparate: es un proyecto completo, construido como una aplicación. La idea central es un cerebro en 3D en el que cada neurona es una competencia, conectada por sinapsis a otras competencias y a los proyectos que la usan. Mi trayectoria forma un hilo de recuerdos, y mis pasiones son nebulosas a su alrededor.

El cerebro es una nube de puntos generada con Three.js: pliegues del córtex en relieve, iluminación por punto, nebulosas, efecto bloom y un ambiente sonoro sintetizado con la API Web Audio. Uno puede moverse libremente, buscar una competencia, seguir una visita guiada o compartir un enlace a una vista concreta.

En el servidor funciona con Symfony 8, PostgreSQL y Doctrine: todo el contenido (competencias, proyectos, trayectoria, CV) viene de la base de datos y se edita desde un back office EasyAdmin protegido (cuentas en base de datos, límite de intentos de conexión, registro de actividad). El CV se genera a partir de la base, en HTML y en PDF, en seis idiomas, como el resto del sitio.

El proyecto funciona en Docker, con pruebas automáticas (PHPUnit), atención a la accesibilidad (navegación con teclado, lectores de pantalla, movimiento reducido) y al rendimiento (recursos en caché, renderizado 3D más ligero en móvil).', 'de' => 'Mein Portfolio ist nicht nur ein Schaufenster: Es ist ein vollständiges Projekt, gebaut wie eine Anwendung. Die Grundidee ist ein 3D-Gehirn, in dem jedes Neuron eine Kenntnis ist, über Synapsen mit anderen Kenntnissen und mit den Projekten verbunden, die sie nutzen. Mein Werdegang bildet einen Faden aus Erinnerungen, und meine Leidenschaften sind Nebel rundherum.

Das Gehirn ist eine mit Three.js erzeugte Punktwolke: plastische Hirnwindungen, Beleuchtung pro Punkt, Nebel, Bloom-Effekt und eine mit der Web Audio API synthetisierte Klangkulisse. Man kann sich frei bewegen, nach einer Kenntnis suchen, einer geführten Tour folgen oder einen Link zu einer bestimmten Ansicht teilen.

Auf dem Server läuft Symfony 8 mit PostgreSQL und Doctrine: Alle Inhalte (Kenntnisse, Projekte, Werdegang, Lebenslauf) kommen aus der Datenbank und werden in einem geschützten EasyAdmin-Backoffice bearbeitet (Konten in der Datenbank, Begrenzung der Anmeldeversuche, Aktivitätsprotokoll). Der Lebenslauf wird aus der Datenbank erzeugt, als HTML und PDF, in sechs Sprachen, wie der Rest der Website.

Das Projekt läuft in Docker, mit automatisierten Tests (PHPUnit), Augenmerk auf Barrierefreiheit (Tastaturbedienung, Screenreader, reduzierte Bewegung) und Performance (gecachte Assets, leichteres 3D-Rendering auf Mobilgeräten).', 'it' => 'Il mio portfolio non è solo una vetrina: è un progetto completo, costruito come un\'applicazione. L\'idea di fondo è un cervello in 3D in cui ogni neurone è una competenza, collegata tramite sinapsi ad altre competenze e ai progetti che la usano. Il mio percorso forma un filo di ricordi, e le mie passioni sono nebulose tutto intorno.

Il cervello è una nuvola di punti generata con Three.js: circonvoluzioni della corteccia in rilievo, illuminazione per punto, nebulose, effetto bloom e un\'atmosfera sonora sintetizzata con la Web Audio API. Ci si può muovere liberamente, cercare una competenza, seguire una visita guidata o condividere un link a una vista precisa.

Lato server gira su Symfony 8 con PostgreSQL e Doctrine: tutto il contenuto (competenze, progetti, percorso, CV) viene dal database e si modifica da un back office EasyAdmin protetto (account nel database, limite ai tentativi di accesso, registro delle attività). Il CV è generato dal database, in HTML e in PDF, in sei lingue, come il resto del sito.

Il progetto gira in Docker, con test automatici (PHPUnit), attenzione all\'accessibilità (navigazione da tastiera, lettori di schermo, movimento ridotto) e alle prestazioni (risorse in cache, rendering 3D più leggero su mobile).', 'pt' => 'O meu portfólio não é apenas uma montra: é um projeto completo, construído como uma aplicação. A ideia central é um cérebro em 3D em que cada neurónio é uma competência, ligada por sinapses a outras competências e aos projetos que a utilizam. O meu percurso forma um fio de memórias, e as minhas paixões são nebulosas à volta.

O cérebro é uma nuvem de pontos gerada com Three.js: circunvoluções do córtex em relevo, iluminação por ponto, nebulosas, efeito bloom e um ambiente sonoro sintetizado com a Web Audio API. É possível mover-se livremente, pesquisar uma competência, seguir uma visita guiada ou partilhar uma ligação para uma vista específica.

No servidor, corre em Symfony 8 com PostgreSQL e Doctrine: todo o conteúdo (competências, projetos, percurso, CV) vem da base de dados e é editado num back office EasyAdmin protegido (contas na base de dados, limite de tentativas de início de sessão, registo de atividade). O CV é gerado a partir da base de dados, em HTML e em PDF, em seis línguas, como o resto do site.

O projeto corre em Docker, com testes automáticos (PHPUnit), atenção à acessibilidade (navegação por teclado, leitores de ecrã, movimento reduzido) e ao desempenho (recursos em cache, renderização 3D mais leve no telemóvel).']],
        // Projet d’équipe : une application qui analyse le code d’u…
        ['projet', 'description', '455b6fc707207ebe9ab268d1da182190', ['es' => 'Proyecto en equipo: una aplicación que analiza el código de un proyecto según el Top 10 de OWASP y propone correcciones.', 'de' => 'Teamprojekt: eine Anwendung, die den Code eines Projekts anhand der OWASP Top 10 analysiert und Korrekturen vorschlägt.', 'it' => 'Progetto di squadra: un\'applicazione che analizza il codice di un progetto secondo la Top 10 OWASP e propone correzioni.', 'pt' => 'Projeto de equipa: uma aplicação que analisa o código de um projeto segundo o Top 10 da OWASP e propõe correções.']],
        // Un projet réalisé en équipe de cinq : SecureScan analyse …
        ['projet', 'details', 'c53d431d66b4f9f0227f4821ac1af8aa', ['es' => 'Un proyecto realizado en un equipo de cinco: SecureScan analiza la seguridad de un proyecto web. Se le entrega un repositorio Git o un archivo ZIP, y revisa el código según las diez categorías del Top 10 de OWASP, la lista de referencia de los riesgos de seguridad de las aplicaciones web.

Cada categoría tiene su propio analizador: uno de ellos, por ejemplo, busca en los archivos de composer y npm dependencias bloqueadas en versiones con vulnerabilidades conocidas. Los problemas encontrados se clasifican por gravedad, vienen con propuestas de corrección (el código original, el código corregido y una explicación) y se resumen en un panel con gráficos y en un informe PDF.

Creé el repositorio y organicé el trabajo del equipo con Git: una rama principal estable, una rama de desarrollo, una rama por funcionalidad y pull requests para integrar las aportaciones de cada uno. También soy el principal contribuidor del proyecto.

Lo que me llevo: trabajar con otros sobre el mismo código, y conocer mejor las vulnerabilidades web más comunes.', 'de' => 'Ein Projekt im Fünferteam: SecureScan analysiert die Sicherheit eines Webprojekts. Man reicht ein Git-Repository oder ein ZIP-Archiv ein, und der Code wird anhand der zehn Kategorien der OWASP Top 10 geprüft, der Referenzliste der Sicherheitsrisiken von Webanwendungen.

Jede Kategorie hat ihren eigenen Analysator: Einer davon durchsucht zum Beispiel die composer- und npm-Dateien nach Abhängigkeiten, die auf Versionen mit bekannten Schwachstellen festgelegt sind. Die gefundenen Probleme werden nach Schweregrad eingestuft, mit Korrekturvorschlägen versehen (ursprünglicher Code, korrigierter Code und eine Erklärung) und in einem Dashboard mit Diagrammen sowie in einem PDF-Bericht zusammengefasst.

Ich habe das Repository angelegt und die Arbeit des Teams mit Git organisiert: ein stabiler Hauptbranch, ein Entwicklungsbranch, ein Branch pro Feature und Pull Requests, um die Beiträge aller zusammenzuführen. Außerdem bin ich der Hauptbeitragende des Projekts.

Was ich mitnehme: mit anderen an derselben Codebasis arbeiten, und ein besseres Verständnis der häufigsten Web-Schwachstellen.', 'it' => 'Un progetto realizzato in una squadra di cinque: SecureScan analizza la sicurezza di un progetto web. Gli si fornisce un repository Git o un archivio ZIP, e controlla il codice secondo le dieci categorie della Top 10 OWASP, l\'elenco di riferimento dei rischi di sicurezza delle applicazioni web.

Ogni categoria ha il suo analizzatore: uno di questi, per esempio, cerca nei file di composer e npm le dipendenze ferme a versioni con vulnerabilità note. I problemi trovati sono classificati per gravità, accompagnati da proposte di correzione (il codice originale, il codice corretto e una spiegazione) e riassunti in una dashboard con grafici e in un report PDF.

Ho creato il repository e organizzato il lavoro della squadra con Git: un branch principale stabile, un branch di sviluppo, un branch per ogni funzionalità e pull request per integrare i contributi di ciascuno. Sono anche il principale contributore del progetto.

Cosa mi porto via: lavorare con altri sullo stesso codice, e una migliore conoscenza delle vulnerabilità web più comuni.', 'pt' => 'Um projeto realizado numa equipa de cinco: o SecureScan analisa a segurança de um projeto web. Entrega-se um repositório Git ou um arquivo ZIP, e ele verifica o código segundo as dez categorias do Top 10 da OWASP, a lista de referência dos riscos de segurança das aplicações web.

Cada categoria tem o seu próprio analisador: um deles, por exemplo, procura nos ficheiros do composer e do npm dependências presas em versões com vulnerabilidades conhecidas. Os problemas encontrados são classificados por gravidade, acompanhados de propostas de correção (o código original, o código corrigido e uma explicação) e resumidos num painel com gráficos e num relatório PDF.

Criei o repositório e organizei o trabalho da equipa com Git: um branch principal estável, um branch de desenvolvimento, um branch por funcionalidade e pull requests para integrar os contributos de cada um. Sou também o principal contribuidor do projeto.

O que levo daqui: trabalhar com outros sobre o mesmo código, e conhecer melhor as vulnerabilidades web mais comuns.']],
        // Topologie Cisco virtuelle
        ['projet', 'titre', '57f14ce7f159e9bb2638a6167b168e04', ['es' => 'Topología Cisco virtual', 'de' => 'Virtuelle Cisco-Topologie', 'it' => 'Topologia Cisco virtuale', 'pt' => 'Topologia Cisco virtual']],
        // Réseau / Infra
        ['projet', 'categorie', '9bcdc1f8ae891ea8f4f1ecf72ad66594', ['es' => 'Redes / Infra', 'de' => 'Netzwerk / Infra', 'it' => 'Reti / Infra', 'pt' => 'Redes / Infra']],
        // Mise en place d’un réseau complet sous Cisco Packet Trace…
        ['projet', 'description', '9654134c91f54a58b07de11a05b21ccb', ['es' => 'Montaje de una red completa en Cisco Packet Tracer con enrutamiento dinámico.', 'de' => 'Aufbau eines vollständigen Netzwerks in Cisco Packet Tracer mit dynamischem Routing.', 'it' => 'Realizzazione di una rete completa con Cisco Packet Tracer e routing dinamico.', 'pt' => 'Montagem de uma rede completa no Cisco Packet Tracer com encaminhamento dinâmico.']],
        // Un projet réseau réalisé dans le cadre de mes cours : con…
        ['projet', 'details', '819f509d3dba3f78a29f4b6504cef3f5', ['es' => 'Un proyecto de redes de mis estudios: diseñar y configurar una red completa en Cisco Packet Tracer, el simulador de redes de Cisco.

La red está dividida en VLAN, y los routers intercambian sus rutas automáticamente con el protocolo de enrutamiento dinámico OSPF. Una maqueta así permite validar una arquitectura y su configuración antes de aplicarlas a equipos reales.', 'de' => 'Ein Netzwerkprojekt aus meinem Studium: ein vollständiges Netzwerk in Cisco Packet Tracer, dem Netzwerksimulator von Cisco, entwerfen und konfigurieren.

Das Netzwerk ist in VLANs aufgeteilt, und die Router tauschen ihre Routen automatisch über das dynamische Routingprotokoll OSPF aus. Mit einem solchen Modell lassen sich eine Architektur und ihre Konfiguration prüfen, bevor man sie auf echte Hardware überträgt.', 'it' => 'Un progetto di reti dei miei studi: progettare e configurare una rete completa con Cisco Packet Tracer, il simulatore di reti di Cisco.

La rete è suddivisa in VLAN, e i router si scambiano automaticamente le rotte con il protocollo di routing dinamico OSPF. Un modello di questo tipo permette di validare un\'architettura e la sua configurazione prima di applicarle a dispositivi reali.', 'pt' => 'Um projeto de redes dos meus estudos: conceber e configurar uma rede completa no Cisco Packet Tracer, o simulador de redes da Cisco.

A rede está dividida em VLAN, e os routers trocam as suas rotas automaticamente com o protocolo de encaminhamento dinâmico OSPF. Uma maqueta assim permite validar uma arquitetura e a sua configuração antes de as aplicar a equipamento real.']],
        // Serveur Web Debian
        ['projet', 'titre', '544daaf8c381443f35b330cbee7b88fb', ['es' => 'Servidor web Debian', 'de' => 'Debian-Webserver', 'it' => 'Server web Debian', 'pt' => 'Servidor web Debian']],
        // Réseau / Infra
        ['projet', 'categorie', '9bcdc1f8ae891ea8f4f1ecf72ad66594', ['es' => 'Redes / Infra', 'de' => 'Netzwerk / Infra', 'it' => 'Reti / Infra', 'pt' => 'Redes / Infra']],
        // Déploiement complet d’un serveur Apache/PHP sécurisé sous…
        ['projet', 'description', 'c34b1da9463a86c60d2a5fe5fc6b0e31', ['es' => 'Despliegue completo de un servidor Apache/PHP seguro en Debian.', 'de' => 'Vollständige Bereitstellung eines abgesicherten Apache/PHP-Servers unter Debian.', 'it' => 'Messa in opera completa di un server Apache/PHP protetto su Debian.', 'pt' => 'Implementação completa de um servidor Apache/PHP seguro em Debian.']],
        // Un projet système réalisé dans le cadre de mes cours : in…
        ['projet', 'details', '197095031afaf51eae8ae9116877615d', ['es' => 'Un proyecto de sistemas de mis estudios: instalar y configurar un servidor web en Debian, desde la instalación hasta la puesta en producción.

Apache2 sirve páginas PHP, el servidor se administra a distancia por SSH y el despliegue incluye su securización.', 'de' => 'Ein Systemprojekt aus meinem Studium: einen Webserver unter Debian installieren und konfigurieren, von der Installation bis zur Inbetriebnahme.

Apache2 liefert PHP-Seiten aus, der Server wird per SSH aus der Ferne verwaltet, und die Bereitstellung umfasst seine Absicherung.', 'it' => 'Un progetto di sistemi dei miei studi: installare e configurare un server web su Debian, dall\'installazione alla messa in produzione.

Apache2 serve pagine PHP, il server è amministrato da remoto via SSH e la messa in opera comprende la sua protezione.', 'pt' => 'Um projeto de sistemas dos meus estudos: instalar e configurar um servidor web em Debian, da instalação à entrada em produção.

O Apache2 serve páginas PHP, o servidor é administrado remotamente por SSH e a implementação inclui a sua segurança.']],
        // Outils perso
        ['projet', 'categorie', 'bfee08a28682a395883122e6bd4cca33', ['es' => 'Herramientas propias', 'de' => 'Eigene Tools', 'it' => 'Strumenti personali', 'pt' => 'Ferramentas pessoais']],
        // Un mini Heroku perso : un seul binaire Go qui déploie un …
        ['projet', 'description', '56507d7088b1c7bd378868cafc72f90b', ['es' => 'Un mini Heroku propio: un único binario Go que despliega un repositorio Git en un contenedor Docker, controlado desde un panel web.', 'de' => 'Ein eigenes Mini-Heroku: ein einziges Go-Binary, das ein Git-Repository in einem Docker-Container bereitstellt, gesteuert über ein Web-Dashboard.', 'it' => 'Un mini Heroku personale: un unico binario Go che distribuisce un repository Git in un container Docker, gestito da una dashboard web.', 'pt' => 'Um mini Heroku pessoal: um único binário Go que implementa um repositório Git num contentor Docker, controlado a partir de um painel web.']],
        // Un outil que j’ai écrit pour déployer mes propres projets…
        ['projet', 'details', '1bf93b1edb361b5c998866a552d8b248', ['es' => 'Una herramienta que escribí para desplegar mis propios proyectos sin proveedor de alojamiento: un pequeño «PaaS» al estilo de Heroku que cabe en un único binario Go, sin dependencias externas.

Desde el panel web se declara un proyecto (URL del repositorio Git, rama, variables de entorno, puertos) y se pulsa Desplegar. La herramienta clona o actualiza el repositorio, construye la imagen a partir de su Dockerfile, arranca el contenedor y muestra toda la salida del despliegue. Los proyectos se guardan en un simple archivo JSON, y el panel está protegido por contraseña.

La decisión fue quedarse en lo mínimo: solo la biblioteca estándar de Go, que controla los comandos git y docker ya instalados en la máquina, en lugar de un SDK. Los límites asumidos están anotados en el código, como el despliegue síncrono, junto con el camino a seguir: una tarea en segundo plano y un registro en directo.

Lo que me llevo: lo bien que encaja Go en este tipo de herramientas. Un único binario, fácil de instalar, y una biblioteca estándar que cubre servidor web, procesos y JSON.', 'de' => 'Ein Tool, das ich geschrieben habe, um meine eigenen Projekte ohne Hoster bereitzustellen: ein kleines „PaaS“ im Stil von Heroku, das in ein einziges Go-Binary ohne externe Abhängigkeiten passt.

Im Web-Dashboard legt man ein Projekt an (URL des Git-Repositorys, Branch, Umgebungsvariablen, Ports) und klickt auf Bereitstellen. Das Tool klont oder aktualisiert das Repository, baut das Image aus seinem Dockerfile, startet den Container und zeigt die komplette Ausgabe der Bereitstellung. Die Projekte werden in einer einfachen JSON-Datei gespeichert, und das Dashboard ist passwortgeschützt.

Die Entscheidung war, minimal zu bleiben: nur die Standardbibliothek von Go, die die bereits installierten Befehle git und docker steuert, statt eines SDK. Die bewussten Grenzen sind im Code vermerkt, etwa die synchrone Bereitstellung, zusammen mit dem weiteren Weg: ein Hintergrundjob und ein Live-Log.

Was ich mitnehme: wie gut Go zu dieser Art von Tool passt. Ein einziges Binary, leicht zu installieren, und eine Standardbibliothek, die Webserver, Prozesse und JSON abdeckt.', 'it' => 'Uno strumento che ho scritto per distribuire i miei progetti senza un provider di hosting: un piccolo «PaaS» in stile Heroku che sta in un unico binario Go, senza dipendenze esterne.

Dalla dashboard web si dichiara un progetto (URL del repository Git, branch, variabili d\'ambiente, porte) e si clicca su Distribuisci. Lo strumento clona o aggiorna il repository, costruisce l\'immagine dal suo Dockerfile, avvia il container e mostra tutto l\'output della distribuzione. I progetti sono salvati in un semplice file JSON, e la dashboard è protetta da password.

La scelta è stata restare minimali: solo la libreria standard di Go, che pilota i comandi git e docker già installati sulla macchina, invece di un SDK. I limiti voluti sono annotati nel codice, come la distribuzione sincrona, insieme alla strada da seguire: un job in background e un log in tempo reale.

Cosa mi porto via: quanto Go si adatta a questo tipo di strumento. Un unico binario, facile da installare, e una libreria standard che copre server web, processi e JSON.', 'pt' => 'Uma ferramenta que escrevi para implementar os meus próprios projetos sem fornecedor de alojamento: um pequeno «PaaS» ao estilo do Heroku que cabe num único binário Go, sem dependências externas.

No painel web, declara-se um projeto (URL do repositório Git, branch, variáveis de ambiente, portas) e clica-se em Implementar. A ferramenta clona ou atualiza o repositório, constrói a imagem a partir do seu Dockerfile, arranca o contentor e mostra toda a saída da implementação. Os projetos são guardados num simples ficheiro JSON, e o painel está protegido por palavra-passe.

A opção foi ficar no mínimo: apenas a biblioteca padrão do Go, que controla os comandos git e docker já instalados na máquina, em vez de um SDK. Os limites assumidos estão anotados no código, como a implementação síncrona, juntamente com o caminho a seguir: uma tarefa em segundo plano e um registo em direto.

O que levo daqui: como o Go se adapta bem a este tipo de ferramenta. Um único binário, fácil de instalar, e uma biblioteca padrão que cobre servidor web, processos e JSON.']],
        // Tableau de bord OBD
        ['projet', 'titre', '953abd7a07ab7b55c8b86f2ab8e73977', ['es' => 'Panel OBD', 'de' => 'OBD-Dashboard', 'it' => 'Dashboard OBD', 'pt' => 'Painel OBD']],
        // Outils perso
        ['projet', 'categorie', 'bfee08a28682a395883122e6bd4cca33', ['es' => 'Herramientas propias', 'de' => 'Eigene Tools', 'it' => 'Strumenti personali', 'pt' => 'Ferramentas pessoais']],
        // Lire en direct les données moteur et les codes défaut de …
        ['projet', 'description', '73e5cbb8311dd71e777a133eddb92016', ['es' => 'Leer en directo los datos del motor y los códigos de avería de mi coche desde un navegador, gracias a un adaptador OBD-II.', 'de' => 'Motordaten und Fehlercodes meines Autos live im Browser auslesen, mit einem OBD-II-Adapter.', 'it' => 'Leggere in tempo reale i dati del motore e i codici di errore della mia auto da un browser, grazie a un adattatore OBD-II.', 'pt' => 'Ler em direto os dados do motor e os códigos de avaria do meu carro num navegador, graças a um adaptador OBD-II.']],
        // Un outil né d’un besoin concret : savoir ce qui se passe …
        ['projet', 'details', 'f6d7e9a073839f8471d4df937d8b6bcc', ['es' => 'Una herramienta nacida de una necesidad real: saber qué pasa en mi coche, un Audi A3, sin software de taller. Un adaptador ELM327 se conecta a la toma de diagnóstico OBD-II y al PC por USB.

Una aplicación en Python (FastAPI) lee los datos con la biblioteca python-OBD y los envía en tiempo real a un panel web por WebSocket: régimen del motor, velocidad, temperatura del refrigerante, tensión de la batería, nivel de combustible y posición del acelerador, con un gráfico del régimen sobre las últimas lecturas. También se pueden leer los códigos de avería, con su descripción, y borrarlos.

Todo el historial se guarda en una base SQLite y se puede exportar en CSV. La aplicación funciona en local, sin cuenta ni servidor remoto, y arranca aunque el coche no esté conectado: basta con volver a conectarse cuando el adaptador esté listo.

El reto viene del hardware: la biblioteca solo conoce los códigos de avería genéricos, así que los códigos propios del grupo Volkswagen aparecen sin descripción. Lo que me llevo: el placer de programar para un uso muy concreto, y un primer contacto con la comunicación con hardware.', 'de' => 'Ein Tool aus einem echten Bedarf heraus: wissen, was in meinem Auto, einem Audi A3, vor sich geht, ohne Werkstattsoftware. Ein ELM327-Adapter wird in die OBD-II-Diagnosebuchse gesteckt und per USB mit dem PC verbunden.

Eine Python-Anwendung (FastAPI) liest die Daten mit der Bibliothek python-OBD und überträgt sie per WebSocket in Echtzeit an ein Web-Dashboard: Motordrehzahl, Geschwindigkeit, Kühlmitteltemperatur, Batteriespannung, Tankfüllstand und Drosselklappenstellung, mit einem Diagramm der Drehzahl über die letzten Messwerte. Man kann auch die Fehlercodes samt Beschreibung auslesen und löschen.

Der gesamte Verlauf wird in einer SQLite-Datenbank gespeichert und lässt sich als CSV exportieren. Die Anwendung läuft lokal, ohne Konto oder entfernten Server, und startet auch, wenn das Auto nicht verbunden ist: Man verbindet sich neu, sobald der Adapter bereit ist.

Die Herausforderung liegt in der Hardware: Die Bibliothek kennt nur die generischen Fehlercodes, daher erscheinen die herstellerspezifischen Codes des Volkswagen-Konzerns ohne Beschreibung. Was ich mitnehme: die Freude, für einen ganz konkreten Zweck zu programmieren, und einen ersten Einblick in die Kommunikation mit Hardware.', 'it' => 'Uno strumento nato da un bisogno reale: sapere cosa succede nella mia auto, un\'Audi A3, senza software da officina. Un adattatore ELM327 si collega alla presa diagnostica OBD-II e al PC via USB.

Un\'applicazione Python (FastAPI) legge i dati con la libreria python-OBD e li invia in tempo reale a una dashboard web via WebSocket: regime del motore, velocità, temperatura del liquido di raffreddamento, tensione della batteria, livello del carburante e posizione dell\'acceleratore, con un grafico del regime sulle ultime letture. Si possono anche leggere i codici di errore, con la loro descrizione, e cancellarli.

Tutto lo storico è salvato in un database SQLite ed è esportabile in CSV. L\'app gira in locale, senza account né server remoto, e si avvia anche quando l\'auto non è collegata: ci si ricollega appena l\'adattatore è pronto.

La sfida viene dall\'hardware: la libreria conosce solo i codici di errore generici, quindi i codici specifici del gruppo Volkswagen compaiono senza descrizione. Cosa mi porto via: il piacere di programmare per un uso molto concreto, e un primo assaggio della comunicazione con l\'hardware.', 'pt' => 'Uma ferramenta nascida de uma necessidade real: saber o que se passa no meu carro, um Audi A3, sem software de oficina. Um adaptador ELM327 liga-se à tomada de diagnóstico OBD-II e ao PC por USB.

Uma aplicação Python (FastAPI) lê os dados com a biblioteca python-OBD e envia-os em tempo real para um painel web por WebSocket: rotação do motor, velocidade, temperatura do líquido de arrefecimento, tensão da bateria, nível de combustível e posição do acelerador, com um gráfico da rotação nas últimas leituras. Também é possível ler os códigos de avaria, com a sua descrição, e apagá-los.

Todo o histórico é guardado numa base SQLite e pode ser exportado em CSV. A aplicação funciona localmente, sem conta nem servidor remoto, e arranca mesmo quando o carro não está ligado: basta voltar a ligar quando o adaptador estiver pronto.

O desafio vem do hardware: a biblioteca só conhece os códigos de avaria genéricos, por isso os códigos próprios do grupo Volkswagen aparecem sem descrição. O que levo daqui: o prazer de programar para um uso muito concreto, e um primeiro contacto com a comunicação com hardware.']],
        // Gestionnaire de serveur
        ['projet', 'titre', '57b7db8595213a3839db52454ca22cc2', ['es' => 'Gestor de servidor', 'de' => 'Server-Manager', 'it' => 'Gestore di server', 'pt' => 'Gestor de servidor']],
        // Outils perso
        ['projet', 'categorie', 'bfee08a28682a395883122e6bd4cca33', ['es' => 'Herramientas propias', 'de' => 'Eigene Tools', 'it' => 'Strumenti personali', 'pt' => 'Ferramentas pessoais']],
        // Tableau de bord web en Go pour surveiller et piloter une …
        ['projet', 'description', 'a50792a2d8888f0e2877a5c6b5e48390', ['es' => 'Panel web en Go para supervisar y controlar una máquina en tiempo real: recursos, procesos, servicios, red, hardware y archivos.', 'de' => 'Web-Dashboard in Go, um einen Rechner in Echtzeit zu überwachen und zu steuern: Ressourcen, Prozesse, Dienste, Netzwerk, Hardware und Dateien.', 'it' => 'Dashboard web in Go per monitorare e gestire una macchina in tempo reale: risorse, processi, servizi, rete, hardware e file.', 'pt' => 'Painel web em Go para monitorizar e controlar uma máquina em tempo real: recursos, processos, serviços, rede, hardware e ficheiros.']],
        // Un outil de supervision que j’ai écrit en Go : un serveur…
        ['projet', 'details', '859d48588800856e38b13b5dcef8ea08', ['es' => 'Una herramienta de supervisión que escribí en Go: un servidor web que se ejecuta en la máquina que se quiere supervisar, en Linux o Windows, y muestra en el navegador, en tiempo real, todo lo que ocurre en ella.

El panel muestra la carga de cada núcleo del procesador, la memoria, los discos y la tarjeta gráfica, con un gráfico histórico y alertas cuando se supera un umbral. También se pueden listar y detener procesos, arrancar o parar servicios, seguir los registros en directo, explorar los discos (leer, descargar, eliminar) y ver el inventario de hardware: pantallas, teclado, ratón, tarjeta gráfica, cámara, impresora. Una pestaña captura el tráfico de red en directo, con un filtro por protocolo o dirección IP, el proceso que hay detrás de cada conexión y la resolución DNS inversa.

En lo técnico, las métricas se envían al navegador por WebSocket, con un hub y una goroutine por cliente, y el acceso está protegido por autenticación JWT. El proyecto se apoya en gopsutil para las métricas del sistema, gopacket para la captura de red y WMI en Windows para el hardware, y está contenedorizado con Docker.

El reto: una herramienta capaz de leer y borrar archivos tiene que ser segura. El explorador de archivos rechaza las rutas que se salen de las carpetas permitidas (path traversal), con pruebas unitarias específicas, y cada sistema operativo necesita su propio código para los servicios y el hardware. Lo que me llevo: mucho Go concurrente (goroutines, WebSocket) y una comprensión mucho mejor de lo que ocurre bajo el capó de una máquina.', 'de' => 'Ein Überwachungstool, das ich in Go geschrieben habe: ein Webserver, der auf dem zu überwachenden Rechner läuft, unter Linux oder Windows, und im Browser in Echtzeit zeigt, was darauf passiert.

Das Dashboard zeigt die Auslastung jedes Prozessorkerns, den Arbeitsspeicher, die Festplatten und die Grafikkarte, mit einem Verlaufsdiagramm und Warnungen, wenn ein Schwellenwert überschritten wird. Man kann auch Prozesse auflisten und beenden, Dienste starten oder stoppen, Logs live verfolgen, die Festplatten durchsuchen (lesen, herunterladen, löschen) und das Hardware-Inventar ansehen: Bildschirme, Tastatur, Maus, Grafikkarte, Kamera, Drucker. Ein Tab zeichnet den Netzwerkverkehr live auf, mit einem Filter nach Protokoll oder IP-Adresse, dem Prozess hinter jeder Verbindung und Reverse-DNS-Auflösung.

Technisch werden die Messwerte per WebSocket an den Browser gesendet, mit einem Hub und einer Goroutine pro Client, und der Zugang ist durch JWT-Authentifizierung geschützt. Das Projekt nutzt gopsutil für die Systemmetriken, gopacket für die Netzwerkaufzeichnung und WMI unter Windows für die Hardware, und es ist mit Docker containerisiert.

Die Herausforderung: Ein Tool, das Dateien lesen und löschen kann, muss sicher sein. Der Datei-Explorer weist Pfade ab, die aus den erlaubten Ordnern ausbrechen (Path Traversal), mit eigenen Unit-Tests, und jedes Betriebssystem braucht eigenen Code für Dienste und Hardware. Was ich mitnehme: viel nebenläufiges Go (Goroutinen, WebSocket) und ein viel besseres Verständnis dafür, was unter der Haube eines Rechners passiert.', 'it' => 'Uno strumento di monitoraggio che ho scritto in Go: un server web che gira sulla macchina da monitorare, Linux o Windows, e mostra nel browser, in tempo reale, tutto ciò che vi accade.

La dashboard mostra il carico di ogni core del processore, la memoria, i dischi e la scheda grafica, con un grafico storico e avvisi quando si supera una soglia. Si possono anche elencare e terminare processi, avviare o fermare servizi, seguire i log in tempo reale, esplorare i dischi (leggere, scaricare, eliminare) e vedere l\'inventario hardware: schermi, tastiera, mouse, scheda grafica, webcam, stampante. Una scheda cattura il traffico di rete in tempo reale, con un filtro per protocollo o indirizzo IP, il processo dietro ogni connessione e la risoluzione DNS inversa.

Sul piano tecnico, le metriche sono inviate al browser via WebSocket, con un hub e una goroutine per client, e l\'accesso è protetto da autenticazione JWT. Il progetto si appoggia a gopsutil per le metriche di sistema, gopacket per la cattura di rete e WMI su Windows per l\'hardware, ed è containerizzato con Docker.

La sfida: uno strumento in grado di leggere e cancellare file deve essere sicuro. L\'esploratore di file rifiuta i percorsi che escono dalle cartelle consentite (path traversal), con test unitari dedicati, e ogni sistema operativo richiede il proprio codice per servizi e hardware. Cosa mi porto via: molto Go concorrente (goroutine, WebSocket) e una comprensione molto migliore di ciò che accade sotto il cofano di una macchina.', 'pt' => 'Uma ferramenta de monitorização que escrevi em Go: um servidor web que corre na máquina a monitorizar, em Linux ou Windows, e mostra no navegador, em tempo real, tudo o que se passa nela.

O painel mostra a carga de cada núcleo do processador, a memória, os discos e a placa gráfica, com um gráfico de histórico e alertas quando um limite é ultrapassado. Também é possível listar e terminar processos, iniciar ou parar serviços, acompanhar os registos em direto, explorar os discos (ler, descarregar, eliminar) e ver o inventário de hardware: ecrãs, teclado, rato, placa gráfica, câmara, impressora. Um separador captura o tráfego de rede em direto, com um filtro por protocolo ou endereço IP, o processo por trás de cada ligação e a resolução DNS inversa.

Do lado técnico, as métricas são enviadas para o navegador por WebSocket, com um hub e uma goroutine por cliente, e o acesso está protegido por autenticação JWT. O projeto apoia-se no gopsutil para as métricas do sistema, no gopacket para a captura de rede e no WMI em Windows para o hardware, e está em contentores Docker.

O desafio: uma ferramenta capaz de ler e apagar ficheiros tem de ser segura. O explorador de ficheiros rejeita os caminhos que saem das pastas permitidas (path traversal), com testes unitários dedicados, e cada sistema operativo precisa do seu próprio código para os serviços e o hardware. O que levo daqui: muito Go concorrente (goroutines, WebSocket) e uma compreensão muito melhor do que acontece por dentro de uma máquina.']],
    ];

    public function getDescription(): string
    {
        return 'Traductions espagnoles, allemandes, italiennes et portugaises du contenu';
    }

    public function up(Schema $schema): void
    {
        foreach (self::TRADUCTIONS as [$table, $champ, $empreinte, $traductions]) {
            // traductions.<langue>.<champ> = texte, sans toucher aux autres champs ni aux autres langues
            $ajouts = implode(', ', array_map(
                static fn (string $langue) => "'$langue', COALESCE(traductions::jsonb -> '$langue', '{}'::jsonb) || jsonb_build_object('$champ', CAST(:$langue AS TEXT))",
                self::LANGUES,
            ));
            $this->addSql(
                "UPDATE $table SET traductions = (traductions::jsonb || jsonb_build_object($ajouts))::json WHERE md5(replace($champ, chr(13) || chr(10), chr(10))) = :empreinte",
                ['empreinte' => $empreinte] + $traductions,
            );
        }
    }

    public function down(Schema $schema): void
    {
        foreach (['categorie_competence', 'centre_interet', 'competence', 'cv_competence', 'cv_profil', 'etape_parcours', 'experience', 'langue', 'passion', 'projet'] as $table) {
            $this->addSql("UPDATE $table SET traductions = (traductions::jsonb - 'es' - 'de' - 'it' - 'pt')::json");
        }
    }
}
