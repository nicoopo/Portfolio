import * as THREE from 'three';
import { ImprovedNoise } from 'three/addons/math/ImprovedNoise.js';
import { glowPointsMaterial, glowSprite } from './textures.js';

const perlin = new ImprovedNoise();

/**
 * Hologramme du cerveau : nuage de points en relief (gyri bombés, sillons creusés),
 * éclairé, avec quelques points à l'intérieur et une aura bleue derrière.
 */
export function createBrain(texture) {
    const brain = new THREE.Points(brainGeometry(), frontFacingMaterial(glowPointsMaterial(texture, 0.028)));
    const aura = glowSprite(texture, new THREE.Color('#3b4cff'), 3.2);
    aura.material.opacity = 0.07;
    brain.add(aura);
    return brain;
}

/**
 * Atténue les points de surface tournés dos à la caméra (attribut `facing` = normale ;
 * normale nulle = point intérieur, toujours visible). Sans ça, les plis de la face arrière
 * se superposent à ceux de l'avant et brouillent le relief.
 */
function frontFacingMaterial(material) {
    material.onBeforeCompile = (shader) => {
        shader.vertexShader = shader.vertexShader
            .replace('#include <common>', '#include <common>\nattribute vec3 facing;\nvarying float vFront;')
            .replace('#include <fog_vertex>', `#include <fog_vertex>
                vFront = dot(facing, facing) < 0.25 ? 1.0
                    : smoothstep(-0.15, 0.35, dot(normalize(normalMatrix * facing), normalize(-mvPosition.xyz)));`);
        shader.fragmentShader = shader.fragmentShader
            .replace('#include <common>', '#include <common>\nvarying float vFront;')
            .replace('#include <opaque_fragment>', 'diffuseColor.a *= 0.12 + 0.88 * vFront;\n#include <opaque_fragment>');
    };
    return material;
}

// ------------------------------------------------
// Forme
// ------------------------------------------------

const GAP = 0.05;    // demi-largeur de la scissure inter-hémisphérique
const LENGTH = 1.05; // demi-longueur avant-arrière d'un hémisphère
const TEMPORAL = { center: [0.4, -0.22, 0.1], radius: [0.24, 0.2, 0.45] }; // lobe temporal (côté +x)

const SURFACE_POINTS = 45000;
const INSIDE_POINTS = 4000;
const SULCUS_DEPTH = 0.07; // profondeur des sillons, en fraction du rayon
const LIGHT = new THREE.Vector3(0.3, 0.8, 0.5).normalize(); // lumière venant d'en haut, à l'avant
const ZERO = new THREE.Vector3();

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
    return Math.abs(perlin.noise(x * 5.5 + warp, y * 5.5 + warp, z * 5.5 + warp));
}

/** Direction aléatoire uniforme. */
function randomDirection() {
    const u = Math.random() * 2 - 1;
    const phi = Math.random() * Math.PI * 2;
    const s = Math.sqrt(1 - u * u);
    return [s * Math.cos(phi), u, s * Math.sin(phi)];
}

/**
 * Point au hasard sur la surface (hémisphère ou lobe temporal), avec le centre de son lobe
 * (pour creuser les sillons vers l'intérieur), ou null si le point est recouvert.
 */
function surfacePoint() {
    const [dx, dy, dz] = randomDirection();
    const side = Math.random() < 0.5 ? -1 : 1;

    if (Math.random() < 0.85) {
        const r = hemisphereRadius(Math.abs(dx), dy, dz);
        const x = side * (Math.abs(dx) * r + GAP), y = dy * r + 0.1, z = dz * r;
        return insideTemporal(x, y, z) ? null : { x, y, z, side, center: [side * GAP, 0.1, 0] };
    }
    const [cx, cy, cz] = TEMPORAL.center, [rx, ry, rz] = TEMPORAL.radius;
    const x = side * (cx + dx * rx), y = cy + dy * ry, z = cz + dz * rz;
    return insideHemisphere(x, y, z) ? null : { x, y, z, side, center: [side * cx, cy, cz] };
}

function brainGeometry() {
    const positions = [];
    const colors = [];
    const normals = []; // pour frontFacingMaterial ; (0, 0, 0) = intérieur
    const deep = new THREE.Color('#2a3cff');  // bleu électrique
    const light = new THREE.Color('#7cc4ff'); // crêtes bleu clair
    const violet = new THREE.Color('#7f5af0');
    const c = new THREE.Color(), c2 = new THREE.Color();

    // Couleur de base, légèrement violette vers l'avant (comme le reste de la scène), × luminosité
    const push = (x, y, z, base, light, normal = ZERO) => {
        positions.push(x, y, z);
        normals.push(normal.x, normal.y, normal.z);
        c.copy(base).lerp(violet, THREE.MathUtils.clamp((z + 0.2) / 2.4, 0, 0.45)).multiplyScalar(light);
        colors.push(c.r, c.g, c.b);
    };

    // Cortex : des points partout, en relief. Les sillons (là où le bruit passe par zéro)
    // sont des creux étroits ; les gyri entre eux, des bourrelets arrondis plus clairs.
    const normal = new THREE.Vector3();
    for (let i = 0; i < SURFACE_POINTS;) {
        const p = surfacePoint();
        if (!p) continue;
        const n = sulcus(p.x + (p.side > 0 ? 0 : 7), p.y, p.z); // chaque hémisphère a ses propres plis
        const h = THREE.MathUtils.smoothstep(n, 0, 0.3);           // 0 au fond du sillon, 1 au sommet du gyrus
        if (Math.random() > 0.1 + 0.9 * h) continue;                // le fond des sillons est presque vide

        // On enfonce le point vers le centre du lobe selon la profondeur du sillon
        const [cx, cy, cz] = p.center;
        const k = 1 - SULCUS_DEPTH * (1 - h);
        const x = cx + (p.x - cx) * k, y = cy + (p.y - cy) * k, z = cz + (p.z - cz) * k;

        // Éclairage : face tournée vers la lumière plus claire, crête du gyrus plus claire,
        // et un liseré lumineux au bord des sillons
        const lambert = 0.45 + 0.55 * Math.max(normal.set(p.x - cx, p.y - cy, p.z - cz).normalize().dot(LIGHT), 0);
        const lip = n > 0.02 && n < 0.05 ? 0.25 : 0;
        push(x, y, z, c2.copy(deep).lerp(light, 0.15 + 0.75 * h), lambert * (0.06 + 0.94 * h ** 1.5) + lip, normal);
        i++;
    }

    // Intérieur : quelques points diffus
    for (let i = 0; i < INSIDE_POINTS; i++) {
        const [dx, dy, dz] = randomDirection();
        const side = Math.random() < 0.5 ? -1 : 1;
        const r = Math.cbrt(Math.random()) * 0.85 * hemisphereRadius(Math.abs(dx), dy, dz);
        push(side * (Math.abs(dx) * r + GAP), dy * r + 0.1, dz * r, deep, 0.25);
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
    geometry.setAttribute('facing', new THREE.Float32BufferAttribute(normals, 3));
    return geometry;
}
