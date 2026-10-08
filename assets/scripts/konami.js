/*
 * Easter egg : le code Konami (↑ ↑ ↓ ↓ ← → ← → B A) déclenche une pluie de météores ;
 * les plus gros s'écrasent : explosion, onde de choc et secousse de la page.
 * Coupé avec le réglage « Effets trou noir » ou les mouvements réduits.
 */
import { fitCanvas, reducedMotion } from './black_hole.js';
import { basseQualite, preferences } from './preferences.js';

const CODE = ['arrowup', 'arrowup', 'arrowdown', 'arrowdown', 'arrowleft', 'arrowright', 'arrowleft', 'arrowright', 'b', 'a'].join();
const DUREE_S = 5;            // temps pendant lequel de nouveaux météores apparaissent
const ANGLE = Math.PI * 0.22; // direction de chute : vers le bas à droite
const COULEURS = ['255, 255, 255', '0, 212, 255', '127, 90, 240', '255, 179, 71'];
const PART_IMPACTS = 0.1;      // part des météores qui s'écrasent
const GRAVITE = 300;          // chute des éclats, en px/s²

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

    const meteores = [], eclats = [], ondes = [];
    let temps = 0, aLancer = 0, avant = performance.now();
    const lancer = () => {
        const vitesse = 700 + Math.random() * 700;
        const gros = Math.random() < PART_IMPACTS;
        // Départ au-dessus de l'écran, décalé à gauche pour que les trajectoires le traversent
        meteores.push({
            x: (Math.random() * 1.3 - 0.3) * largeur,
            y: -40 - Math.random() * hauteur * 0.3,
            vx: Math.cos(ANGLE) * vitesse,
            vy: Math.sin(ANGLE) * vitesse,
            longueur: gros ? 220 + Math.random() * 80 : 80 + Math.random() * 180,
            epaisseur: gros ? 3 + Math.random() : 1 + Math.random() * 2,
            impact: gros ? hauteur * (0.35 + Math.random() * 0.55) : null, // hauteur où il s'écrase
            couleur: COULEURS[Math.floor(Math.random() * COULEURS.length)],
        });
    };
    const exploser = (m) => {
        for (let i = 0, n = basseQualite() ? 15 : 40; i < n; i++) {
            const angle = Math.random() * Math.PI * 2, vitesse = 80 + Math.random() * 380;
            eclats.push({
                x: m.x, y: m.y, vx: Math.cos(angle) * vitesse, vy: Math.sin(angle) * vitesse - 120, vie: 1,
                taille: 1 + Math.random() * 2.5, couleur: Math.random() < 0.5 ? m.couleur : '255, 179, 71',
            });
        }
        ondes.push({ x: m.x, y: m.y, rayon: 0, vie: 1, couleur: m.couleur });
        secousse();
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
            if (m.impact && m.y >= m.impact) {
                if (m.x > 0 && m.x < largeur) exploser(m); // hors de l'écran : il disparaît sans bruit
                meteores.splice(i, 1);
                continue;
            }
            if (m.y - m.longueur > hauteur || m.x - m.longueur > largeur) { meteores.splice(i, 1); continue; }
            trainee(ctx, m);
        }
        for (let i = ondes.length - 1; i >= 0; i--) {
            const o = ondes[i];
            o.vie -= dt * 2;
            if (o.vie <= 0) { ondes.splice(i, 1); continue; }
            o.rayon += dt * 420;
            onde(ctx, o);
        }
        for (let i = eclats.length - 1; i >= 0; i--) {
            const e = eclats[i];
            e.vie -= dt * 1.1;
            if (e.vie <= 0) { eclats.splice(i, 1); continue; }
            e.vx *= 0.97;
            e.vy = e.vy * 0.97 + GRAVITE * dt;
            e.x += e.vx * dt;
            e.y += e.vy * dt;
            ctx.fillStyle = `rgba(${e.couleur}, ${e.vie})`;
            ctx.beginPath();
            ctx.arc(e.x, e.y, e.taille * (0.5 + e.vie / 2), 0, Math.PI * 2);
            ctx.fill();
        }

        if (temps < DUREE_S || meteores.length || eclats.length || ondes.length) {
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

/** Flash au point d'impact puis anneau qui s'élargit en s'effaçant. */
function onde(ctx, o) {
    if (o.vie > 0.7) {
        const flash = ctx.createRadialGradient(o.x, o.y, 0, o.x, o.y, 60);
        flash.addColorStop(0, `rgba(255, 255, 255, ${(o.vie - 0.7) * 3})`);
        flash.addColorStop(1, `rgba(${o.couleur}, 0)`);
        ctx.fillStyle = flash;
        ctx.fillRect(o.x - 60, o.y - 60, 120, 120);
    }
    ctx.strokeStyle = `rgba(${o.couleur}, ${o.vie * 0.8})`;
    ctx.lineWidth = 3 * o.vie;
    ctx.beginPath();
    ctx.arc(o.x, o.y, o.rayon, 0, Math.PI * 2);
    ctx.stroke();
}

/** La page tremble un instant (l'animation passe devant le transform posé par page_transition.js). */
function secousse() {
    const main = document.querySelector('main');
    if (!main) return;
    const pas = [8, -7, 6, -4, 3, -1, 0];
    main.animate(pas.map((d, i) => ({ transform: `translate(${d}px, ${(i % 2 ? d : -d) * 0.7}px)` })), { duration: 380, easing: 'ease-out' });
}
