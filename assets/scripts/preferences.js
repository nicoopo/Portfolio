/*
 * Réglages du visiteur, gardés dans son navigateur (menu de la navbar) :
 * - transitions : effets entre les pages (et easter eggs)
 * - effet : 'hasard', 'trou-noir', 'teleportation', 'distorsion' ou 'lumiere' (page_transition.js)
 * - qualite : 'haute' ou 'basse' (moins de particules, pas de lueur, pour les machines modestes) ;
 *   sans choix du visiteur, basse d'office si le navigateur n'a pas d'accélération graphique
 * - curseur : 'comete', 'orbite', 'trou-noir' ou 'systeme' (curseur.js)
 * - son : ambiance sonore du cerveau (brain_controller.js) et petits sons du site (sons.js)
 * - decouvertes : easter eggs déjà trouvés (decouvertes.js)
 * - qualiteAuto : proposer la qualité basse si le site rame (qualite_auto.js), jusqu'à ce que le visiteur choisisse
 */
const KEY = 'preferences';
const DEFAULTS = { transitions: true, effet: 'hasard', qualite: 'haute', curseur: 'comete', son: false, qualiteAuto: true, decouvertes: [] };

const read = () => { try { return { ...DEFAULTS, ...JSON.parse(localStorage.getItem(KEY)) }; } catch { return { ...DEFAULTS }; } };

export const preferences = read();

export function setPreference(name, value) {
    preferences[name] = value;
    try { localStorage.setItem(KEY, JSON.stringify(preferences)); } catch { /* navigation privée : réglage pour cette page seulement */ }
    // Panneau ⚙ et page cerveau restent synchronisés
    document.dispatchEvent(new CustomEvent('preference', { detail: { name, value } }));
}

let logiciel;
/** Pas d'accélération graphique : le navigateur dessine tout au processeur (WebGL refuse alors ce contexte) */
function renduLogiciel() {
    if (logiciel === undefined) {
        const gl = document.createElement('canvas').getContext('webgl', { failIfMajorPerformanceCaveat: true });
        logiciel = !gl;
        gl?.getExtension('WEBGL_lose_context')?.loseContext(); // libère le contexte de test
    }
    return logiciel;
}

export const basseQualite = () => preferences.qualite === 'basse' || (preferences.qualiteAuto && renduLogiciel());
