/*
 * Easter egg : le code Konami (↑ ↑ ↓ ↓ ← → ← → B A) déclenche une pluie de météores.
 * Coupé avec le réglage « Effets trou noir » ou les mouvements réduits.
 */
import { fitCanvas, reducedMotion } from './black_hole.js';
import { basseQualite, preferences } from './preferences.js';

const CODE = ['arrowup', 'arrowup', 'arrowdown', 'arrowdown', 'arrowleft', 'arrowright', 'arrowleft', 'arrowright', 'b', 'a'].join();
const DUREE_S = 5;            // temps pendant lequel de nouveaux météores apparaissent
const ANGLE = Math.PI * 0.22; // direction de chute : vers le bas à droite
const COULEURS = ['255, 255, 255', '0, 212, 255', '127, 90, 240', '255, 179, 71'];

const tapees = [];
let enCours = false;

document.addEventListener('keydown', (e) => {
    if (e.target.closest?.('input, textarea, select, [contenteditable]')) return;
    tapees.push(e.key.toLowerCase());
    if (tapees.length > 10) tapees.shift();
    if (tapees.join() !== CODE) return;
    tapees.length = 0;
    if (!enCours && preferences.transitions && !reducedMotion()) pluie();
});

function pluie() {
    enCours = true;
    const canvas = Object.assign(document.createElement('canvas'), { className: 'pluie-meteores' });
    canvas.setAttribute('aria-hidden', 'true');
    document.body.appendChild(canvas);
    const ctx = fitCanvas(canvas);
    const largeur = canvas.clientWidth, hauteur = canvas.clientHeight;
    const parSeconde = basseQualite() ? 10 : 28;

    const meteores = [];
    let temps = 0, aLancer = 0, avant = performance.now();
    const lancer = () => {
        const vitesse = 700 + Math.random() * 700;
        // Départ au-dessus de l'écran, décalé à gauche pour que les trajectoires le traversent
        meteores.push({
            x: (Math.random() * 1.3 - 0.3) * largeur,
            y: -40 - Math.random() * hauteur * 0.3,
            vx: Math.cos(ANGLE) * vitesse,
            vy: Math.sin(ANGLE) * vitesse,
            longueur: 80 + Math.random() * 180,
            epaisseur: 1 + Math.random() * 2,
            couleur: COULEURS[Math.floor(Math.random() * COULEURS.length)],
        });
    };

    const image = (maintenant) => {
        const dt = Math.min((maintenant - avant) / 1000, 0.05);
        avant = maintenant;
        temps += dt;
        if (temps < DUREE_S) {
            for (aLancer += dt * parSeconde; aLancer >= 1; aLancer--) lancer();
        }

        ctx.clearRect(0, 0, largeur, hauteur);
        ctx.globalCompositeOperation = 'lighter';
        for (let i = meteores.length - 1; i >= 0; i--) {
            const m = meteores[i];
            m.x += m.vx * dt;
            m.y += m.vy * dt;
            if (m.y - m.longueur > hauteur || m.x - m.longueur > largeur) { meteores.splice(i, 1); continue; }
            trainee(ctx, m);
        }

        if (temps < DUREE_S || meteores.length) {
            requestAnimationFrame(image);
        } else {
            canvas.remove();
            enCours = false;
        }
    };
    requestAnimationFrame(image);
}

function trainee(ctx, m) {
    const k = m.longueur / Math.hypot(m.vx, m.vy);
    const queueX = m.x - m.vx * k, queueY = m.y - m.vy * k;
    const g = ctx.createLinearGradient(m.x, m.y, queueX, queueY);
    g.addColorStop(0, `rgba(${m.couleur}, 1)`);
    g.addColorStop(1, `rgba(${m.couleur}, 0)`);
    ctx.strokeStyle = g;
    ctx.lineWidth = m.epaisseur;
    ctx.lineCap = 'round';
    ctx.beginPath();
    ctx.moveTo(m.x, m.y);
    ctx.lineTo(queueX, queueY);
    ctx.stroke();
    // Tête lumineuse
    const tete = ctx.createRadialGradient(m.x, m.y, 0, m.x, m.y, m.epaisseur * 4);
    tete.addColorStop(0, 'rgba(255, 255, 255, 0.9)');
    tete.addColorStop(1, `rgba(${m.couleur}, 0)`);
    ctx.fillStyle = tete;
    ctx.fillRect(m.x - m.epaisseur * 4, m.y - m.epaisseur * 4, m.epaisseur * 8, m.epaisseur * 8);
}
