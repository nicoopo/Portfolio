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

4. Accéder au site : [http://localhost:8081](http://localhost:8081)

**Base de dev** (client SQL, PhpStorm…) : `localhost:5433`, base `app`, utilisateur `app`, sans mot de passe
(port ouvert sur 127.0.0.1 uniquement). En ligne de commande : `docker compose exec database psql -U app app`.

**Administration** : [http://localhost:8081/admin](http://localhost:8081/admin) pour modifier compétences, projets,
passions et parcours. Premier compte : `make admin-create` (identifiant et mot de passe demandés) ; ensuite, les comptes
et les mots de passe se gèrent dans l'administration (menu « Comptes »).

**Preprod** : définir dans `.env.local` sur le serveur `POSTGRES_PASSWORD=...` et `MAILER_DSN=...` (SMTP qui envoie le
formulaire de contact ; en dev les e-mails ne partent pas, ils sont visibles dans la barre de debug), puis
`make preprod-deploy` (build, redémarrage, migrations), et une seule fois `make preprod-admin-create`.
La preprod a sa propre base (volume `db_data_preprod`).

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
