/*
 * Transition entre les pages : en cliquant un lien du site, la page part avec un effet (trou noir qui l'aspire,
 * téléportation, distorsion, saut en vitesse lumière) et la page suivante arrive avec le même.
 * Effet choisi dans les Réglages (preferences.effet), ou au hasard.
 * Sans animation (prefers-reduced-motion ou réglage du menu) : simple fondu à l'arrivée, navigation normale.
 */
import { reducedMotion } from './black_hole.js';
import { preferences } from './preferences.js';

const sansTrouNoir = () => reducedMotion() || !preferences.transitions;

const DURATION_MS = 600;
const ARRIVED_KEY = 'trou-noir'; // posé avant de partir (nom de l'effet) : la page suivante arrive avec le même

/** Pour chaque effet : la page qui part (l'arrivée est dans _base.css, @keyframes arrivee-*) et le voile plein écran */
const EFFETS = {
    'trou-noir': { depart: 'scale(0.05) rotate(30deg)', filtre: 'blur(4px)' },
    teleportation: { depart: 'scaleX(1.25) scaleY(0.004)', filtre: 'brightness(3)' },
    distorsion: { depart: 'skewX(28deg) scale(1.08)', filtre: 'blur(6px) hue-rotate(160deg) saturate(3)' },
    lumiere: { depart: 'scale(2.6)', filtre: 'blur(10px)' },
};

const storage = (action) => { try { return action(sessionStorage); } catch { return null; } };

const choisir = () => (EFFETS[preferences.effet] ? preferences.effet
    : Object.keys(EFFETS)[Math.floor(Math.random() * Object.keys(EFFETS).length)]);

/** Voile de l'effet ; .visible le déploie (transition CSS) */
function voile(effet, visible) {
    const element = document.createElement('div');
    element.className = `transition-voile transition-voile--${effet}${visible ? ' visible' : ''}`;
    element.setAttribute('aria-hidden', 'true');
    document.body.appendChild(element);
    return element;
}

// Arrivée : la page s'anime en CSS (_base.css, html[data-arrivee] posé dans base.html.twig) ; ici, le voile qui se replie
document.addEventListener('DOMContentLoaded', () => {
    const effet = document.documentElement.dataset.arrivee;
    if (!EFFETS[effet] || sansTrouNoir()) return;
    const element = voile(effet, true);
    requestAnimationFrame(() => requestAnimationFrame(() => {
        element.classList.remove('visible');
        element.addEventListener('transitionend', () => element.remove(), { once: true });
    }));
});

// Départ
document.addEventListener('click', (e) => {
    const link = e.target.closest('a[href]');
    if (!link || e.defaultPrevented || e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
    if ((link.target && link.target !== '_self') || link.hasAttribute('download') || sansTrouNoir()) return;
    const url = new URL(link.href, location.href);
    if (url.origin !== location.origin || /\.\w+$/.test(url.pathname)) return; // autre site, fichier (PDF…)
    if (url.pathname === location.pathname && url.search === location.search) return; // ancre de la même page

    e.preventDefault();
    const effet = choisir();
    const main = document.querySelector('main');
    if (main) {
        // Vers le centre de l'écran, pas vers le centre de la page
        main.style.transformOrigin = `50% ${window.scrollY + window.innerHeight / 2 - main.offsetTop}px`;
        main.style.transition = `transform ${DURATION_MS}ms cubic-bezier(0.6, 0, 0.9, 0.4), opacity ${DURATION_MS}ms ease-in, filter ${DURATION_MS}ms`;
        main.style.transform = EFFETS[effet].depart;
        main.style.opacity = 0;
        main.style.filter = EFFETS[effet].filtre;
    }
    const element = voile(effet, false);
    element.getBoundingClientRect(); // applique l'état replié avant d'animer
    element.classList.add('visible');
    storage((s) => s.setItem(ARRIVED_KEY, effet));
    setTimeout(() => { location.href = url.href; }, DURATION_MS);
});

// Retour arrière depuis le cache du navigateur : la page revient telle qu'on l'a laissée (partie)
window.addEventListener('pageshow', (e) => {
    if (!e.persisted) return;
    document.querySelectorAll('.transition-voile').forEach((element) => element.remove());
    storage((s) => s.removeItem(ARRIVED_KEY));
    const main = document.querySelector('main');
    if (main) Object.assign(main.style, { transition: 'none', transform: '', opacity: '', filter: '' });
});
