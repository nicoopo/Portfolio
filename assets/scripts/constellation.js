/*
 * Easter egg : une constellation cachée en haut à droite de l'écran. Ses 7 étoiles brillent
 * un peu plus que les autres ; cliquées dans le bon ordre, elles se relient et dessinent
 * un « N » (Nico) : bas gauche → haut gauche → diagonale → haut droite.
 * Une mauvaise étoile efface tout. Coupé avec le réglage « Effets trou noir ».
 */
import { reducedMotion } from './black_hole.js';
import { preferences } from './preferences.js';
import { isEmptySpace } from './stars.js';

const TAILLE = 160;          // côté du canvas, en px
const RAYON_CLIC = 20;       // tolérance autour d'une étoile (doigt compris)
const TRACE_S = 0.35;        // durée du tracé d'un trait
const VICTOIRE_S = 5;        // durée de la constellation complète avant de s'effacer
// Le « N », dans l'ordre à suivre, légèrement irrégulier pour faire naturel
const ETOILES = [[22, 138], [18, 82], [24, 22], [78, 84], [138, 136], [142, 80], [136, 24]];

document.addEventListener('DOMContentLoaded', () => {
    const canvas = Object.assign(document.createElement('canvas'), { className: 'constellation' });
    canvas.setAttribute('aria-hidden', 'true');
    document.body.appendChild(canvas);
    const ratio = Math.min(window.devicePixelRatio || 1, 2);
    canvas.width = canvas.height = TAILLE * ratio;
    const ctx = canvas.getContext('2d');
    ctx.scale(ratio, ratio);
    const message = document.getElementById('constellationTrouvee');

    let reliees = 0;       // nombre d'étoiles reliées dans le bon ordre
    let traceDepuis = 0;   // début du tracé du dernier trait
    let victoire = null;   // moment où la constellation a été complétée
    let eclats = [];
    let temps = 0;

    const recommencer = () => { reliees = 0; victoire = null; eclats = []; };

    document.addEventListener('pointerdown', (e) => {
        if (!preferences.transitions || victoire !== null || !isEmptySpace(e.target)) return;
        const box = canvas.getBoundingClientRect();
        const x = e.clientX - box.left, y = e.clientY - box.top;
        const touchee = ETOILES.findIndex(([ex, ey]) => Math.hypot(ex - x, ey - y) < RAYON_CLIC);
        if (touchee === -1) return; // clic ailleurs : on garde la progression
        if (touchee !== reliees) { recommencer(); if (touchee !== 0) return; }
        reliees++;
        traceDepuis = temps;
        if (reliees === ETOILES.length) gagner();
    });

    const gagner = () => {
        victoire = temps;
        eclats = ETOILES.flatMap(([x, y]) => Array.from({ length: 8 }, () => {
            const angle = Math.random() * Math.PI * 2, vitesse = 20 + Math.random() * 50;
            return { x, y, vx: Math.cos(angle) * vitesse, vy: Math.sin(angle) * vitesse, vie: 1 };
        }));
        if (message) {
            message.hidden = false;
            setTimeout(() => { message.hidden = true; }, VICTOIRE_S * 1000);
        }
    };

    let avant = performance.now();
    const image = (maintenant) => {
        requestAnimationFrame(image);
        const dt = Math.min((maintenant - avant) / 1000, 0.05);
        avant = maintenant;
        temps += dt;
        ctx.clearRect(0, 0, TAILLE, TAILLE);
        if (!preferences.transitions) return;

        const calme = reducedMotion();
        // Constellation complète : tout brille puis s'efface doucement
        let eclat = 1;
        if (victoire !== null) {
            const age = temps - victoire;
            if (age > VICTOIRE_S) { recommencer(); return; }
            eclat = age < VICTOIRE_S - 1 ? 1 : VICTOIRE_S - age;
        }

        ctx.globalCompositeOperation = 'lighter';
        ctx.lineCap = 'round';
        for (let i = 1; i < reliees; i++) {
            const [x1, y1] = ETOILES[i - 1], [x2, y2] = ETOILES[i];
            const k = i === reliees - 1 && !calme ? Math.min((temps - traceDepuis) / TRACE_S, 1) : 1;
            ctx.strokeStyle = `rgba(127, 200, 255, ${(victoire !== null ? 0.9 : 0.6) * eclat})`;
            ctx.lineWidth = victoire !== null ? 2 : 1.2;
            ctx.shadowColor = '#7f5af0';
            ctx.shadowBlur = victoire !== null ? 10 : 4;
            ctx.beginPath();
            ctx.moveTo(x1, y1);
            ctx.lineTo(x1 + (x2 - x1) * k, y1 + (y2 - y1) * k);
            ctx.stroke();
        }
        ctx.shadowBlur = 0;

        ETOILES.forEach(([x, y], i) => {
            const allumee = i < reliees;
            const prochaine = i === reliees && reliees > 0; // l'étoile suivante scintille un peu plus
            const pulse = calme ? 0.5 : 0.5 + 0.5 * Math.sin(temps * (prochaine ? 5 : 1.6) + i * 1.3);
            const r = (allumee ? 2.6 : 1.7 + (prochaine ? 0.6 : 0.3) * pulse) * (victoire !== null ? 1.3 : 1);
            const halo = ctx.createRadialGradient(x, y, 0, x, y, r * 4);
            halo.addColorStop(0, `rgba(255, 255, 255, ${(allumee ? 1 : 0.65 + 0.3 * pulse) * eclat})`);
            halo.addColorStop(0.3, `rgba(170, 170, 255, ${(allumee ? 0.6 : 0.25) * eclat})`);
            halo.addColorStop(1, 'rgba(170, 170, 255, 0)');
            ctx.fillStyle = halo;
            ctx.fillRect(x - r * 4, y - r * 4, r * 8, r * 8);
        });

        eclats = eclats.filter((p) => (p.vie -= dt * 0.9) > 0);
        for (const p of eclats) {
            p.x += p.vx * dt;
            p.y += p.vy * dt;
            ctx.fillStyle = `rgba(255, 209, 106, ${p.vie})`;
            ctx.fillRect(p.x - 1, p.y - 1, 2, 2);
        }
    };
    requestAnimationFrame(image);
});
