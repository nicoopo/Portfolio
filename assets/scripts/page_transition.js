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

/** Pour chaque effet : la page qui part, la page qui arrive, et le voile plein écran (_base.css) */
const EFFETS = {
    'trou-noir': { depart: 'scale(0.05) rotate(30deg)', filtre: 'blur(4px)', arrivee: 'scale(0.6) rotate(-6deg)' },
    teleportation: { depart: 'scaleX(1.25) scaleY(0.004)', filtre: 'brightness(3)', arrivee: 'scaleX(1.2) scaleY(0.02)' },
    distorsion: { depart: 'skewX(28deg) scale(1.08)', filtre: 'blur(6px) hue-rotate(160deg) saturate(3)', arrivee: 'skewX(-18deg) scale(0.96)' },
    lumiere: { depart: 'scale(2.6)', filtre: 'blur(10px)', arrivee: 'scale(0.35)' },
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

// Arrivée
document.addEventListener('DOMContentLoaded', () => {
    const main = document.querySelector('main');
    if (!main) return;
    const nom = storage((s) => s.getItem(ARRIVED_KEY));
    const effet = !sansTrouNoir() && EFFETS[nom] ? nom : null;
    storage((s) => s.removeItem(ARRIVED_KEY));

    main.style.opacity = 0;
    main.style.transformOrigin = `50% ${window.innerHeight / 2 - main.offsetTop}px`;
    main.style.transform = effet ? EFFETS[effet].arrivee : 'translateY(30px)';
    main.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
    const element = effet && voile(effet, true);
    requestAnimationFrame(() => requestAnimationFrame(() => {
        main.style.opacity = 1;
        main.style.transform = 'translateY(0)';
        if (element) {
            element.classList.remove('visible');
            element.addEventListener('transitionend', () => element.remove(), { once: true });
        }
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
    if (main) {
        main.style.transition = 'none';
        Object.assign(main.style, { transform: 'translateY(0)', opacity: 1, filter: '' });
    }
});
