import * as THREE from 'three';
import { ImprovedNoise } from 'three/addons/math/ImprovedNoise.js';
import { glowPointsMaterial } from './textures.js';

const perlin = new ImprovedNoise();

/** Hologramme du cerveau : nuage de points cyan → violet. */
export function createBrain(texture, count = 22000) {
    return new THREE.Points(brainGeometry(count), glowPointsMaterial(texture, 0.035));
}

// ------------------------------------------------
// Forme
// ------------------------------------------------

const GAP = 0.06; // demi-largeur de la scissure inter-hémisphérique
const TEMPORAL = { center: [0.4, -0.22, 0.12], radius: [0.24, 0.2, 0.5] }; // lobe temporal (côté +x)

/** Rayon d'un hémisphère dans la direction (dx, dy, dz), sans les plis. */
function hemisphereRadius(dx, dy, dz) {
    // Plus étroit à l'avant, pôle occipital un peu pincé
    const width = 0.62 * (1 - 0.15 * Math.max(dz, 0) ** 2 - 0.1 * Math.max(-dz - 0.6, 0));
    // Dessous du lobe frontal aplati (il repose sur les orbites)
    const down = 0.55 * (dz > 0 ? 1 - 0.35 * dz : 1);
    const rx = width, ry = dy < 0 ? down : 0.8, rz = 1.2;
    return 1 / Math.sqrt((dx / rx) ** 2 + (dy / ry) ** 2 + (dz / rz) ** 2);
}

/** Le point (x, y, z) est-il à l'intérieur d'un hémisphère (plis compris en moyenne) ? */
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
 * Circonvolutions : bruit de Perlin « en crêtes » (1 - |bruit|), légèrement déformé
 * pour que les plis serpentent. 1 = sommet d'un gyrus, 0 = fond d'un sillon.
 */
function gyrus(x, y, z) {
    const warp = 0.5 * perlin.noise(x * 2, y * 2, z * 2);
    return 1 - Math.abs(perlin.noise(x * 4.5 + warp, y * 4.5 + warp, z * 4.5 + warp));
}

/**
 * Forme de cerveau procédurale : hémisphères plissés, lobes temporaux séparés par la
 * scissure latérale, cervelet strié, tronc cérébral.
 * Les points se concentrent sur les crêtes des gyri : les sillons apparaissent en creux.
 */
function brainGeometry(count) {
    const positions = [];
    const colors = [];
    const cyan = new THREE.Color('#00d4ff');
    const violet = new THREE.Color('#7f5af0');
    const c = new THREE.Color();

    const push = (x, y, z, light) => {
        positions.push(x, y, z);
        // Dégradé arrière (cyan) → avant (violet), plus lumineux sur les crêtes
        c.copy(cyan).lerp(violet, THREE.MathUtils.clamp((z + 1.3) / 2.6, 0, 1)).multiplyScalar(light);
        colors.push(c.r, c.g, c.b);
    };

    while (positions.length < count * 3) {
        // Direction aléatoire uniforme
        const u = Math.random() * 2 - 1;
        const phi = Math.random() * Math.PI * 2;
        const s = Math.sqrt(1 - u * u);
        const dx = s * Math.cos(phi), dy = u, dz = s * Math.sin(phi);
        const side = Math.random() < 0.5 ? -1 : 1;
        const roll = Math.random();

        if (roll < 0.06) {
            // Volume intérieur : quelques points diffus
            const r = Math.cbrt(Math.random()) * 0.85 * hemisphereRadius(Math.abs(dx), dy, dz);
            push(side * (Math.abs(dx) * r + GAP), dy * r + 0.1, dz * r, 0.35);
        } else if (roll < 0.8) {
            // Hémisphères
            let r = hemisphereRadius(Math.abs(dx), dy, dz);
            const x0 = Math.abs(dx) * r, y0 = dy * r, z0 = dz * r;
            const g = gyrus(x0 + (side > 0 ? 0 : 7), y0, z0); // l'autre hémisphère a ses propres plis
            if (Math.random() > 0.08 + 0.92 * g ** 4) continue;
            r *= 1 + 0.05 * (g - 0.6);
            const x = side * (Math.abs(dx) * r + GAP), y = dy * r + 0.1, z = dz * r;
            if (insideTemporal(x, y, z)) continue; // recouvert par le lobe temporal
            push(x, y, z, 0.3 + 0.7 * g);
        } else if (roll < 0.9) {
            // Lobes temporaux : bombés sur les côtés, sous la scissure latérale
            const [cx, cy, cz] = TEMPORAL.center, [rx, ry, rz] = TEMPORAL.radius;
            const g = gyrus(dx * rx + 3, dy * ry, dz * rz);
            if (Math.random() > 0.08 + 0.92 * g ** 4) continue;
            const k = 1 + 0.06 * (g - 0.6);
            const x = side * (cx + dx * rx * k), y = cy + dy * ry * k, z = cz + dz * rz * k;
            if (insideHemisphere(x, y, z)) continue;
            push(x, y, z, 0.3 + 0.7 * g);
        } else if (roll < 0.97) {
            // Cervelet : petit ellipsoïde strié de lamelles horizontales, en bas à l'arrière
            const folia = 0.5 + 0.5 * Math.sin(dy * 45 + perlin.noise(dx * 3, dy * 3, dz * 3) * 3);
            if (Math.random() > 0.3 + 0.7 * folia) continue;
            const k = 1 + 0.03 * folia;
            push(dx * 0.55 * k, dy * 0.28 * k - 0.5, dz * 0.35 * k - 0.85, 0.4 + 0.5 * folia);
        } else {
            // Tronc cérébral : cylindre qui descend en s'affinant
            const t = Math.random();
            const a = Math.random() * Math.PI * 2;
            const r = 0.14 - 0.04 * t;
            push(Math.cos(a) * r, -0.4 - t * 0.75, Math.sin(a) * r - 0.4 - 0.1 * t, 0.6);
        }
    }

    const geometry = new THREE.BufferGeometry();
    geometry.setAttribute('position', new THREE.Float32BufferAttribute(positions, 3));
    geometry.setAttribute('color', new THREE.Float32BufferAttribute(colors, 3));
    return geometry;
}
