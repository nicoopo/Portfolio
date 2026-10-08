/*
 * Petits sons du site, synthétisés (Web Audio, aucun fichier), seulement si le réglage « Son » est actif.
 * Un seul AudioContext, créé au premier son, donc toujours juste après un geste (règle des navigateurs).
 * L'ambiance du cerveau garde le sien (assets/cerveau/ambiance.js).
 */
import { preferences } from './preferences.js';

const VOLUME = 0.5; // volume général : chaque son reste sous 0,1, rien de strident
// Do majeur pentatonique, comme les « étoiles » de l'ambiance : jamais de fausse note
const NOTES = [523.25, 587.33, 659.25, 783.99, 880, 1046.5, 1174.66, 1318.51, 1567.98, 1760];

let context, master, souffle;

function audio() {
    if (!preferences.son) return null;
    if (!context) {
        context = new AudioContext();
        master = context.createGain();
        master.gain.value = VOLUME;
        master.connect(context.destination);
        // Bruit blanc d'une demi-seconde, réutilisé par chaque impact
        souffle = context.createBuffer(1, context.sampleRate / 2, context.sampleRate);
        souffle.getChannelData(0).forEach((_, i, data) => { data[i] = Math.random() * 2 - 1; });
    }
    context.resume();
    return context;
}

/** Note qui glisse de debut à fin Hz : attaque rapide puis extinction */
function ton(debut, fin, duree, volume, delai = 0) {
    const ctx = audio();
    if (!ctx) return;
    const t = ctx.currentTime + delai;
    const oscillateur = ctx.createOscillator();
    oscillateur.frequency.setValueAtTime(debut, t);
    oscillateur.frequency.exponentialRampToValueAtTime(fin, t + duree);
    const gain = ctx.createGain();
    gain.gain.setValueAtTime(0, t);
    gain.gain.linearRampToValueAtTime(volume, t + 0.01);
    gain.gain.exponentialRampToValueAtTime(0.0001, t + duree);
    oscillateur.connect(gain).connect(master);
    oscillateur.start(t);
    oscillateur.stop(t + duree);
}

/** Souffle sourd : bruit blanc filtré dans les graves */
function bruit(duree, volume, coupure) {
    const ctx = audio();
    if (!ctx) return;
    const t = ctx.currentTime;
    const source = ctx.createBufferSource();
    source.buffer = souffle;
    const filtre = ctx.createBiquadFilter();
    filtre.type = 'lowpass';
    filtre.frequency.value = coupure;
    const gain = ctx.createGain();
    gain.gain.setValueAtTime(volume, t);
    gain.gain.exponentialRampToValueAtTime(0.0001, t + duree);
    source.connect(filtre).connect(gain).connect(master);
    source.start(t, 0, duree);
}

export const sons = {
    /** Menu ou réglages : petit glissando montant à l'ouverture, descendant à la fermeture */
    menu: (ouvert) => ton(ouvert ? 440 : 660, ouvert ? 660 : 440, 0.15, 0.04),
    /** Trou noir qui s'ouvre : grondement qui plonge */
    trouNoir: () => ton(160, 35, 1.4, 0.15),
    /** Météore qui s'écrase : choc grave et souffle */
    impact: () => { ton(110, 35, 0.4, 0.12); bruit(0.35, 0.1, 700); },
    /** Étoile reliée : une note de plus en plus aiguë à chaque étape */
    etoile: (etape) => ton(NOTES[etape % NOTES.length], NOTES[etape % NOTES.length], 0.8, 0.05),
    /** Constellation complète : arpège */
    constellation: () => [0, 2, 4, 5, 7].forEach((n, i) => ton(NOTES[n], NOTES[n], 1.5, 0.05, i * 0.1)),
};

// Menu, réglages et langues sont des popovers natifs ; « toggle » ne remonte pas, on l'écoute à la capture
document.addEventListener('toggle', (e) => { if (e.target.matches?.('#menu, #reglages, #langues')) sons.menu(e.newState === 'open'); }, true);
