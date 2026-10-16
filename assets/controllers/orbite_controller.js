/* stimulusFetch: 'lazy' */
import { Controller } from '@hotwired/stimulus';
import { createBlackHole, fitCanvas, reducedMotion } from '../scripts/black_hole.js';
import { createBlackHoleGL } from '../scripts/black_hole_gl.js';
import { basseQualite } from '../scripts/preferences.js';
import { sons } from '../scripts/sons.js';

const SPEED = 0.1; // tour d'orbite, en radians par seconde
const TILT = 0.2;  // inclinaison de la vue sur le disque du trou noir (shader), en radians ; la souris la fait varier

/**
 * Page projets : les projets tournent autour d'un trou noir. Ceux de l'arrière passent
 * derrière l'ombre, ceux de devant par-dessus ; l'orbite s'arrête au survol.
 * Trou noir en shader (black_hole_gl.js) : un clic dessus le fait imploser en supernova, qui avale toute la page
 * (les morceaux désignés par pieces, un sélecteur CSS) puis la souffle et laisse un soleil ; un clic sur le soleil
 * le refait trou noir. Pendant ces séquences, le canvas couvre tout l'écran.
 * En qualité basse ou sans WebGL, sa version 2D.
 */
export default class extends Controller {
    static targets = ['canvas', 'projet'];
    static values = { pieces: String };

    connect() {
        this.gl = basseQualite() ? null : createBlackHoleGL(this.canvasTarget);
        this.hole = this.gl ? null : createBlackHole({ particles: 1100 });
        this.phase = 0;
        this.speed = SPEED;
        this.resize = () => {
            if (this.gl) this.gl.resize(); else this.ctx = fitCanvas(this.canvasTarget);
            this.draw(0);
        };
        this.resize();
        window.addEventListener('resize', this.resize);
        // Survol : l'orbite s'arrête doucement, pour viser le projet
        this.hovered = false;
        this.element.addEventListener('pointerover', (e) => { this.hovered = !!e.target.closest('.orbite-projet'); });
        this.element.addEventListener('pointerleave', () => { this.hovered = false; this.targetTilt = TILT; });
        // Souris en haut : on voit le disque un peu plus par-dessus ; en bas, plus par la tranche
        this.tilt = this.targetTilt = TILT;
        this.element.addEventListener('pointermove', (e) => {
            const box = this.element.getBoundingClientRect();
            // Pas trop à plat : vus par la tranche, les grains s'étireraient en traits
            this.targetTilt = Math.max(TILT + (0.5 - (e.clientY - box.top) / box.height) * 0.3, 0.1);
        });

        if (reducedMotion()) return;
        if (this.gl) {
            this.element.addEventListener('pointermove', (e) => { this.element.style.cursor = this.onHole(e) ? 'pointer' : ''; });
            this.element.addEventListener('click', (e) => {
                if (!this.onHole(e) || this.gl.busy) return;
                this.gl.burst();
                this.unleash();
                sons.trouNoir();
            });
        }
        let last = performance.now();
        const loop = (now) => {
            if (this.element.offsetWidth) this.draw(Math.min((now - last) / 1000, 0.05));
            last = now;
            this.frame = requestAnimationFrame(loop);
        };
        this.frame = requestAnimationFrame(loop);
    }

    disconnect() {
        cancelAnimationFrame(this.frame);
        window.removeEventListener('resize', this.resize);
        if (this.pieces) this.settle();
        this.gl?.destroy();
    }

    /** Début d'une séquence : le canvas couvre l'écran, les morceaux de la page sont repérés autour du trou noir */
    unleash() {
        this.canvasTarget.classList.add('orbite-plein-ecran');
        this.gl.resize();
        const box = this.element.getBoundingClientRect();
        const holeX = box.left + box.width / 2, holeY = box.top + box.height / 2;
        this.pieces = [...document.querySelectorAll(this.piecesValue || ':not(*)')]
            .filter((el) => !this.element.contains(el) && el.getClientRects().length)
            .map((el) => {
                const rect = el.getBoundingClientRect();
                const dx = holeX - (rect.left + rect.width / 2), dy = holeY - (rect.top + rect.height / 2);
                el.style.transition = 'none'; // pas de retard sur les effets de survol
                el.style.animation = 'none'; // une animation CSS (flottement de la barre) l'emporterait sur transform
                el.style.willChange = 'transform, opacity';
                return { el, dx, dy, distance: Math.hypot(dx, dy), twist: (Math.random() < 0.5 ? -1 : 1) * (90 + Math.random() * 180) };
            });
        const farthest = Math.max(1, ...this.pieces.map((piece) => piece.distance));
        this.pieces.forEach((piece) => { piece.delay = 0.6 * piece.distance / farthest; }); // les plus proches partent d'abord
    }

    /** Fin de la séquence : la page et le canvas reprennent leur place */
    settle() {
        this.pieces.forEach(({ el }) => { ['transform', 'opacity', 'transition', 'animation', 'willChange'].forEach((p) => { el.style[p] = ''; }); });
        this.pieces = null;
        this.canvasTarget.classList.remove('orbite-plein-ecran');
        this.gl.resize();
    }

    /**
     * Morceaux de la page : avant l'explosion, chacun est aspiré à son tour (suck, de 0 à 1) en tournoyant ;
     * ensuite, ils suivent tous pull (0 = dans le trou, plus de 1 = soufflés au-delà de leur place)
     */
    movePieces({ suck, pull }) {
        this.pieces.forEach(({ el, dx, dy, twist, delay }) => {
            const a = Math.min(Math.max((suck - delay) / (1 - delay), 0), 1);
            const t = suck > 0 ? a * a : 1 - pull; // part du chemin vers le trou
            const inside = Math.min(Math.max(t, 0), 1);
            el.style.transform = `translate(${dx * t}px, ${dy * t}px) rotate(${twist * inside}deg) scale(${1 - 0.95 * inside})`;
            el.style.opacity = Math.min(Math.max((1 - t) * 5, 0), 1);
        });
    }

    /** Centre et rayon de l'ombre du trou noir, en pixels dans l'orbite */
    geometry() {
        const w = this.element.clientWidth, h = this.element.clientHeight;
        return { w, h, x: w / 2, y: h / 2, r: Math.min(w * 0.07, h * 0.12) };
    }

    /** Pointeur sur le trou noir (son disque, vu presque par la tranche), pas sur un projet */
    onHole(e) {
        if (e.target.closest('.orbite-projet')) return false;
        const box = this.element.getBoundingClientRect();
        const { x, y, r } = this.geometry();
        return ((e.clientX - box.left - x) / (2.9 * r)) ** 2 + ((e.clientY - box.top - y) / (1.3 * r)) ** 2 <= 1;
    }

    draw(dt) {
        const { w, h, x, y, r } = this.geometry();
        // Pendant la supernova, les projets filent sous le pointeur : on ne s'arrête pas pour autant
        const stop = this.hovered && !this.gl?.busy;
        this.speed += ((stop ? 0 : SPEED) - this.speed) * Math.min(dt * 4, 1);
        this.phase += this.speed * dt;

        const holeDt = dt * (0.3 + 0.7 * this.speed / SPEED);
        let pull = 1; // rayon de l'orbite : 0 quand le trou noir a tout avalé, plus de 1 quand l'explosion souffle
        if (this.gl) {
            this.tilt += (this.targetTilt - this.tilt) * Math.min(dt * 3, 1);
            // En plein écran, le trou noir reste au centre de l'orbite, là où elle est dans la fenêtre
            const box = this.pieces ? this.element.getBoundingClientRect() : { left: 0, top: 0 };
            const fx = this.gl.draw(box.left + x, box.top + y, r, holeDt, this.tilt);
            pull = fx.pull;
            fx.events.forEach((event) => ({ boom: sons.supernova, birth: sons.constellation, reborn: sons.impact })[event]());
            if (this.pieces) {
                if (this.gl.busy) this.movePieces(fx); else this.settle();
            }
        } else {
            this.ctx.clearRect(0, 0, w, h);
            this.hole.draw(this.ctx, x, y, r, holeDt);
        }

        // Deux anneaux en alternance, pour que les vignettes se chevauchent moins
        const n = this.projetTargets.length;
        this.projetTargets.forEach((el, i) => {
            const angle = this.phase + (i / n) * Math.PI * 2;
            const ring = i % 2 ? 0.72 : 1;
            const rx = (w / 2 - 60) * ring, ry = (h / 2 - 50) * ring * 0.8;
            const depth = Math.sin(angle); // 1 = devant, -1 = derrière
            const shrink = 0.3 + 0.7 * Math.min(pull, 1);
            el.style.transform = `translate(${x + rx * Math.cos(angle) * pull}px, ${y + ry * depth * pull}px) translate(-50%, -50%) scale(${(0.82 + 0.18 * depth) * shrink})`;
            el.style.opacity = (0.55 + 0.45 * (depth + 1) / 2) * Math.min(pull * 4, 1);
            el.style.zIndex = 50 + Math.round(depth * 49); // canvas (ombre du trou noir) à 50 : l'arrière passe dessous
        });
    }
}
