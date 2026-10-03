// Les CSS (Bootstrap puis styles/app.css) sont chargés par des <link> dans base.html.twig, pas importés
// ici : AssetMapper remplacerait chaque import CSS par un module « data: », que la CSP refuse.

// 1. Scripts globaux (présents sur toutes les pages)
import './scripts/stars.js';
import './scripts/navbar.js';
import './scripts/page_transition.js';

// 2. Stimulus : comportements attachés à un élément via data-controller (assets/controllers/)
import './bootstrap.js';
