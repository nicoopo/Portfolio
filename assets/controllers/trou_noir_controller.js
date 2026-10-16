/* stimulusFetch: 'lazy' */
import { Controller } from '@hotwired/stimulus';
import { createBlackHole, fitCanvas, reducedMotion } from '../scripts/black_hole.js';

// Morceaux de la page perdue, aspirés en spirale
const DEBRIS = ['404', '<div>', '</>', '{ }', 'GET', '/page', '?', 'null', 'undefined', '<h1>', 'href', '0', '1', ';'];
const DEBRIS_COUNT = 14;

/**
 * Page 404 : le « 0 » est un trou noir qui aspire les morceaux de la page.
 * <span data-controller="trou-noir"><canvas data-trou-noir-target="canvas"></canvas></span>
 */
export default class extends Controller {
    static targets = ['canvas'];

    connect() {
        this.hole = createBlackHole({ particles: 700 });
        this.debris = Array.from({ length: DEBRIS_COUNT }, () => this.spawn(Math.random()));
        this.resize = () => { this.ctx = fitCanvas(this.canvasTarget); };
        this.resize();
        window.addEventListener('resize', this.resize);

        if (reducedMotion()) {
            this.draw(0);
            return;
        }
        let last = performance.now();
        const loop = (now) => {
            this.draw(Math.min((now - last) / 1000, 0.05));
            last = now;
            this.frame = requestAnimationFrame(loop);
        };
        this.frame = requestAnimationFrame(loop);
    }

    disconnect() {
        cancelAnimationFrame(this.frame);
        window.removeEventListener('resize', this.resize);
    }

    /** Débris au bord, ou déjà en route (progress 0 → 1) pour ne pas tous arriver ensemble. */
    spawn(progress = 0) {
        return {
            text: DEBRIS[Math.floor(Math.random() * DEBRIS.length)],
            distance: 1 - progress * 0.8, // part du rayon du canvas
            angle: Math.random() * Math.PI * 2,
            fall: 0.08 + Math.random() * 0.06,
        };
    }

    draw(dt) {
        const { ctx } = this;
        const w = this.canvasTarget.clientWidth, h = this.canvasTarget.clientHeight;
        const x = w / 2, y = h / 2, r = Math.min(w, h) * 0.085, edge = Math.min(w, h) / 2;
        ctx.clearRect(0, 0, w, h);

        // Débris : la chute accélère et la rotation aussi en approchant ; ils s'étirent puis disparaissent
        ctx.textAlign = 'center';
        ctx.textBaseline = 'middle';
        this.debris = this.debris.map((d) => {
            d.distance -= d.fall * dt / Math.max(d.distance, 0.15);
            d.angle += 0.6 * dt / d.distance;
            const rho = d.distance * edge;
            if (rho < r * 1.1) return this.spawn();

            const near = Math.min(1, (rho - r) / (edge * 0.5));
            ctx.save();
            ctx.translate(x + rho * Math.cos(d.angle), y + rho * Math.sin(d.angle) * 0.6);
            ctx.rotate(d.angle + Math.PI / 2);
            ctx.scale(0.4 + 0.6 * near, 1 + 1.5 * (1 - near)); // étirés vers le centre (spaghettification)
            ctx.globalAlpha = Math.min(near * 2, 1 - d.distance) * 0.85;
            ctx.fillStyle = '#cfe9ff';
            ctx.font = '600 14px monospace';
            ctx.fillText(d.text, 0, 0);
            ctx.restore();
            return d;
        });

        this.hole.draw(ctx, x, y, r, dt);
    }
}
