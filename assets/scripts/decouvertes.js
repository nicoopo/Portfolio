/*
 * Carnet de découvertes : chaque easter egg trouvé est retenu (preferences.decouvertes) et coché
 * dans le panneau ⚙ (base.html.twig), où les autres n'affichent qu'un indice.
 * Nouvelle découverte : notification ; toutes trouvées : pluie de météores (konami.js) en récompense.
 */
import { preferences, setPreference } from './preferences.js';

const AFFICHAGE_MS = 5000;

/** Appelé par chaque easter egg : 'trou-noir', 'konami', 'constellation-n', 'constellation-lion' */
export function decouvrir(id) {
    if (preferences.decouvertes.includes(id)) return;
    setPreference('decouvertes', [...preferences.decouvertes, id]);
    afficherCarnet();

    const toast = document.getElementById('decouverte');
    const ligne = document.querySelector(`[data-decouverte="${id}"]`);
    const { trouvees, total } = compte();
    if (toast && ligne) {
        toast.querySelector('[data-nom]').textContent = ligne.querySelector('.decouverte-nom').textContent;
        toast.querySelector('[data-compte]').textContent = `${trouvees}/${total}`;
        toast.querySelector('[data-bravo]').hidden = trouvees < total;
        toast.hidden = false;
        clearTimeout(toast.timer);
        toast.timer = setTimeout(() => { toast.hidden = true; }, AFFICHAGE_MS);
    }
    if (trouvees === total) document.dispatchEvent(new CustomEvent('decouvertes:toutes'));
}

const lignes = () => document.querySelectorAll('[data-decouverte]');
const compte = () => {
    const toutes = [...lignes()].map((ligne) => ligne.dataset.decouverte);
    return { trouvees: toutes.filter((id) => preferences.decouvertes.includes(id)).length, total: toutes.length };
};

/** Coche les découvertes dans le panneau ⚙ */
function afficherCarnet() {
    lignes().forEach((ligne) => ligne.classList.toggle('trouvee', preferences.decouvertes.includes(ligne.dataset.decouverte)));
    const { trouvees, total } = compte();
    const compteur = document.getElementById('decouvertesCompte');
    if (compteur) compteur.textContent = `${trouvees}/${total}`;
    const badge = document.getElementById('decouvertesBravo');
    if (badge) badge.hidden = trouvees < total;
}

document.addEventListener('DOMContentLoaded', afficherCarnet);
