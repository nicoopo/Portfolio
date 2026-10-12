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
 * Trou noir en shader (black_hole_gl.js) : un clic dessus lui fait aspirer son disque ;
 * en qualité basse ou sans WebGL, sa version 2D.
 */
export default class extends Controller {
    static targets = ['canvas', 'projet'];

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
                if (!this.onHole(e)) return;
                this.gl.burst();
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
        this.gl?.destroy();
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
        this.speed += ((this.hovered ? 0 : SPEED) - this.speed) * Math.min(dt * 4, 1);
        this.phase += this.speed * dt;

        const holeDt = dt * (0.3 + 0.7 * this.speed / SPEED);
        if (this.gl) {
            this.tilt += (this.targetTilt - this.tilt) * Math.min(dt * 3, 1);
            this.gl.draw(x, y, r, holeDt, this.tilt);
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
            el.style.transform = `translate(${x + rx * Math.cos(angle)}px, ${y + ry * depth}px) translate(-50%, -50%) scale(${0.82 + 0.18 * depth})`;
            el.style.opacity = 0.55 + 0.45 * (depth + 1) / 2;
            el.style.zIndex = 50 + Math.round(depth * 49); // canvas (ombre du trou noir) à 50 : l'arrière passe dessous
        });
    }
}
