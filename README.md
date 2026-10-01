# Mon Portfolio Symfony

🌐 **Lien du site en ligne** : [Mon Portfolio](https://nicolascataluna.fr/)

Ce projet est un portfolio personnel développé avec Symfony 7.4, mettant en avant mes compétences, projets et CV en ligne.

## 📁 Structure du Projet

```
Portfolio/
├── assets/              # Fichiers statiques (JS, CSS, images)
│   ├── controllers/     # Contrôleurs Stimulus (comportement lié à un élément)
│   ├── scripts/         # Scripts globaux chargés sur toutes les pages
│   ├── styles/          # CSS découpé (voir « Organisation des assets »)
│   └── vendor/          # Bibliothèques tierces (importmap, non versionné)
├── config/              # Configuration Symfony
├── src/                 # Code source
│   ├── Controller/      # Contrôleurs Symfony
│   ├── Entity/          # Entités Doctrine
│   ├── Form/            # Formulaires Symfony
│   └── Repository/      # Repositories Doctrine
├── templates/           # Vues Twig
│   ├── competences/     # Page des compétences
│   ├── contact/         # Page de contact
│   ├── cv/              # CV en ligne et PDF
│   ├── home/            # Page d'accueil
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
- `assets/cerveau/` : modules Three.js du cerveau 3D (forme, neurones, synapses, nébuleuses), sans logique
  d'interface ; importés uniquement par `controllers/brain_controller.js`.
- Pas de `<script>` ni de `<style>` dans les templates Twig (exceptions : le template PDF, car Dompdf exige le CSS inline, et la ligne qui applique le thème du CV avant l'affichage).

## ✨ Fonctionnalités

- **Compétences** : Présentation de mes compétences techniques et professionnelles.
- **Portfolio** : Galerie de projets avec descriptions et captures d'écran.
- **CV en ligne** : CV interactif avec téléchargement en PDF.
- **Contact** : Formulaire pour me contacter.
- **Responsive** : Optimisé pour tous les appareils.

## 🛠 Technologies

- Symfony 7.4
- PHP
- Node.js
- Doctrine
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

3. Configurer l'environnement :
   - Copier `.env` en `.env.local`.
   - Configurer la base de données dans `.env.local`.

4. Créer la base de données :
   ```bash
   php bin/console doctrine:database:create
   php bin/console doctrine:migrations:migrate
   ```

5. Lancer le serveur :
   ```bash
   symfony serve
   ```

6. Accéder au site :
   Ouvrir [http://localhost:8000](http://localhost:8000) dans un navigateur.

## 📝 Commandes Utiles

- Lancer les tests :
  ```bash
  php bin/phpunit
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

## 👤 Auteur

[Nicolas](https://github.com/nicoopo/Portfolio)
