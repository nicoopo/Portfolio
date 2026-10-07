import * as THREE from 'three';
import { ImprovedNoise } from 'three/addons/math/ImprovedNoise.js';
import { dotTexture, glowPointsMaterial, glowSprite } from './textures.js';

const perlin = new ImprovedNoise();
export const BODY_OPACITY = 0.7;

/**
 * Hologramme du cerveau : nuage de points en relief (gyri bombés, séparés par des sillons
 * sombres), avec quelques points à l'intérieur et une aura bleue derrière.
 */
export function createBrain(texture) {
    // Créé avant les points : à position égale, Three.js dessine d'abord l'objet créé en premier
    const body = createBody();
    // L'écran (createBody) est en retrait de la surface : il ne cache que les points qui passent
    // derrière (cervelet derrière le lobe temporal, face arrière, intérieur)
    // Point au cœur plein et au bord court (le point flou commun donnerait une surface granuleuse)
    const dot = dotTexture([[0, 1], [0.3, 0.9], [0.55, 0.3], [1, 0]]);
    const brain = new THREE.Points(brainGeometry(), frontFacingMaterial(glowPointsMaterial(dot, 0.028)));
    const aura = glowSprite(texture, new THREE.Color('#3b4cff'), 3.2);
    aura.material.opacity = 0.07;
    brain.add(body, aura);
    brain.userData.bodyMaterial = body.children[0].material; // estompé quand on plonge dedans (brain_controller)
    return brain;
}

/**
 * Sous les points, deux couches de la forme lisse du cerveau (un peu en retrait de la surface) :
 * - un corps sombre semi-transparent : fond des sillons, contraste des plis ;
 * - un écran invisible qui n'écrit que la profondeur (passe opaque, donc avant tout le reste) :
 *   il cache ce qui passe derrière le cerveau (lumière des nébuleuses, étoiles), qui sinon
 *   le traverse en halo. Neurones, synapses et souvenirs, dedans, sont dessinés sans test de
 *   profondeur (cf. brain_controller). Vu de l'intérieur, ses faces sont tournées
 *   vers l'extérieur : il disparaît.
 */
function createBody() {
    const material = new THREE.MeshBasicMaterial({ color: '#03040d', transparent: true, opacity: BODY_OPACITY, depthWrite: false, depthTest: false });
    // S'estompe vers sa silhouette (surface vue en biais) : sinon son bord se dessine en trait sombre
    material.onBeforeCompile = (shader) => {
        shader.vertexShader = shader.vertexShader
            .replace('#include <common>', '#include <common>\nvarying float vFacing;')
            .replace('#include <fog_vertex>', '#include <fog_vertex>\nvFacing = abs(dot(normalize(normalMatrix * normal), normalize(-mvPosition.xyz)));');
        shader.fragmentShader = shader.fragmentShader
            .replace('#include <common>', '#include <common>\nvarying float vFacing;')
            .replace('#include <opaque_fragment>', 'diffuseColor.a *= smoothstep(0.05, 0.5, vFacing);\n#include <opaque_fragment>');
    };
    const screen = new THREE.MeshBasicMaterial({ colorWrite: false });

    const body = new THREE.Group();
    const smooth = (geometry, place, materials) => {
        const position = geometry.attributes.position;
        for (let i = 0; i < position.count; i++) position.setXYZ(i, ...place(position.getX(i), position.getY(i), position.getZ(i)));
        geometry.computeVertexNormals();
        geometry.computeBoundingSphere();
        for (const m of materials) body.add(new THREE.Mesh(geometry, m));
    };
    for (const side of [1, -1]) {
        smooth(new THREE.SphereGeometry(1, 64, 40), (dx, dy, dz) => {
            const r = 0.8 * cortexRadius(dx, dy, dz);
            return [side * (Math.max(dx * r, 0) + GAP), dy * r + 0.1, dz * r];
        }, [material, screen]);
    }
    // Cervelet : écran seulement (deux corps sombres qui se chevauchent dessineraient un contour)
    smooth(new THREE.SphereGeometry(1, 48, 32), (dx, dy, dz) => cerebellumSurface(dx, dy, dz, 0.82), [screen]);
    return body;
}

/**
 * Atténue les points de surface tournés dos à la caméra (attribut `facing` = normale ;
 * normale nulle = point intérieur, toujours visible). Sans ça, les plis de la face arrière
 * se superposent à ceux de l'avant et brouillent le relief.
 * Taille propre à chaque point (attribut `pointScale`) : gros sur les crêtes, fins vers les sillons.
 */
function frontFacingMaterial(material) {
    material.onBeforeCompile = (shader) => {
        shader.vertexShader = shader.vertexShader
            .replace('#include <common>', '#include <common>\nattribute vec3 facing;\nattribute float pointScale;\nvarying float vFront;')
            .replace('gl_PointSize = size;', 'gl_PointSize = size * pointScale;')
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
const CEREBELLUM = { center: [0, -0.36, -0.62], radius: [0.44, 0.21, 0.25] };
const HEMISPHERE_ORIGIN = [GAP, 0.1, 0];

const HEMISPHERE_DIRECTIONS = 110000; // moitié utilisée par hémisphère
const CEREBELLUM_DIRECTIONS = 24000;
const INSIDE_POINTS = 4000;
const SULCUS_DEPTH = 0.08; // profondeur des sillons, en fraction du rayon
const SULCUS_GAP = 0.3;    // sous cette hauteur de relief, pas de point : le sillon reste une ligne sombre
const LIGHT = new THREE.Vector3(0.3, 0.8, 0.5).normalize(); // lumière venant d'en haut, à l'avant
const ZERO = new THREE.Vector3();
const DEEP = new THREE.Color('#2238ff');  // bleu électrique
const CREST = new THREE.Color('#6fb4ff'); // crêtes bleu clair

/** Rayon d'un hémisphère dans la direction (dx, dy, dz). */
function hemisphereRadius(dx, dy, dz) {
    // Plus étroit à l'avant, pôle occipital un peu pincé
    const width = 0.62 * (1 - 0.15 * Math.max(dz, 0) ** 2 - 0.1 * Math.max(-dz - 0.6, 0));
    // Dessous du lobe frontal aplati (il repose sur les orbites)
    const down = 0.55 * (dz > 0 ? 1 - 0.35 * dz : 1);
    const rx = width, ry = dy < 0 ? down : 0.78, rz = LENGTH;
    return 1 / Math.sqrt((dx / rx) ** 2 + (dy / ry) ** 2 + (dz / rz) ** 2);
}

/** Distance, depuis o dans la direction u, de la sortie de l'ellipsoïde (center, radius) ; 0 si raté. */
function ellipsoidExit(o, u, center, radius) {
    let a = 0, b = 0, c = -1;
    for (let i = 0; i < 3; i++) {
        const d = o[i] - center[i], r2 = radius[i] ** 2;
        a += u[i] ** 2 / r2;
        b += 2 * u[i] * d / r2;
        c += d * d / r2;
    }
    const disc = b * b - 4 * a * c;
    return disc > 0 ? (-b + Math.sqrt(disc)) / (2 * a) : 0;
}

/** Rayon du cortex lisse (hémisphère réuni au lobe temporal) depuis le centre de l'hémisphère. */
function cortexRadius(dx, dy, dz) {
    return Math.max(hemisphereRadius(dx, dy, dz), ellipsoidExit(HEMISPHERE_ORIGIN, [dx, dy, dz], TEMPORAL.center, TEMPORAL.radius));
}

/**
 * Scissure latérale (de Sylvius) : creux là où le lobe temporal rejoint le reste de
 * l'hémisphère. 0 au fond, 1 loin de la jonction.
 */
function lateralFissure(dx, dy, dz) {
    const gap = ellipsoidExit(HEMISPHERE_ORIGIN, [dx, dy, dz], TEMPORAL.center, TEMPORAL.radius) - hemisphereRadius(dx, dy, dz);
    return THREE.MathUtils.smoothstep(Math.abs(gap), 0, 0.05);
}

/**
 * Sillons : les lignes où le bruit de Perlin (légèrement déformé) passe par zéro forment
 * des courbes sinueuses et fermées, comme les contours des gyri. Renvoie |bruit|.
 */
function sulcus(x, y, z) {
    const warp = 0.45 * perlin.noise(x * 1.8, y * 1.8, z * 1.8);
    return Math.abs(perlin.noise(x * 6.5 + warp, y * 6.5 + warp, z * 6.5 + warp));
}

/**
 * Hauteur du relief en (x, y, z) : 0 au fond d'un sillon, 1 au sommet d'un gyrus.
 * Profil en arc (sinus) : gyri bombés et arrondis, sillons étroits.
 */
function relief(x, y, z, side) {
    const n = sulcus(x + (side > 0 ? 0 : 7), y, z); // plis propres à chaque hémisphère
    return Math.sin(Math.min(n / 0.28, 1) * Math.PI / 2);
}

/** Point du cortex dans la direction (dx > 0, dy, dz), enfoncé selon le sillon : [x, y, z, h]. */
function cortexPoint(dx, dy, dz, side) {
    let r = cortexRadius(dx, dy, dz);
    const h = Math.min(relief(side * (dx * r + GAP), dy * r + 0.1, dz * r, side), lateralFissure(dx, dy, dz));
    r *= 1 - SULCUS_DEPTH * (1 - h);
    return [side * (dx * r + GAP), dy * r + 0.1, dz * r, h];
}

/**
 * Cervelet : une seule masse large et aplatie, glissée sous l'arrière du cerveau, derrière
 * le tronc ; dessus plat (contre le cerveau), dessous bombé, légère encoche médiane à
 * l'arrière et en dessous (vermis). Point de sa surface dans la direction (dx, dy, dz), × k.
 */
function cerebellumSurface(dx, dy, dz, k = 1) {
    const [cx, cy, cz] = CEREBELLUM.center, [rx, ry, rz] = CEREBELLUM.radius;
    const notch = 1 - 0.1 * Math.exp(-((dx / 0.12) ** 2)) * Math.max(-dz, -dy, 0);
    const r = k * notch;
    return [cx + dx * rx * r, cy + dy * ry * (dy > 0 ? 0.6 : 1) * r, cz + dz * rz * r];
}

/**
 * Lamelles (folia) fines en éventail depuis l'attache au tronc (axe gauche-droite à l'avant du
 * cervelet, caché) : arcs horizontaux empilés vus de dos, éventail de profil, courbes vues de
 * dessous. Renvoie [x, y, z, h], le point enfoncé dans les creux entre lamelles.
 */
function cerebellumPoint(dx, dy, dz) {
    const [, cy, cz] = CEREBELLUM.center, rz = CEREBELLUM.radius[2];
    const [x, y, z] = cerebellumSurface(dx, dy, dz);
    const angle = Math.atan2(y - (cy + 0.03), (cz + 0.75 * rz) - z);
    const folia = Math.abs(Math.sin(angle * 40 + 0.6 * perlin.noise(x * 6, y * 6, z * 6)));
    const h = Math.sin(Math.min(folia / 0.6, 1) * Math.PI / 2);
    return [...cerebellumSurface(dx, dy, dz, 1 - 0.05 * (1 - h)), h];
}

/**
 * Tronc cérébral puis moelle, t de 0 (haut) à 1 (bas), angle a (sin a > 0 = vers l'avant) :
 * mésencéphale, pont très bombé à l'avant (strié en travers), bulbe avec ses deux olives,
 * puis moelle fine et régulière ; fissures médianes avant et arrière tout du long.
 */
const bump = (v, center, width) => Math.exp(-(((v - center) / width) ** 2));
function brainstemPoint(t, a) {
    const front = Math.max(Math.sin(a), 0);
    const pons = bump(t, 0.17, 0.09);
    const olives = bump(t, 0.38, 0.06) * (bump(a, Math.PI / 2 - 0.75, 0.3) + bump(a, Math.PI / 2 + 0.75, 0.3));
    const core = t < 0.5 ? 0.12 - 0.1 * t : 0.07 - 0.015 * (t - 0.5);
    const r = core + 0.08 * front * pons + 0.022 * olives;
    const fissure = Math.sin(Math.min(Math.abs(Math.cos(a)) / 0.2, 1) * Math.PI / 2);
    const fibers = 1 - 0.35 * pons * front * (1 - Math.abs(Math.sin(t * 140))); // stries du pont
    return [Math.cos(a) * r * (1 - 0.08 * (1 - fissure)), -0.36 - t * 0.85, Math.sin(a) * r - 0.2 - 0.08 * t, fissure * fibers];
}

// ------------------------------------------------
// Points
// ------------------------------------------------

/** Direction aléatoire uniforme. */
function randomDirection() {
    const u = Math.random() * 2 - 1;
    const phi = Math.random() * Math.PI * 2;
    const s = Math.sqrt(1 - u * u);
    return [s * Math.cos(phi), u, s * Math.sin(phi)];
}

/**
 * Directions réparties régulièrement sur la sphère (spirale de Fibonacci), un peu perturbées :
 * pas d'amas ni de trous comme avec un tirage au hasard, le relief se lit mieux.
 */
function evenDirections(count) {
    const golden = Math.PI * (3 - Math.sqrt(5));
    const jitter = 0.6 * Math.sqrt(4 * Math.PI / count); // assez pour casser la trame de la spirale
    const directions = [];
    for (let i = 0; i < count; i++) {
        const y = 1 - (2 * (i + 0.5)) / count;
        const s = Math.sqrt(1 - y * y);
        const v = new THREE.Vector3(Math.cos(i * golden) * s, y, Math.sin(i * golden) * s);
        v.x += (Math.random() - 0.5) * jitter;
        v.y += (Math.random() - 0.5) * jitter;
        v.z += (Math.random() - 0.5) * jitter;
        directions.push(v.normalize());
    }
    return directions;
}

function brainGeometry() {
    const positions = [];
    const colors = [];
    const normals = []; // pour frontFacingMaterial ; (0, 0, 0) = intérieur
    const violet = new THREE.Color('#7f5af0');
    const c = new THREE.Color(), c2 = new THREE.Color();

    // Couleur de base, légèrement violette vers l'avant (comme le reste de la scène), × luminosité
    const scales = [];
    let size = 1; // taille du prochain point (pushRelief la règle selon la hauteur du relief)
    const push = (x, y, z, base, light, normal = ZERO) => {
        scales.push(size);
        size = 1;
        positions.push(x, y, z);
        normals.push(normal.x, normal.y, normal.z);
        c.copy(base).lerp(violet, THREE.MathUtils.clamp((z + 0.2) / 2.4, 0, 0.45)).multiplyScalar(light);
        colors.push(c.r, c.g, c.b);
    };
    // Point de surface en relief (h = hauteur, ahead = hauteur un peu plus loin vers la lumière) :
    // crêtes claires, flanc tourné vers la lumière plus clair, flanc opposé dans l'ombre.
    // Rien au fond des sillons : ils restent des lignes sombres entre les gyri.
    const normal = new THREE.Vector3();
    const pushRelief = ([x, y, z, h], ahead, center, gain = 1, shade = 0.35) => {
        if (h < SULCUS_GAP) return;
        const g = (h - SULCUS_GAP) / (1 - SULCUS_GAP); // 0 au bord du sillon, 1 sur la crête
        size = 0.5 + 0.5 * g;
        normal.set(x - center[0], y - center[1], z - center[2]).normalize();
        const lambert = 1 - shade + shade * Math.max(normal.dot(LIGHT), 0); // shade = part de l'ombre
        const slope = THREE.MathUtils.clamp((ahead - h) * 6, -1, 1);
        const brightness = gain * lambert * (0.15 + 0.85 * g ** 1.5) * (1 - 0.45 * slope);
        push(x, y, z, c2.copy(DEEP).lerp(CREST, 0.6 * g), Math.max(brightness, 0.02), normal);
    };
    const towardLight = (v, d) => v.clone().addScaledVector(LIGHT, d).normalize();

    // Cortex, sur les deux hémisphères (face interne cachée contre l'autre hémisphère)
    for (const d of evenDirections(HEMISPHERE_DIRECTIONS)) {
        if (d.x <= 0) continue;
        const a = towardLight(d, 0.02);
        for (const side of [1, -1]) {
            pushRelief(cortexPoint(d.x, d.y, d.z, side), cortexPoint(a.x, a.y, a.z, side)[3], [side * GAP, 0.1, 0]);
        }
    }

    // Intérieur : quelques points diffus
    for (let i = 0; i < INSIDE_POINTS; i++) {
        const [dx, dy, dz] = randomDirection();
        const side = Math.random() < 0.5 ? -1 : 1;
        const r = Math.cbrt(Math.random()) * 0.85 * hemisphereRadius(Math.abs(dx), dy, dz);
        push(side * (Math.abs(dx) * r + GAP), dy * r + 0.1, dz * r, DEEP, 0.2);
    }

    // Cervelet : lamelles fines séparées par des lignes sombres, un peu plus discret que le cortex,
    // ombre marquée pour lire son volume
    for (const d of evenDirections(CEREBELLUM_DIRECTIONS)) {
        const a = towardLight(d, 0.03);
        pushRelief(cerebellumPoint(d.x, d.y, d.z), cerebellumPoint(a.x, a.y, a.z)[3], CEREBELLUM.center, 0.6, 0.7);
    }

    // Tronc cérébral puis moelle : anneaux réguliers, discrets, qui s'effacent au bout
    const RINGS = 120, AROUND = 48;
    for (let i = 0; i < RINGS; i++) {
        for (let j = 0; j < AROUND; j++) {
            const t = (i + Math.random() * 0.5) / RINGS;
            const a = ((j + Math.random() * 0.5) / AROUND) * Math.PI * 2;
            const p = brainstemPoint(t, a);
            const fade = 1 - THREE.MathUtils.smoothstep(t, 0.75, 1);
            pushRelief(p, p[3], [0, p[1], -0.2 - 0.08 * t], 0.55 * fade);
        }
    }

    const geometry = new THREE.BufferGeometry();
    geometry.setAttribute('position', new THREE.Float32BufferAttribute(positions, 3));
    geometry.setAttribute('color', new THREE.Float32BufferAttribute(colors, 3));
    geometry.setAttribute('facing', new THREE.Float32BufferAttribute(normals, 3));
    geometry.setAttribute('pointScale', new THREE.Float32BufferAttribute(scales, 1));
    return geometry;
}
