/*
 * Easter egg : des constellations cachées dans le ciel. Leurs étoiles brillent un peu plus
 * que les autres ; cliquées dans le bon ordre, elles se relient :
 * - en haut à droite, un « N » (Nico) : bas gauche → haut gauche → diagonale → haut droite
 * - en haut à gauche, le Lion : la faucille de la tête (ε → Régulus), puis le corps jusqu'à
 *   la queue (Dénébola), et retour sur Algieba pour refermer
 * Une mauvaise étoile efface tout. Coupé avec le réglage « Effets trou noir ».
 */
import { reducedMotion } from './black_hole.js';
import { preferences } from './preferences.js';
import { isEmptySpace } from './stars.js';
import { decouvrir } from './decouvertes.js';

const RAYON_CLIC = 20;       // tolérance autour d'une étoile (doigt compris)
const TRACE_S = 0.35;        // durée du tracé d'un trait
const VICTOIRE_S = 5;        // durée de la constellation complète avant de s'effacer

const CONSTELLATIONS = [
    {
        nom: 'n', largeur: 160, hauteur: 160,
        // Légèrement irrégulier pour faire naturel
        etoiles: [[22, 138], [18, 82], [24, 22], [78, 84], [138, 136], [142, 80], [136, 24]],
        parcours: [0, 1, 2, 3, 4, 5, 6],
    },
    {
        nom: 'lion', largeur: 220, hauteur: 120,
        // Positions réelles (ascension droite, déclinaison) projetées, la tête à droite comme dans le ciel
        etoiles: [
            [207, 30],  // 0 ε Leonis
            [196, 16],  // 1 μ Rasalas
            [159, 32],  // 2 ζ Adhafera
            [153, 55],  // 3 γ Algieba
            [174, 74],  // 4 η Leonis
            [172, 104], // 5 α Régulus
            [68, 83],   // 6 θ Chertan
            [13, 88],   // 7 β Dénébola
            [68, 51],   // 8 δ Zosma
        ],
        parcours: [0, 1, 2, 3, 4, 5, 6, 7, 8, 3],
    },
];

document.addEventListener('DOMContentLoaded', () => {
    const ciel = CONSTELLATIONS.map((def) => creer(def, (nom) => decouvrir(`constellation-${nom}`)));

    document.addEventListener('pointerdown', (e) => {
        if (!preferences.transitions || !isEmptySpace(e.target)) return;
        ciel.forEach((constellation) => constellation.clic(e.clientX, e.clientY));
    });
});

function creer({ nom, largeur, hauteur, etoiles, parcours }, trouvee) {
    const canvas = Object.assign(document.createElement('canvas'), { className: `constellation constellation-${nom}` });
    canvas.setAttribute('aria-hidden', 'true');
    document.body.appendChild(canvas);
    const ratio = Math.min(window.devicePixelRatio || 1, 2);
    canvas.width = largeur * ratio;
    canvas.height = hauteur * ratio;
    const ctx = canvas.getContext('2d');
    ctx.scale(ratio, ratio);

    let reliees = 0;       // nombre d'étapes du parcours franchies
    let traceDepuis = 0;   // début du tracé du dernier trait
    let victoire = null;   // moment où la constellation a été complétée
    let eclats = [];
    let temps = 0;

    const recommencer = () => { reliees = 0; victoire = null; eclats = []; };

    const gagner = () => {
        victoire = temps;
        eclats = etoiles.flatMap(([x, y]) => Array.from({ length: 8 }, () => {
            const angle = Math.random() * Math.PI * 2, vitesse = 20 + Math.random() * 50;
            return { x, y, vx: Math.cos(angle) * vitesse, vy: Math.sin(angle) * vitesse, vie: 1 };
        }));
        trouvee(nom);
    };

    const clic = (clientX, clientY) => {
        if (victoire !== null) return;
        const box = canvas.getBoundingClientRect();
        const x = clientX - box.left, y = clientY - box.top;
        // L'étoile la plus proche (certaines sont voisines)
        let touchee = -1, meilleure = RAYON_CLIC;
        etoiles.forEach(([ex, ey], i) => {
            const d = Math.hypot(ex - x, ey - y);
            if (d < meilleure) { meilleure = d; touchee = i; }
        });
        if (touchee === -1) return; // clic ailleurs : on garde la progression
        if (touchee !== parcours[reliees]) { recommencer(); if (touchee !== parcours[0]) return; }
        reliees++;
        traceDepuis = temps;
        if (reliees === parcours.length) gagner();
    };

    let avant = performance.now();
    const image = (maintenant) => {
        requestAnimationFrame(image);
        const dt = Math.min((maintenant - avant) / 1000, 0.05);
        avant = maintenant;
        temps += dt;
        ctx.clearRect(0, 0, largeur, hauteur);
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
            const [x1, y1] = etoiles[parcours[i - 1]], [x2, y2] = etoiles[parcours[i]];
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

        const allumees = new Set(parcours.slice(0, reliees));
        const prochaine = reliees > 0 ? parcours[reliees] : -1; // l'étoile suivante scintille un peu plus
        etoiles.forEach(([x, y], i) => {
            const allumee = allumees.has(i);
            const pulse = calme ? 0.5 : 0.5 + 0.5 * Math.sin(temps * (i === prochaine ? 5 : 1.6) + i * 1.3);
            const r = (allumee ? 2.6 : 1.7 + (i === prochaine ? 0.6 : 0.3) * pulse) * (victoire !== null ? 1.3 : 1);
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

    return { clic };
}
