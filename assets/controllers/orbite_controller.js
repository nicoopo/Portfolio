/* stimulusFetch: 'lazy' */
import { Controller } from '@hotwired/stimulus';
import { createBlackHole, fitCanvas, reducedMotion } from '../scripts/black_hole.js';

const SPEED = 0.1; // tour d'orbite, en radians par seconde

/**
 * Page projets : les projets tournent autour d'un trou noir. Ceux de l'arrière passent
 * derrière l'ombre, ceux de devant par-dessus ; l'orbite s'arrête au survol.
 */
export default class extends Controller {
    static targets = ['canvas', 'projet'];

    connect() {
        this.hole = createBlackHole({ particles: 1100 });
        this.phase = 0;
        this.speed = SPEED;
        this.resize = () => { this.ctx = fitCanvas(this.canvasTarget); this.draw(0); };
        this.resize();
        window.addEventListener('resize', this.resize);
        // Survol : l'orbite s'arrête doucement, pour viser le projet
        this.hovered = false;
        this.element.addEventListener('pointerover', (e) => { this.hovered = !!e.target.closest('.orbite-projet'); });
        this.element.addEventListener('pointerleave', () => { this.hovered = false; });

        if (reducedMotion()) return;
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
    }

    draw(dt) {
        const w = this.element.clientWidth, h = this.element.clientHeight;
        const x = w / 2, y = h / 2, r = Math.min(w * 0.07, h * 0.12);
        this.speed += ((this.hovered ? 0 : SPEED) - this.speed) * Math.min(dt * 4, 1);
        this.phase += this.speed * dt;

        this.ctx.clearRect(0, 0, w, h);
        this.hole.draw(this.ctx, x, y, r, dt * (0.3 + 0.7 * this.speed / SPEED));

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
