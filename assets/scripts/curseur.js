/*
 * Curseur animé, au choix du visiteur (panneau ⚙, preferences.curseur) :
 * - comete : noyau lumineux et traînée de poussière d'étoiles
 * - orbite : point lumineux, anneau qui suit en retard, petite lune qui tourne
 * - trou-noir : mini trou noir comme ceux du site
 * - systeme : curseur normal du navigateur
 * Souris seulement (rien sur écran tactile). Caché pendant l'easter egg et sur les champs de texte.
 */
import { createBlackHole, fitCanvas, reducedMotion } from './black_hole.js';
import { preferences } from './preferences.js';

const CLIQUABLE = 'a, button, label, summary, select, [role="button"], input[type="checkbox"], input[type="radio"]';
const TEXTE = 'input:not([type="checkbox"]):not([type="radio"]), textarea, [contenteditable]';
const CYAN = '0, 212, 255', VIOLET = '127, 90, 240', ORANGE = '255, 179, 71';

if (window.matchMedia('(hover: hover) and (pointer: fine)').matches) {
    document.addEventListener('DOMContentLoaded', demarrer);
}

function demarrer() {
    // Popover : le canvas passe au-dessus du menu et des réglages, qui sont aussi dans la couche du dessus
    const canvas = Object.assign(document.createElement('canvas'), { className: 'curseur' });
    canvas.setAttribute('aria-hidden', 'true');
    canvas.popover = 'manual';
    document.body.appendChild(canvas);
    canvas.showPopover();
    document.addEventListener('toggle', (e) => {
        if (e.target !== canvas && e.newState === 'open') { canvas.hidePopover(); canvas.showPopover(); }
    }, true);

    let ctx = fitCanvas(canvas);
    window.addEventListener('resize', () => { ctx = fitCanvas(canvas); });

    const souris = { x: 0, y: 0, visible: false, survol: false, texte: false };
    document.addEventListener('pointermove', (e) => {
        if (e.pointerType !== 'mouse') return;
        Object.assign(souris, { x: e.clientX, y: e.clientY, visible: true });
        souris.survol = !!e.target.closest?.(CLIQUABLE);
        souris.texte = !!e.target.closest?.(TEXTE);
        if (preferences.curseur === 'comete') poussiere(souris.x, souris.y);
    });
    document.documentElement.addEventListener('mouseleave', () => { souris.visible = false; });

    // État de chaque style
    const grains = [];
    const poussiere = (x, y) => {
        if (reducedMotion() || grains.length > 200) return;
        for (let i = 0; i < 2; i++) {
            grains.push({ x, y, vx: (Math.random() - 0.5) * 0.6, vy: (Math.random() - 0.5) * 0.6, vie: 1, taille: 1 + Math.random() * 2, couleur: Math.random() < 0.5 ? CYAN : VIOLET });
        }
    };
    const anneau = { x: 0, y: 0, rayon: 14 };
    let lune = 0, survol = 0, temps = 0;
    let trouNoir = null;

    let avant = performance.now();
    const boucle = (maintenant) => {
        requestAnimationFrame(boucle);
        const dt = Math.min((maintenant - avant) / 1000, 0.05);
        avant = maintenant;
        temps += dt;
        const style = preferences.curseur;
        document.documentElement.classList.toggle('curseur-perso', style !== 'systeme');
        ctx.clearRect(0, 0, canvas.clientWidth, canvas.clientHeight);

        survol += ((souris.survol ? 1 : 0) - survol) * Math.min(dt * 12, 1); // passage en douceur sur un lien
        const cache = !souris.visible || souris.texte || document.body.classList.contains('trou-noir-ouvert');
        if (style === 'systeme' || cache) { grains.length = 0; anneau.x = souris.x; anneau.y = souris.y; return; }

        ctx.save();
        ctx.globalCompositeOperation = 'lighter';
        if (style === 'comete') comete(ctx, souris, grains, survol, temps, dt);
        if (style === 'orbite') { lune += reducedMotion() ? 0 : dt * 4; orbite(ctx, souris, anneau, survol, lune, dt); }
        ctx.restore();
        if (style === 'trou-noir') {
            trouNoir ??= createBlackHole({ particles: 150 });
            trouNoir.draw(ctx, souris.x, souris.y, 6.5 + 3 * survol, reducedMotion() ? 0 : dt * (1 + 2 * survol));
        }
    };
    requestAnimationFrame(boucle);
}

function lueur(ctx, x, y, r, couleur, alpha = 1) {
    const g = ctx.createRadialGradient(x, y, 0, x, y, r);
    g.addColorStop(0, `rgba(255, 255, 255, ${alpha})`);
    g.addColorStop(0.25, `rgba(${couleur}, ${alpha})`);
    g.addColorStop(1, `rgba(${couleur}, 0)`);
    ctx.fillStyle = g;
    ctx.fillRect(x - r, y - r, r * 2, r * 2);
}

function comete(ctx, souris, grains, survol, temps, dt) {
    for (let i = grains.length - 1; i >= 0; i--) {
        const g = grains[i];
        g.vie -= dt * 1.2;
        if (g.vie <= 0) { grains.splice(i, 1); continue; }
        g.x += g.vx;
        g.y += g.vy;
        ctx.fillStyle = `rgba(${g.couleur}, ${g.vie * 0.8})`;
        ctx.beginPath();
        ctx.arc(g.x, g.y, g.taille * g.vie, 0, Math.PI * 2);
        ctx.fill();
    }
    // Sur un lien : le noyau grossit et pulse
    const r = 13 + survol * (8 + 2 * Math.sin(temps * 6));
    lueur(ctx, souris.x, souris.y, r, CYAN);
    lueur(ctx, souris.x, souris.y, r * 0.5, VIOLET, 0.6);
}

function orbite(ctx, souris, anneau, survol, lune, dt) {
    const k = reducedMotion() ? 1 : 1 - Math.exp(-dt * 14); // l'anneau rattrape le point
    anneau.x += (souris.x - anneau.x) * k;
    anneau.y += (souris.y - anneau.y) * k;
    anneau.rayon = 14 + 10 * survol;

    lueur(ctx, souris.x, souris.y, 6, CYAN);
    ctx.strokeStyle = `rgba(${VIOLET}, ${0.7 + 0.3 * survol})`;
    ctx.lineWidth = 1.5;
    ctx.beginPath();
    ctx.arc(anneau.x, anneau.y, anneau.rayon, 0, Math.PI * 2);
    ctx.stroke();
    if (survol > 0.01) {
        ctx.fillStyle = `rgba(${VIOLET}, ${0.12 * survol})`;
        ctx.fill();
    }
    const lx = anneau.x + Math.cos(lune) * anneau.rayon, ly = anneau.y + Math.sin(lune) * anneau.rayon;
    lueur(ctx, lx, ly, 5, ORANGE);
}
