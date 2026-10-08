import { createBlackHole } from './black_hole.js';
import { basseQualite, preferences } from './preferences.js';
import { decouvrir } from './decouvertes.js';

/** « Le vide » pour les easter eggs : pas un lien, un champ, une image, le cerveau 3D… ni du texte */
export const isEmptySpace = (el) => !el.closest('a, button, input, textarea, select, label, summary, img, video, canvas, iframe, svg, nav, [contenteditable]')
    && ![...el.childNodes].some((node) => node.nodeType === Node.TEXT_NODE && node.textContent.trim());

document.addEventListener('DOMContentLoaded', () => {
    const canvas = document.createElement('canvas');
    canvas.id = 'stars';
    canvas.setAttribute('aria-hidden', 'true'); // décor : ignoré par les lecteurs d'écran
    document.body.appendChild(canvas);
    const ctx = canvas.getContext('2d');

    let stars = [];
    const STAR_COUNT = basseQualite() ? 60 : 150;
    let mouseX = 0, mouseY = 0;
    let centreX = 0, centreY = 0; // centre de l'écran, relu seulement au redimensionnement (le lire à chaque image force un recalcul de mise en page)

    const resize = () => {
        centreX = window.innerWidth / 2;
        centreY = window.innerHeight / 2;
        canvas.width = window.innerWidth;
        canvas.height = Math.max(document.body.scrollHeight, window.innerHeight);
        stars = Array.from({ length: STAR_COUNT }, () => ({
            x: Math.random() * canvas.width,
            y: Math.random() * canvas.height,
            r: Math.random() * 1.5 + 0.2,
            s: Math.random() * 0.3 + 0.1,
            depth: Math.random() * 1.5 + 0.5 // profondeur
        }));
    };

    const animate = () => {
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        stars.forEach(star => {
            // effet de profondeur dynamique
            const offsetX = (mouseX - centreX) * 0.002 * star.depth;
            const offsetY = (mouseY - centreY) * 0.002 * star.depth;

            if (hole.strength > 0) pull(star, offsetX, offsetY);

            ctx.beginPath();
            ctx.arc(star.x + offsetX, star.y + offsetY, star.r, 0, Math.PI * 2);
            ctx.fillStyle = `rgba(255, 255, 255, ${0.7 / star.depth})`;
            ctx.shadowBlur = basseQualite() ? 0 : 8; // le flou d'ombre coûte cher
            ctx.shadowColor = '#aaf';
            ctx.fill();

            star.y += star.s;
            if (star.y > canvas.height) star.y = 0;
        });
        requestAnimationFrame(animate);
    };

    const onMouseMove = (e) => {
        mouseX = e.clientX;
        mouseY = e.clientY;
    };

    const onScroll = () => {
        const scrollOffset = window.scrollY * 0.3;
        canvas.style.transform = `translateY(${scrollOffset}px)`;
    };

    // ------------------------------------------------
    // Easter egg : un clic long dans le vide fait apparaître un mini trou noir sous le
    // curseur, qui attire et avale les étoiles ; il se referme quand on relâche.
    // ------------------------------------------------
    const HOLD_MS = 700;
    const hole = { x: 0, y: 0, strength: 0, target: 0, timer: null, canvas: null, ctx: null, disk: null, frame: null };

    // Le canvas des étoiles est étiré à la hauteur de l'écran et décalé au défilement :
    // on passe en coordonnées écran pour que l'attraction soit ronde
    const pull = (star, offsetX, offsetY) => {
        const sy = canvas.height / window.innerHeight;
        const shift = window.scrollY * 0.3;
        const dx = hole.x - (star.x + offsetX);
        const dy = hole.y - ((star.y + offsetY) / sy + shift);
        const d = Math.hypot(dx, dy);
        if (d > 320 * hole.strength) return;
        if (d < 14 * hole.strength) { // avalée : renaît en haut
            star.x = Math.random() * canvas.width;
            star.y = 0;
            return;
        }
        const step = Math.min(d, (hole.strength * 900) / (d + 30));
        // vers le trou + un peu de côté : les étoiles tombent en spirale
        star.x += (dx + dy * 0.8) / d * step;
        star.y += (dy - dx * 0.8) / d * step * sy;
    };

    const drawHole = () => {
        hole.strength += (hole.target - hole.strength) * 0.12;
        const { ctx: hctx, canvas: hcanvas } = hole;
        hctx.clearRect(0, 0, hcanvas.width, hcanvas.height);
        if (hole.target === 0 && hole.strength < 0.02) {
            hole.strength = 0;
            hcanvas.remove();
            hole.canvas = null;
            return;
        }
        hole.disk.draw(hctx, hole.x, hole.y, 16 * hole.strength, 1 / 60);
        hole.frame = requestAnimationFrame(drawHole);
    };

    const open = () => {
        hole.target = 1;
        document.body.classList.add('trou-noir-ouvert');
        decouvrir('trou-noir');
        if (hole.canvas) return;
        hole.canvas = Object.assign(document.createElement('canvas'), { className: 'trou-noir-curseur' });
        hole.canvas.setAttribute('aria-hidden', 'true');
        hole.canvas.width = window.innerWidth;
        hole.canvas.height = window.innerHeight;
        document.body.appendChild(hole.canvas);
        hole.ctx = hole.canvas.getContext('2d');
        hole.disk ??= createBlackHole({ particles: 300 });
        cancelAnimationFrame(hole.frame);
        drawHole();
    };

    const close = () => {
        clearTimeout(hole.timer);
        hole.timer = null;
        hole.target = 0;
        document.body.classList.remove('trou-noir-maintenu', 'trou-noir-ouvert');
    };

    let start = null;
    document.addEventListener('pointerdown', (e) => {
        if (e.button !== 0 || !preferences.transitions || !isEmptySpace(e.target)) return;
        // Dès le début de l'appui : sur mobile, la sélection de texte arrive avant le trou noir
        document.body.classList.add('trou-noir-maintenu');
        window.getSelection()?.removeAllRanges();
        start = { x: e.clientX, y: e.clientY };
        hole.x = e.clientX;
        hole.y = e.clientY;
        hole.timer = setTimeout(open, HOLD_MS);
    });
    document.addEventListener('pointermove', (e) => {
        if (!start) return;
        if (hole.target === 0 && Math.hypot(e.clientX - start.x, e.clientY - start.y) > 8) {
            start = null; // on glisse : ce n'est pas un clic long
            close();
            return;
        }
        hole.x = e.clientX;
        hole.y = e.clientY;
    });
    // Appui long au doigt : ni menu « Copier » ni loupe pendant le trou noir
    document.addEventListener('contextmenu', (e) => { if (start || hole.target) e.preventDefault(); });
    // Trou noir ouvert : le doigt le déplace au lieu de faire défiler la page (sinon pointercancel le referme)
    document.addEventListener('touchmove', (e) => { if (hole.target) e.preventDefault(); }, { passive: false });
    ['pointerup', 'pointercancel'].forEach((type) => document.addEventListener(type, () => { start = null; close(); }));

    resize();
    animate();
    window.addEventListener('resize', resize);
    window.addEventListener('mousemove', onMouseMove);
    window.addEventListener('scroll', onScroll);
});


