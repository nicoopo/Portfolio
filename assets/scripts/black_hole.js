/*
 * Trou noir dessiné en canvas 2D : ombre noire, anneau de photons, disque d'accrétion
 * vu presque par la tranche, et l'arrière du disque replié au-dessus et en dessous par
 * la lumière déviée (comme Gargantua dans Interstellar). Le côté droit du disque vient
 * vers nous : il est plus lumineux (effet Doppler).
 * Utilisé par la page 404, la page projets et l'easter egg du curseur (stars.js).
 */

// Du bord intérieur (chaud) au bord extérieur (froid) : couleurs du site
const PALETTE = ['#fff4d6', '#ffb347', '#ff5fa2', '#7f5af0'];
const INNER = 1.5, OUTER = 3.6; // rayons du disque, en rayons d'ombre
const TILT = 0.22;              // aplatissement du disque (0 = vu par la tranche)

import { basseQualite } from './preferences.js';

/** Disque d'accrétion de `particles` grains ; draw(ctx, x, y, r, dt) dessine le tout, r = rayon de l'ombre. */
export function createBlackHole({ particles = 900 } = {}) {
    if (basseQualite()) particles = Math.round(particles / 3);
    const grains = Array.from({ length: particles }, () => {
        const t = Math.random() ** 1.6; // plus dense près du bord intérieur
        const rho = INNER + (OUTER - INNER) * t;
        return {
            rho,
            angle: Math.random() * Math.PI * 2,
            speed: 0.9 * rho ** -1.5, // Kepler : l'intérieur tourne plus vite
            size: 0.6 + Math.random() * 1.2,
            color: mix(t),
        };
    });

    return {
        draw(ctx, x, y, r, dt = 0) {
            grains.forEach((g) => { g.angle += g.speed * dt * 2; });
            const scale = Math.max(r / 40, 1);

            ctx.save();
            // Halo violet
            const halo = ctx.createRadialGradient(x, y, r, x, y, r * OUTER * 1.3);
            halo.addColorStop(0, 'rgba(127, 90, 240, 0.22)');
            halo.addColorStop(1, 'rgba(127, 90, 240, 0)');
            ctx.fillStyle = halo;
            ctx.fillRect(x - r * 5, y - r * 5, r * 10, r * 10);

            ctx.globalCompositeOperation = 'lighter';
            band(ctx, x, y, r, Math.PI, 0.7);
            // Arrière du disque, puis son image déviée au-dessus (vive) et en dessous (pâle)
            grains.forEach((g) => {
                const sin = Math.sin(g.angle), cos = Math.cos(g.angle);
                if (sin >= 0) return;
                const light = 0.6 + 0.4 * cos;
                dot(ctx, g, x + r * g.rho * cos, y + r * g.rho * sin * TILT, scale, light * 0.8);
                const bent = 1.05 + 0.35 * (g.rho - INNER) / (OUTER - INNER);
                dot(ctx, g, x + r * bent * cos, y + r * bent * sin, scale * 0.8, light * 0.7);
                const under = 1.02 + 0.12 * (g.rho - INNER) / (OUTER - INNER);
                dot(ctx, g, x + r * under * cos, y - r * under * sin, scale * 0.6, light * 0.25);
            });

            // Ombre, puis anneau de photons, plus vif côté droit
            ctx.globalCompositeOperation = 'source-over';
            ctx.fillStyle = '#000';
            ctx.beginPath();
            ctx.arc(x, y, r, 0, Math.PI * 2);
            ctx.fill();
            const ring = ctx.createLinearGradient(x - r, y, x + r, y);
            ring.addColorStop(0, 'rgba(255, 179, 71, 0.45)');
            ring.addColorStop(1, 'rgba(255, 244, 214, 0.95)');
            ctx.strokeStyle = ring;
            ctx.lineWidth = Math.max(1, r * 0.04);
            ctx.shadowColor = '#ffb347';
            ctx.shadowBlur = r * 0.3;
            ctx.beginPath();
            ctx.arc(x, y, r * 1.03, 0, Math.PI * 2);
            ctx.stroke();
            ctx.shadowBlur = 0;

            // Avant du disque, par-dessus l'ombre
            ctx.globalCompositeOperation = 'lighter';
            band(ctx, x, y, r, 0, 1);
            grains.forEach((g) => {
                const sin = Math.sin(g.angle), cos = Math.cos(g.angle);
                if (sin < 0) return;
                dot(ctx, g, x + r * g.rho * cos, y + r * g.rho * sin * TILT, scale, 0.6 + 0.4 * cos);
            });
            ctx.restore();
        },
    };
}

/** Canvas net sur écran haute densité : taille interne = taille affichée × densité de pixels. */
export function fitCanvas(canvas) {
    const ratio = basseQualite() ? 1 : Math.min(window.devicePixelRatio || 1, 2);
    canvas.width = canvas.clientWidth * ratio;
    canvas.height = canvas.clientHeight * ratio;
    const ctx = canvas.getContext('2d');
    ctx.setTransform(ratio, 0, 0, ratio, 0, 0);
    return ctx;
}

export const reducedMotion = () => window.matchMedia('(prefers-reduced-motion: reduce)').matches;

/** Lueur continue sous les grains : demi-disque (from = 0 devant, π derrière) et, derrière, son image déviée. */
function band(ctx, x, y, r, from, alpha) {
    const glow = ctx.createLinearGradient(x - r * OUTER, y, x + r * OUTER, y);
    glow.addColorStop(0, `rgba(255, 95, 162, ${0.12 * alpha})`);
    glow.addColorStop(0.5, `rgba(255, 179, 71, ${0.22 * alpha})`);
    glow.addColorStop(1, `rgba(255, 220, 160, ${0.35 * alpha})`);
    ctx.globalAlpha = 1;
    ctx.strokeStyle = glow;
    ctx.lineWidth = r * 0.3;
    ctx.beginPath();
    ctx.ellipse(x, y, r * 2.2, r * 2.2 * TILT, 0, from, from + Math.PI);
    ctx.stroke();
    if (from === 0) return;
    ctx.lineWidth = r * 0.12;
    ctx.beginPath();
    ctx.arc(x, y, r * 1.18, Math.PI, 2 * Math.PI);
    ctx.stroke();
}

function dot(ctx, grain, x, y, scale, alpha) {
    const s = grain.size * scale;
    ctx.globalAlpha = alpha;
    ctx.fillStyle = grain.color;
    ctx.fillRect(x - s / 2, y - s / 2, s, s);
}

/** Couleur de la palette à la position t (0 → 1). */
function mix(t) {
    const i = Math.min(Math.floor(t * (PALETTE.length - 1)), PALETTE.length - 2);
    const f = t * (PALETTE.length - 1) - i;
    const [a, b] = [PALETTE[i], PALETTE[i + 1]].map((hex) => [1, 3, 5].map((k) => parseInt(hex.slice(k, k + 2), 16)));
    return `rgb(${a.map((v, k) => Math.round(v + (b[k] - v) * f)).join(',')})`;
}
