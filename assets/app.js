// 1. Bootstrap en premier (CSS seulement : aucun composant JS de Bootstrap n'est utilisé)
import 'bootstrap/dist/css/bootstrap.min.css';

// 2. Nos styles après : ils surchargent Bootstrap
import './styles/app.css';

// 3. Scripts globaux (présents sur toutes les pages)
import './scripts/stars.js';
import './scripts/navbar.js';
import './scripts/page_transition.js';
import './scripts/curseur.js';
import './scripts/konami.js';
import './scripts/constellation.js';
import './scripts/qualite_auto.js';
import './scripts/sons.js';
import './scripts/ambiance_page.js';
import './scripts/palette.js';
import './scripts/visite.js';

// Application installable : page hors ligne quand le réseau manque (public/sw.js)
if ('serviceWorker' in navigator) {
    navigator.serviceWorker.register('/sw.js').catch(() => {}); // sans service worker, le site marche pareil
}

// 4. Stimulus : comportements attachés à un élément via data-controller (assets/controllers/)
import './stimulus_bootstrap.js';
