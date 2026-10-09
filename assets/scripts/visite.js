/*
 * Visite guidée en une minute : une étape par page (base.html.twig, #visite), avancée automatique
 * au bout de la barre de progression (animation CSS), pause, étape suivante, quitter.
 * L'étape en cours est retenue pour l'onglet (sessionStorage) ; arrivé sur une autre page que celle prévue, la visite s'arrête.
 * Lancée par [data-visite-demarrer] (accueil) ou la commande de la palette.
 */
import { naviguer } from './naviguer.js';

const barre = document.getElementById('visite');
const etapes = JSON.parse(barre.dataset.etapes);
const textes = JSON.parse(barre.dataset.textes);
const texte = barre.querySelector('[data-texte]');
const numero = barre.querySelector('[data-numero]');
const pause = barre.querySelector('[data-pause]');
const suivant = barre.querySelector('[data-suivant]');
const progression = barre.querySelector('.visite-progression');
const CLE = 'visite';

const lire = () => { try { return sessionStorage.getItem(CLE); } catch { return null; } };
const ecrire = (i) => { try { i === null ? sessionStorage.removeItem(CLE) : sessionStorage.setItem(CLE, i); } catch { /* visite sur une seule page */ } };
const ici = (i) => new URL(etapes[i].url, location.href).pathname === location.pathname;

let actuelle = null;

function aller(i) {
    if (i >= etapes.length) return quitter();
    ecrire(i);
    if (ici(i)) afficher(i); else naviguer(etapes[i].url);
}

function afficher(i) {
    actuelle = i;
    const derniere = i === etapes.length - 1;
    numero.textContent = `${i + 1}/${etapes.length}`;
    texte.textContent = etapes[i].texte;
    suivant.textContent = derniere ? textes.terminer : textes.suivant;
    pause.hidden = derniere; // dernière étape : pas d'avancée automatique
    pause.textContent = textes.pause;
    barre.classList.remove('en-pause');
    barre.classList.toggle('derniere', derniere);
    // Relance la barre de progression
    progression.style.animation = 'none';
    progression.getBoundingClientRect();
    progression.style.animation = '';
    barre.hidden = false;
}

function quitter() {
    ecrire(null);
    actuelle = null;
    barre.hidden = true;
}

progression.addEventListener('animationend', () => { if (actuelle !== null && !barre.classList.contains('derniere')) aller(actuelle + 1); });
suivant.addEventListener('click', () => aller(actuelle + 1));
pause.addEventListener('click', () => {
    const enPause = barre.classList.toggle('en-pause');
    pause.textContent = enPause ? textes.reprendre : textes.pause;
});
barre.querySelector('[data-quitter]').addEventListener('click', quitter);
document.addEventListener('keydown', (e) => { if (e.key === 'Escape' && actuelle !== null && !document.querySelector('dialog[open], :popover-open')) quitter(); });
document.addEventListener('visite:demarrer', () => aller(0));
document.addEventListener('click', (e) => { if (e.target.closest('[data-visite-demarrer]')) aller(0); });

// Arrivée sur une page pendant la visite
const enCours = lire();
if (enCours !== null) {
    if (etapes[enCours] && ici(+enCours)) afficher(+enCours); else quitter();
}
