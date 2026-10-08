/*
 * Transition entre les pages : en cliquant un lien du site, la page est aspirée par un
 * trou noir qui grandit jusqu'à remplir l'écran ; la page suivante en ressort.
 * Sans animation (prefers-reduced-motion ou réglage du menu) : simple fondu à l'arrivée, navigation normale.
 */
import { reducedMotion } from './black_hole.js';
import { preferences } from './preferences.js';

const sansTrouNoir = () => reducedMotion() || !preferences.transitions;

const DURATION_MS = 600;
const ARRIVED_KEY = 'trou-noir'; // posé avant de partir : la page suivante sort du trou noir

const storage = (action) => { try { return action(sessionStorage); } catch { return null; } };

function overlay(scale) {
    const hole = document.createElement('div');
    hole.className = 'trou-noir-transition';
    hole.setAttribute('aria-hidden', 'true');
    hole.style.transform = `scale(${scale})`;
    document.body.appendChild(hole);
    return hole;
}

// Arrivée
document.addEventListener('DOMContentLoaded', () => {
    const main = document.querySelector('main');
    if (!main) return;
    const fromHole = storage((s) => s.getItem(ARRIVED_KEY)) && !sansTrouNoir();
    storage((s) => s.removeItem(ARRIVED_KEY));

    main.style.opacity = 0;
    main.style.transformOrigin = `50% ${window.innerHeight / 2 - main.offsetTop}px`;
    main.style.transform = fromHole ? 'scale(0.6) rotate(-6deg)' : 'translateY(30px)';
    main.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
    const hole = fromHole && overlay(1);
    requestAnimationFrame(() => requestAnimationFrame(() => {
        main.style.opacity = 1;
        main.style.transform = 'translateY(0)';
        if (hole) {
            hole.style.transform = 'scale(0)';
            hole.addEventListener('transitionend', () => hole.remove(), { once: true });
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
    const main = document.querySelector('main');
    if (main) {
        // Aspirée vers le centre de l'écran, pas vers le centre de la page
        main.style.transformOrigin = `50% ${window.scrollY + window.innerHeight / 2 - main.offsetTop}px`;
        main.style.transition = `transform ${DURATION_MS}ms cubic-bezier(0.6, 0, 0.9, 0.4), opacity ${DURATION_MS}ms ease-in, filter ${DURATION_MS}ms`;
        main.style.transform = 'scale(0.05) rotate(30deg)';
        main.style.opacity = 0;
        main.style.filter = 'blur(4px)';
    }
    const hole = overlay(0);
    hole.getBoundingClientRect(); // applique scale(0) avant d'animer
    hole.style.transform = 'scale(1)';
    storage((s) => s.setItem(ARRIVED_KEY, '1'));
    setTimeout(() => { location.href = url.href; }, DURATION_MS);
});

// Retour arrière depuis le cache du navigateur : la page revient telle qu'on l'a laissée (aspirée)
window.addEventListener('pageshow', (e) => {
    if (!e.persisted) return;
    document.querySelectorAll('.trou-noir-transition').forEach((hole) => hole.remove());
    storage((s) => s.removeItem(ARRIVED_KEY));
    const main = document.querySelector('main');
    if (main) {
        main.style.transition = 'none';
        Object.assign(main.style, { transform: 'translateY(0)', opacity: 1, filter: '' });
    }
});
