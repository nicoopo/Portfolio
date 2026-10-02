import * as THREE from 'three';
import { ImprovedNoise } from 'three/addons/math/ImprovedNoise.js';
import { glowPointsMaterial, glowSprite } from './textures.js';

const perlin = new ImprovedNoise();

/**
 * Hologramme du cerveau : contours lumineux des circonvolutions sur une peau de points
 * discrète, avec une aura bleue derrière.
 */
export function createBrain(texture) {
    const brain = new THREE.Points(brainGeometry(), glowPointsMaterial(texture, 0.026));
    const aura = glowSprite(texture, new THREE.Color('#3b4cff'), 3.2);
    aura.material.opacity = 0.07;
    brain.add(aura);
    return brain;
}

// ------------------------------------------------
// Forme
// ------------------------------------------------

const GAP = 0.05;    // demi-largeur de la scissure inter-hémisphérique
const LENGTH = 1.05; // demi-longueur avant-arrière d'un hémisphère
const TEMPORAL = { center: [0.4, -0.22, 0.1], radius: [0.24, 0.2, 0.45] }; // lobe temporal (côté +x)

const LINE_POINTS = 34000; // contours des gyri
const SKIN_POINTS = 7000;  // peau holographique entre les contours

/** Rayon d'un hémisphère dans la direction (dx, dy, dz). */
function hemisphereRadius(dx, dy, dz) {
    // Plus étroit à l'avant, pôle occipital un peu pincé
    const width = 0.62 * (1 - 0.15 * Math.max(dz, 0) ** 2 - 0.1 * Math.max(-dz - 0.6, 0));
    // Dessous du lobe frontal aplati (il repose sur les orbites)
    const down = 0.55 * (dz > 0 ? 1 - 0.35 * dz : 1);
    const rx = width, ry = dy < 0 ? down : 0.78, rz = LENGTH;
    return 1 / Math.sqrt((dx / rx) ** 2 + (dy / ry) ** 2 + (dz / rz) ** 2);
}

function insideHemisphere(x, y, z) {
    const hx = Math.abs(x) - GAP, hy = y - 0.1;
    const len = Math.hypot(hx, hy, z);
    return hx > 0 && len < hemisphereRadius(hx / len, hy / len, z / len) * 0.98;
}

function insideTemporal(x, y, z) {
    const [cx, cy, cz] = TEMPORAL.center, [rx, ry, rz] = TEMPORAL.radius;
    return ((Math.abs(x) - cx) / rx) ** 2 + ((y - cy) / ry) ** 2 + ((z - cz) / rz) ** 2 < 0.96;
}

/**
 * Sillons : les lignes où le bruit de Perlin (légèrement déformé) passe par zéro forment
 * des courbes sinueuses et fermées, comme les contours des gyri. Renvoie |bruit|.
 */
function sulcus(x, y, z) {
    const warp = 0.45 * perlin.noise(x * 1.8, y * 1.8, z * 1.8);
    return Math.abs(perlin.noise(x * 4.8 + warp, y * 4.8 + warp, z * 4.8 + warp));
}

/** Direction aléatoire uniforme. */
function randomDirection() {
    const u = Math.random() * 2 - 1;
    const phi = Math.random() * Math.PI * 2;
    const s = Math.sqrt(1 - u * u);
    return [s * Math.cos(phi), u, s * Math.sin(phi)];
}

/** Point au hasard sur la surface (hémisphère ou lobe temporal), ou null si recouvert. */
function surfacePoint() {
    const [dx, dy, dz] = randomDirection();
    const side = Math.random() < 0.5 ? -1 : 1;

    if (Math.random() < 0.85) {
        const r = hemisphereRadius(Math.abs(dx), dy, dz);
        const x = side * (Math.abs(dx) * r + GAP), y = dy * r + 0.1, z = dz * r;
        return insideTemporal(x, y, z) ? null : [x, y, z, side];
    }
    const [cx, cy, cz] = TEMPORAL.center, [rx, ry, rz] = TEMPORAL.radius;
    const x = side * (cx + dx * rx), y = cy + dy * ry, z = cz + dz * rz;
    return insideHemisphere(x, y, z) ? null : [x, y, z, side];
}

function brainGeometry() {
    const positions = [];
    const colors = [];
    const deep = new THREE.Color('#2a3cff');  // bleu électrique
    const light = new THREE.Color('#5fb4ff'); // contours bleu clair
    const violet = new THREE.Color('#7f5af0');
    const c = new THREE.Color();

    // Couleur de base, légèrement violette vers l'avant (comme le reste de la scène), × luminosité
    const push = (x, y, z, base, light) => {
        positions.push(x, y, z);
        c.copy(base).lerp(violet, THREE.MathUtils.clamp((z + 0.2) / 2.4, 0, 0.45)).multiplyScalar(light);
        colors.push(c.r, c.g, c.b);
    };

    // Contours des gyri : on garde les points de surface proches d'un sillon
    for (let lines = 0; lines < LINE_POINTS;) {
        const p = surfacePoint();
        if (!p) continue;
        const [x, y, z, side] = p;
        const n = sulcus(x + (side > 0 ? 0 : 7), y, z); // chaque hémisphère a ses propres plis
        if (n > 0.05) continue;
        // Les sillons sont des creux : on enfonce un peu la ligne ; son centre est plus lumineux
        push(x * 0.985, (y - 0.1) * 0.985 + 0.1, z * 0.985, light, 0.25 + 0.4 * (1 - n / 0.05));
        lines++;
    }

    // Peau : points épars et sombres entre les contours
    for (let skin = 0; skin < SKIN_POINTS;) {
        const p = surfacePoint();
        if (!p) continue;
        push(p[0], p[1], p[2], deep, 0.35 + 0.25 * Math.random());
        skin++;
    }

    // Cervelet : lamelles horizontales fines et serrées, en bas à l'arrière
    for (let i = 0; i < 6000;) {
        const [dx, dy, dz] = randomDirection();
        const folia = Math.abs(Math.sin(dy * 22 + perlin.noise(dx * 3, dy * 3, dz * 3) * 2));
        const onLine = folia < 0.25;
        if (!onLine && Math.random() > 0.08) continue;
        push(dx * 0.48, dy * 0.22 - 0.4, dz * 0.3 - 0.64, onLine ? light : deep, onLine ? 0.45 : 0.25);
        i++;
    }

    // Tronc cérébral : cylindre qui s'affine vers le bas
    for (let i = 0; i < 1500; i++) {
        const t = Math.random();
        const a = Math.random() * Math.PI * 2;
        const r = 0.13 - 0.04 * t;
        push(Math.cos(a) * r, -0.38 - t * 0.75, Math.sin(a) * r - 0.33 - 0.1 * t, light, 0.25);
    }

    const geometry = new THREE.BufferGeometry();
    geometry.setAttribute('position', new THREE.Float32BufferAttribute(positions, 3));
    geometry.setAttribute('color', new THREE.Float32BufferAttribute(colors, 3));
    return geometry;
}
