/* stimulusFetch: 'lazy' */
import { Controller } from '@hotwired/stimulus';

const MEILLEUR = 'asteroides-record';
const VITESSE_VAISSEAU = 420; // px/s
const RAYON_VAISSEAU = 12;

/**
 * Page 404 : mini-jeu « évite les astéroïdes ». Le vaisseau glisse en bas (flèches ou A/D, ou doigt/souris
 * sur le terrain) ; les astéroïdes tombent de plus en plus vite ; le score est le temps tenu, en dixièmes de seconde.
 * Meilleur score gardé dans le navigateur. Lancé seulement par le bouton (rien ne bouge tout seul).
 */
export default class extends Controller {
    static targets = ['canvas', 'jouer', 'score', 'record'];
    static values = { textes: Object };

    connect() {
        this.record = this.lireRecord();
        this.afficherRecord();
        this.touches = new Set();
        this.clavier = (e) => {
            if (!this.enCours || !['ArrowLeft', 'ArrowRight', 'a', 'd', 'q'].includes(e.key)) return;
            e.preventDefault(); // pas de défilement de la page pendant la partie
            if (e.type === 'keydown') this.touches.add(e.key); else this.touches.delete(e.key);
        };
        document.addEventListener('keydown', this.clavier);
        document.addEventListener('keyup', this.clavier);
        // Doigt ou souris : le vaisseau suit le pointeur sur le terrain
        this.canvasTarget.addEventListener('pointermove', (e) => { if (this.enCours) this.cible = this.xCanvas(e); });
        this.canvasTarget.addEventListener('pointerdown', (e) => { if (this.enCours) this.cible = this.xCanvas(e); });
    }

    disconnect() {
        document.removeEventListener('keydown', this.clavier);
        document.removeEventListener('keyup', this.clavier);
        cancelAnimationFrame(this.frame);
    }

    jouer() {
        const canvas = this.canvasTarget;
        canvas.hidden = false;
        const ratio = Math.min(window.devicePixelRatio || 1, 2);
        this.largeur = canvas.clientWidth;
        this.hauteur = canvas.clientHeight;
        canvas.width = this.largeur * ratio;
        canvas.height = this.hauteur * ratio;
        this.ctx = canvas.getContext('2d');
        this.ctx.scale(ratio, ratio);
        const style = getComputedStyle(this.element);
        this.couleurs = { vaisseau: style.getPropertyValue('--color-secondary').trim() || '#00d4ff', roche: style.getPropertyValue('--color-primary').trim() || '#7f5af0' };

        this.vaisseau = this.largeur / 2;
        this.cible = null;
        this.roches = [];
        this.temps = 0;
        this.aLancer = 0;
        this.enCours = true;
        this.jouerTarget.hidden = true;
        this.scoreTarget.textContent = '';
        canvas.focus();
        this.avant = performance.now();
        this.frame = requestAnimationFrame((t) => this.image(t));
    }

    image(maintenant) {
        const dt = Math.min((maintenant - this.avant) / 1000, 0.05);
        this.avant = maintenant;
        this.temps += dt;

        // Vaisseau : clavier, ou glisse vers le pointeur
        const gauche = this.touches.has('ArrowLeft') || this.touches.has('a') || this.touches.has('q');
        const droite = this.touches.has('ArrowRight') || this.touches.has('d');
        if (gauche || droite) {
            this.cible = null;
            this.vaisseau += (droite - gauche) * VITESSE_VAISSEAU * dt;
        } else if (this.cible !== null) {
            const ecart = this.cible - this.vaisseau;
            this.vaisseau += Math.sign(ecart) * Math.min(Math.abs(ecart), VITESSE_VAISSEAU * 1.5 * dt);
        }
        this.vaisseau = Math.max(RAYON_VAISSEAU, Math.min(this.largeur - RAYON_VAISSEAU, this.vaisseau));

        // Astéroïdes : de plus en plus nombreux et rapides
        const difficulte = 1 + this.temps / 15;
        for (this.aLancer += dt * 1.6 * difficulte; this.aLancer >= 1; this.aLancer--) {
            const rayon = 8 + Math.random() * 16;
            this.roches.push({ x: Math.random() * this.largeur, y: -rayon, rayon, vitesse: (120 + Math.random() * 120) * difficulte, angle: Math.random() * 6 });
        }
        const yVaisseau = this.hauteur - 28;
        for (const roche of this.roches) {
            roche.y += roche.vitesse * dt;
            roche.angle += dt;
            if (Math.hypot(roche.x - this.vaisseau, roche.y - yVaisseau) < roche.rayon * 0.85 + RAYON_VAISSEAU * 0.7) return this.perdre();
        }
        this.roches = this.roches.filter((roche) => roche.y - roche.rayon < this.hauteur);

        this.dessiner(yVaisseau);
        this.frame = requestAnimationFrame((t) => this.image(t));
    }

    dessiner(yVaisseau) {
        const { ctx } = this;
        ctx.clearRect(0, 0, this.largeur, this.hauteur);
        ctx.fillStyle = this.couleurs.roche;
        for (const roche of this.roches) {
            ctx.beginPath();
            for (let i = 0; i < 7; i++) { // roche irrégulière
                const a = roche.angle + (i / 7) * Math.PI * 2;
                const r = roche.rayon * (0.75 + 0.25 * Math.sin(i * 2.3 + roche.rayon));
                ctx.lineTo(roche.x + Math.cos(a) * r, roche.y + Math.sin(a) * r);
            }
            ctx.closePath();
            ctx.fill();
        }
        ctx.fillStyle = this.couleurs.vaisseau;
        ctx.beginPath();
        ctx.moveTo(this.vaisseau, yVaisseau - RAYON_VAISSEAU * 1.4);
        ctx.lineTo(this.vaisseau + RAYON_VAISSEAU, yVaisseau + RAYON_VAISSEAU);
        ctx.lineTo(this.vaisseau - RAYON_VAISSEAU, yVaisseau + RAYON_VAISSEAU);
        ctx.closePath();
        ctx.fill();
        ctx.font = '600 14px Inter, sans-serif';
        ctx.fillText(this.points().toFixed(1), 10, 20);
    }

    perdre() {
        this.enCours = false;
        this.touches.clear();
        const score = this.points();
        const record = score > this.record;
        if (record) {
            this.record = score;
            try { localStorage.setItem(MEILLEUR, String(score)); } catch { /* navigation privée */ }
            this.afficherRecord();
        }
        this.scoreTarget.textContent = (record ? this.textesValue.record : this.textesValue.perdu).replace('%score%', score.toFixed(1));
        this.jouerTarget.textContent = this.textesValue.rejouer;
        this.jouerTarget.hidden = false;
        this.jouerTarget.focus();
    }

    points() { return Math.floor(this.temps * 10) / 10; }

    lireRecord() {
        try { return Number(localStorage.getItem(MEILLEUR)) || 0; } catch { return 0; }
    }

    afficherRecord() {
        this.recordTarget.textContent = this.record ? this.textesValue.meilleur.replace('%score%', this.record.toFixed(1)) : '';
    }

    xCanvas(e) { return e.clientX - this.canvasTarget.getBoundingClientRect().left; }
}
