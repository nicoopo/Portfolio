# Mon Portfolio Symfony

🌐 **Lien du site en ligne** : [Mon Portfolio](https://nicolascataluna.fr/)

Ce projet est un portfolio personnel développé avec Symfony 7.4, mettant en avant mes compétences, projets et CV en ligne.

## 📁 Structure du Projet

```
Portfolio/
├── assets/              # Fichiers statiques (JS, CSS, images)
│   ├── cerveau/         # Modules Three.js du cerveau 3D
│   ├── controllers/     # Contrôleurs Stimulus (comportement lié à un élément)
│   ├── scripts/         # Scripts globaux chargés sur toutes les pages
│   ├── styles/          # CSS découpé (voir « Organisation des assets »)
│   └── vendor/          # Bibliothèques tierces (importmap, non versionné)
├── config/              # Configuration Symfony
├── src/                 # Code source
│   ├── Controller/      # Contrôleurs Symfony
│   ├── Entity/          # Entités Doctrine (compétences, projets, passions, parcours)
│   └── Repository/      # Repositories Doctrine
├── migrations/          # Schéma PostgreSQL + contenu du portfolio
├── templates/           # Vues Twig
│   ├── competences/     # Page des compétences
│   ├── contact/         # Page de contact
│   ├── cv/              # CV en ligne et PDF
│   ├── home/            # Page d'accueil et cerveau 3D
│   ├── portfolio/       # Page des projets
│   └── univers/         # Page des centres d'intérêt
├── public/              # Point d'entrée public
└── var/                 # Cache et logs
```

## 🗂 Organisation des assets

**CSS** — `assets/styles/app.css` ne contient que des `@import`, dans l'ordre de la cascade :

| Dossier | Contenu | Exemple |
|---|---|---|
| `base/` | Variables, styles globaux, keyframes partagées | `_variables.css` |
| `layout/` | Structure commune à toutes les pages | `_navbar.css` |
| `components/` | Blocs réutilisables sur plusieurs pages | `_buttons.css` |
| `pages/` | Un fichier par page | `_contact.css` |

- Les fichiers importés par `app.css` commencent par `_`.
- Un CSS qui ne doit pas s'appliquer partout (ex. `pages/cv.css`, qui redéfinit `body`) n'a pas de `_`
  et est chargé seulement par sa page via `{% block stylesheets %}`.
- Les media queries sont dans le fichier du composant qu'elles modifient, pas dans un fichier « responsive ».

**JS**
- `assets/controllers/xxx_controller.js` : contrôleur Stimulus, branché sur un élément avec `data-controller="xxx"`.
  C'est le choix par défaut pour tout comportement de page (ex. `typing`, `cv`, `brain`).
- `assets/scripts/` : scripts globaux importés par `app.js` (fond étoilé, menu, transition de page).
- `assets/cerveau/` : modules du cerveau 3D : Three.js (forme, neurones, synapses, nébuleuses, souvenirs) et ambiance sonore (Web Audio), sans logique
  d'interface ; importés uniquement par `controllers/brain_controller.js`.
- Pas de `<script>` ni de `<style>` dans les templates Twig (exceptions : le template PDF, car Dompdf exige le CSS inline, et la ligne qui applique le thème du CV avant l'affichage).

## ✨ Fonctionnalités

- **Mon Cerveau** : Cerveau holographique 3D (Three.js) : chaque neurone est une compétence, cliquable,
  relié par des synapses animées aux compétences utilisées dans les mêmes projets ; des nébuleuses en orbite
  représentent mes passions ; un fil de souvenirs retrace mon parcours.
- **Compétences** : Présentation de mes compétences techniques et professionnelles.
- **Portfolio** : Galerie de projets avec descriptions et captures d'écran.
- **CV en ligne** : page et PDF (thème clair ou sombre) générés depuis la base, en français et en anglais ; modifiables dans l’administration (section « CV »).
- **Contact** : formulaire (anti-spam par champ piège), e-mail, GitHub, LinkedIn et CV numérique.
- **Responsive** : Optimisé pour tous les appareils.
- **Français / anglais** : version anglaise sous `/en` (bouton FR/EN dans la navigation). Textes des pages :
  `translations/messages.en.yaml` (la clé est le texte français) ; contenu de la base : champs « … (anglais) »
  de l'administration (vide = le français s'affiche). Seule la lettre de motivation (PDF) reste en français.

## 🛠 Technologies

- Symfony 7.4
- Three.js
- PHP
- Node.js
- Doctrine / PostgreSQL
- Bootstrap
- JavaScript
- CSS
- HTML
- Twig
- Composer

## 🚀 Installation

1. Cloner le dépôt :
   ```bash
   git clone https://github.com/nicoopo/Portfolio.git
   cd mon-projet-symfony
   ```

2. Installer les dépendances :
   ```bash
   composer install
   ```

3. Lancer les conteneurs (PHP/Apache + PostgreSQL 16), installer les dépendances et créer la base :
   ```bash
   make up
   ```
   Le contenu du portfolio (compétences, projets, passions, parcours) est inséré par les migrations.

4. Accéder au site : [http://localhost:8082](http://localhost:8082)

**Base de dev** (client SQL, PhpStorm…) : `localhost:5434`, base `app`, utilisateur `app`, sans mot de passe
(port ouvert sur 127.0.0.1 uniquement). En ligne de commande : `docker compose exec database psql -U app app`.

**Administration** : [http://localhost:8082/admin](http://localhost:8082/admin) pour modifier compétences, projets,
passions et parcours. Premier compte : `make admin-create` (identifiant et mot de passe demandés) ; ensuite, les comptes
et les mots de passe se gèrent dans l'administration (menu « Comptes »).

## 🔒 Mise en production — https://nicolascataluna.fr

```
Internet ──IPv6:443──▶ Caddy (FrankenPHP, sur la machine) ──▶ 127.0.0.1:8081 ── container Apache/PHP ── PostgreSQL
         (certificat Let's Encrypt automatique, HTTP → HTTPS, www → domaine nu, en-têtes de sécurité)
```

Un push sur `master` déploie (`.github/workflows/deploy-prod.yml` → `scripts/deploy-prod.sh` : build, redémarrage,
migrations). La prod a son propre projet Docker (`portfolio_prod`) et sa propre base (volume `db_data_prod`).
Le code de prod est déployé dans un git worktree séparé (`~/deploy/portfolio`, HEAD détachée sur `origin/master`) :
le dossier de travail et sa branche ne sont jamais touchés par un déploiement. Son `.env.local` est un lien vers celui du dépôt.

Une seule fois, sur le serveur :

1. `.env.local` : `APP_SECRET=...`, `POSTGRES_PASSWORD=...`, `MAILER_DSN=...` (SMTP du formulaire de contact ;
   en dev les e-mails ne partent pas, ils sont visibles dans la barre de debug).
2. DNS (OVH) : `AAAA` de `nicolascataluna.fr` et `www.nicolascataluna.fr` vers l'IPv6 fixe de la machine.
3. Box : pare-feu IPv6, n'ouvrir que les ports **80 et 443** vers la machine (80 sert au certificat et à la
   redirection vers HTTPS).
4. Caddy : `sudo install -m 644 docker/caddy/Caddyfile /etc/frankenphp/Caddyfile && sudo systemctl reload frankenphp`
   (à refaire après chaque modification de `docker/caddy/Caddyfile`).
5. Après le premier déploiement : `make prod-admin-create`.

### Sauvegardes de la base

`scripts/sauvegarde-base.sh`, chaque nuit à 3 h 15 (crontab de `nicolas`, log dans `~/cron-logs/portfolio-sauvegarde.log`) :
`pg_dump` compressé, vérifié par `pg_restore --list`, et archive des images envoyées depuis l'admin
(`portfolio-images-*.tar.gz`, volume `uploads_prod`), gardés 14 jours dans `~/sauvegardes/portfolio`, puis copiés
sur le second disque (`/mnt/sauvegardes`, s'il est monté) et sur Proton Drive (remote rclone `proton:`, s'il est configuré).

Restaurer une sauvegarde (**remplace** le contenu de la base de prod) :

```bash
docker compose -f compose.yaml -f compose.prod.yaml exec -T database \
    pg_restore -U app -d app --clean --if-exists --no-owner < ~/sauvegardes/portfolio/portfolio-AAAA-MM-JJ_HHMM.dump
```

Restaurer les images envoyées depuis l'admin :

```bash
docker compose -f compose.yaml -f compose.prod.yaml exec -T php \
    tar -xzf - -C public < ~/sauvegardes/portfolio/portfolio-images-AAAA-MM-JJ_HHMM.tar.gz
```

### Images des projets

Envoyées depuis l'admin (`/admin`, Projets → Image) : JPG, PNG ou WebP, 3 Mo maximum, rangées dans
`public/uploads/projets/` sous un nom tiré de leur contenu. En prod, ce dossier est le volume Docker `uploads_prod` :
les images survivent aux déploiements et sont sauvegardées chaque nuit (ci-dessus). Apache n'y exécute aucun script.
Les images déjà présentes dans le dépôt (`assets/images/projets/`, champ « Image du dépôt ») restent utilisées tant
qu'aucune image n'est envoyée.

### Purge des données personnelles

`make prod-purge`, chaque nuit à 3 h 30 après la sauvegarde (crontab de `nicolas`, depuis `~/deploy/portfolio`, log dans
`~/cron-logs/portfolio-purge.log`) : supprime le journal (`app:journal:purge`) et les demandes de contact
(`app:contact:purge`) de plus de 12 mois, la durée annoncée dans la politique de confidentialité (`/confidentialite`).

## 📝 Commandes Utiles

- Lancer les tests (crée et migre la base `app_test` au besoin) :
  ```bash
  make test
  ```

- Appliquer de nouvelles migrations :
  ```bash
  make migrate
  ```

- Générer les assets :
  ```bash
  php bin/console asset-map:compile
  ```

- Vider le cache :
  ```bash
  php bin/console cache:clear
  ```

## 🤝 Contribution

1. Forker le projet.
2. Créer une branche : `git checkout -b feature/ma-fonctionnalite`.
3. Commiter : `git commit -m 'Ajout de ma fonctionnalité'`.
4. Pousser : `git push origin feature/ma-fonctionnalite`.
5. Ouvrir une Pull Request.

## 📄 Licence

MIT - Voir [LICENSE](LICENSE).

### Crédits des icônes

Icônes stockées dans `assets/icons/` (`php bin/console ux:icons:lock` pour en ajouter), via [Iconify](https://iconify.design) :
Bootstrap Icons, Fluent, Iconoir, Pepicons, Tabler (MIT) ; **Streamline Pixel** par Streamline
([CC BY 4.0](https://creativecommons.org/licenses/by/4.0/)) : logo LinkedIn et icône de contact.

## 👤 Auteur

[Nicolas](https://github.com/nicoopo/Portfolio)
