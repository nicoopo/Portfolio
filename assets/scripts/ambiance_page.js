/*
 * Ambiance sonore des pages (réglage « Son », coupé par défaut) : une couleur par page (PROFILS de
 * assets/cerveau/ambiance.js), en fondu à l'arrivée. Le cerveau garde la sienne (brain_controller.js).
 * Les navigateurs n'autorisent le son qu'après un geste : on attend le premier clic ou la première touche de la page.
 */
import { Ambiance, PROFILS } from '../cerveau/ambiance.js';
import { preferences } from './preferences.js';

const PAGES = {
    app_home: 'accueil',
    app_projects: 'projets', app_project: 'projets', app_projects_frise: 'projets',
    app_univers: 'univers',
    app_competences: 'competences', app_comparateur: 'competences',
};

let ambiance = null;

function jouer(on) {
    if (!window.AudioContext || document.querySelector('[data-controller~="brain"]')) return;
    if (on === !!ambiance?.on) return;
    ambiance ??= new Ambiance(PROFILS[PAGES[document.body.dataset.page]] ?? PROFILS.calme);
    ambiance.toggle();
}

const premierGeste = () => { if (preferences.son) jouer(true); };
['pointerdown', 'keydown'].forEach((type) => document.addEventListener(type, premierGeste, { once: true, capture: true }));
// Interrupteur des Réglages (ou bouton du cerveau) : c'est déjà un geste, le son peut partir tout de suite
document.addEventListener('preference', (e) => { if (e.detail.name === 'son') jouer(e.detail.value); });
document.addEventListener('visibilitychange', () => ambiance?.setVisible(!document.hidden));
