/*
 * Qualité automatique : quelques secondes après le chargement, on compte les images affichées.
 * Si le site rame, un message propose de passer en qualité basse (jamais de bascule forcée).
 * Plus de mesure une fois que le visiteur a répondu ou choisi lui-même sa qualité (navbar.js).
 */
import { basseQualite, preferences, setPreference } from './preferences.js';

const ATTENTE_MS = 3000; // laisse passer le chargement (Three.js, construction du cerveau…)
const MESURE_MS = 4000;
const SEUIL_FPS = 30;

if (preferences.qualiteAuto && !basseQualite()) {
    window.addEventListener('load', () => setTimeout(mesurer, ATTENTE_MS));
}

function mesurer() {
    // Onglet caché pendant la mesure : les images sont suspendues, la mesure ne veut plus rien dire
    let interrompu = document.hidden;
    document.addEventListener('visibilitychange', () => { interrompu = true; }, { once: true });

    let debut = null, images = 0;
    const image = (t) => {
        if (interrompu) return;
        debut ??= t;
        images++;
        if (t - debut < MESURE_MS) {
            requestAnimationFrame(image);
            return;
        }
        if ((images - 1) * 1000 / (t - debut) < SEUIL_FPS) proposer();
    };
    requestAnimationFrame(image);
}

function proposer() {
    const message = document.getElementById('qualiteAuto');
    if (!message) return;
    message.hidden = false;
    const repondre = (basse) => {
        setPreference('qualiteAuto', false);
        message.hidden = true;
        if (basse) {
            setPreference('qualite', 'basse');
            location.reload(); // cerveau et trous noirs sont construits au chargement
        }
    };
    message.querySelector('[data-qualite="basse"]').addEventListener('click', () => repondre(true), { once: true });
    message.querySelector('[data-qualite="garder"]').addEventListener('click', () => repondre(false), { once: true });
}
