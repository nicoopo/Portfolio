/*
 * Réglages du visiteur, gardés dans son navigateur (menu de la navbar) :
 * - transitions : trou noir entre les pages
 * - qualite : 'haute' ou 'basse' (moins de particules, pas de lueur, pour les machines modestes)
 * - curseur : 'comete', 'orbite', 'trou-noir' ou 'systeme' (curseur.js)
 */
const KEY = 'preferences';
const DEFAULTS = { transitions: true, qualite: 'haute', curseur: 'comete' };

const read = () => { try { return { ...DEFAULTS, ...JSON.parse(localStorage.getItem(KEY)) }; } catch { return { ...DEFAULTS }; } };

export const preferences = read();

export function setPreference(name, value) {
    preferences[name] = value;
    try { localStorage.setItem(KEY, JSON.stringify(preferences)); } catch { /* navigation privée : réglage pour cette page seulement */ }
}

export const basseQualite = () => preferences.qualite === 'basse';
