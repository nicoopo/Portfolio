/*
 * Palette de commandes : Ctrl+K (⌘K sur Mac), « / » ou le bouton loupe de la barre de navigation.
 * Cherche dans les pages, compétences, projets et articles (/recherche.json, chargé à la première ouverture)
 * et lance quelques commandes. Liste au clavier : modèle combobox de l'ARIA (aria-activedescendant).
 */
import { decouvrir } from './decouvertes.js';

const dialog = document.getElementById('palette');
const champ = dialog.querySelector('input');
const liste = dialog.querySelector('[role="listbox"]');
const message = dialog.querySelector('.palette-message');
const textes = JSON.parse(dialog.dataset.textes);
const MAX = 12;

const COMMANDES = [
    { type: 'commande', titre: textes.meteores, action: () => document.dispatchEvent(new CustomEvent('meteores')) },
];

let entrees = null; // null tant que /recherche.json n'a pas répondu : seules les commandes sont cherchées
let resultats = [];
let actif = 0;

const normaliser = (texte) => texte.normalize('NFD').replace(/\p{Diacritic}/gu, '').toLowerCase();

async function ouvrir() {
    if (dialog.open) return;
    champ.value = '';
    dialog.showModal();
    filtrer();
    entrees ??= await fetch(dialog.dataset.url)
        .then((reponse) => (reponse.ok ? reponse.json() : Promise.reject()))
        .then((donnees) => COMMANDES.concat(donnees), () => null);
    filtrer();
}

function filtrer() {
    const q = normaliser(champ.value.trim());
    const tous = entrees ?? COMMANDES;
    if (q === 'sudo') decouvrir('terminal');
    resultats = q === 'sudo' ? [] : (q
        ? tous.filter((e) => normaliser(`${e.titre} ${e.detail ?? ''}`).includes(q))
            .sort((a, b) => normaliser(b.titre).startsWith(q) - normaliser(a.titre).startsWith(q)) // le titre qui commence par la recherche d'abord
        : tous.filter((e) => e.type === 'page' || e.type === 'commande')
    ).slice(0, MAX);

    liste.replaceChildren(...resultats.map((entree, i) => {
        const option = Object.assign(document.createElement('li'), { id: `palette-${i}`, className: 'palette-option' });
        option.setAttribute('role', 'option');
        option.append(
            Object.assign(document.createElement('span'), { className: 'palette-type', textContent: textes[entree.type] }),
            Object.assign(document.createElement('span'), { className: 'palette-titre', textContent: entree.titre }),
        );
        if (entree.detail) option.append(Object.assign(document.createElement('span'), { className: 'palette-detail', textContent: entree.detail }));
        option.addEventListener('click', () => choisir(entree));
        option.addEventListener('pointermove', () => activer(i));
        return option;
    }));
    message.textContent = q === 'sudo' ? textes.sudo : textes.vide;
    message.hidden = resultats.length > 0 || (q !== 'sudo' && entrees === null);
    activer(0);
}

function activer(i) {
    liste.querySelector('[aria-selected="true"]')?.setAttribute('aria-selected', 'false');
    actif = i;
    const option = document.getElementById(`palette-${i}`);
    if (!option) return champ.removeAttribute('aria-activedescendant');
    option.setAttribute('aria-selected', 'true');
    champ.setAttribute('aria-activedescendant', option.id);
    option.scrollIntoView({ block: 'nearest' });
}

function choisir(entree) {
    dialog.close();
    if (entree.action) return entree.action();
    // Vrai clic sur un lien : la transition trou noir (page_transition.js) s'applique comme partout
    const lien = Object.assign(document.createElement('a'), { href: entree.url, hidden: true });
    document.body.append(lien);
    lien.click();
    lien.remove();
    // Même page, autre ancre (neurone du cerveau) : le navigateur ne recharge pas, la page ne relit pas l'ancre
    if (new URL(entree.url, location.href).pathname === location.pathname) location.reload();
}

champ.addEventListener('input', filtrer);
champ.addEventListener('keydown', (e) => {
    const n = resultats.length;
    if (e.key === 'ArrowDown' && n) { e.preventDefault(); activer((actif + 1) % n); }
    if (e.key === 'ArrowUp' && n) { e.preventDefault(); activer((actif - 1 + n) % n); }
    if (e.key === 'Enter' && resultats[actif]) { e.preventDefault(); choisir(resultats[actif]); }
});
dialog.addEventListener('click', (e) => { if (e.target === dialog) dialog.close(); }); // clic sur le fond
document.getElementById('paletteOuvrir').addEventListener('click', ouvrir);
document.addEventListener('keydown', (e) => {
    const raccourci = (e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k';
    const slash = e.key === '/' && !e.target.closest?.('input, textarea, select, [contenteditable]');
    if (!raccourci && !slash) return;
    e.preventDefault();
    ouvrir();
});
