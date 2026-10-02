import * as THREE from 'three';
import { ImprovedNoise } from 'three/addons/math/ImprovedNoise.js';
import { glowPointsMaterial, glowSprite } from './textures.js';

const perlin = new ImprovedNoise();

/**
 * Hologramme du cerveau : nuage de points en relief (gyri bombés, sillons creusés),
 * éclairé, avec quelques points à l'intérieur et une aura bleue derrière.
 */
export function createBrain(texture) {
    // Créé avant les points : à position égale, Three.js dessine d'abord l'objet créé en premier
    const body = createBody();
    const brain = new THREE.Points(brainGeometry(), frontFacingMaterial(glowPointsMaterial(texture, 0.034)));
    const aura = glowSprite(texture, new THREE.Color('#3b4cff'), 3.2);
    aura.material.opacity = 0.07;
    brain.add(body, aura);
    return brain;
}

/**
 * Corps sombre et semi-transparent sous les points : il masque en partie ce qui est derrière
 * le cerveau (nébuleuses), qui sinon se voit à travers et casse l'impression de volume.
 * Les neurones, synapses et souvenirs sont dessinés après lui (renderOrder, brain_controller).
 */
function createBody() {
    const material = new THREE.MeshBasicMaterial({ color: '#03040d', transparent: true, opacity: 0.55, depthWrite: false });
    // S'estompe vers sa silhouette (surface vue en biais) : sinon son bord se dessine en trait sombre
    material.onBeforeCompile = (shader) => {
        shader.vertexShader = shader.vertexShader
            .replace('#include <common>', '#include <common>\nvarying float vFacing;')
            .replace('#include <fog_vertex>', '#include <fog_vertex>\nvFacing = abs(dot(normalize(normalMatrix * normal), normalize(-mvPosition.xyz)));');
        shader.fragmentShader = shader.fragmentShader
            .replace('#include <common>', '#include <common>\nvarying float vFacing;')
            .replace('#include <opaque_fragment>', 'diffuseColor.a *= smoothstep(0.05, 0.5, vFacing);\n#include <opaque_fragment>');
    };
    const body = new THREE.Group();
    const ellipsoid = (mapVertex) => {
        const geometry = new THREE.SphereGeometry(1, 64, 40);
        const position = geometry.attributes.position;
        for (let i = 0; i < position.count; i++) {
            position.setXYZ(i, ...mapVertex(position.getX(i), position.getY(i), position.getZ(i)));
        }
        geometry.computeVertexNormals();
        geometry.computeBoundingSphere();
        body.add(new THREE.Mesh(geometry, material));
    };
    // Seulement les hémisphères : là où deux corps se chevauchent (lobe temporal, cervelet),
    // la double couche sombre dessine un contour visible
    for (const side of [1, -1]) {
        // Dôme : la moitié intérieure de la sphère se replie sur l'extérieure
        ellipsoid((dx, dy, dz) => {
            const r = 0.93 * hemisphereRadius(Math.abs(dx), dy, dz);
            return [side * (Math.abs(dx) * r + GAP), dy * r + 0.1, dz * r];
        });
    }
    return body;
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

const HEMISPHERE_DIRECTIONS = 70000; // moitié utilisée par hémisphère
const TEMPORAL_DIRECTIONS = 9000;
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
 * Directions réparties régulièrement sur la sphère (spirale de Fibonacci), un peu perturbées :
 * pas d'amas ni de trous comme avec un tirage au hasard, le relief se lit mieux.
 */
function evenDirections(count) {
    const golden = Math.PI * (3 - Math.sqrt(5));
    const jitter = 0.8 * Math.sqrt(4 * Math.PI / count); // assez pour casser la trame de la spirale
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

/** Points de la surface du cortex (hémisphères + lobes temporaux), avec le centre de leur lobe. */
function cortexPoints() {
    const points = [];
    for (const d of evenDirections(HEMISPHERE_DIRECTIONS)) {
        if (d.x < 0) continue; // chaque hémisphère utilise la moitié extérieure, puis on la reflète
        const r = hemisphereRadius(d.x, d.y, d.z);
        for (const side of [1, -1]) {
            const x = side * (d.x * r + GAP), y = d.y * r + 0.1, z = d.z * r;
            if (!insideTemporal(x, y, z)) points.push({ x, y, z, side, center: [side * GAP, 0.1, 0] });
        }
    }
    const [cx, cy, cz] = TEMPORAL.center, [rx, ry, rz] = TEMPORAL.radius;
    for (const d of evenDirections(TEMPORAL_DIRECTIONS)) {
        for (const side of [1, -1]) {
            const x = side * (cx + d.x * rx), y = cy + d.y * ry, z = cz + d.z * rz;
            if (!insideHemisphere(x, y, z)) points.push({ x, y, z, side, center: [side * cx, cy, cz] });
        }
    }
    return points;
}

/** Hauteur du relief en (x, y, z) : 0 au fond d'un sillon, 1 au sommet d'un gyrus. */
function relief(x, y, z, side) {
    return THREE.MathUtils.smoothstep(sulcus(x + (side > 0 ? 0 : 7), y, z), 0, 0.3); // plis propres à chaque hémisphère
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
    // sont des creux étroits ; les gyri entre eux, des bourrelets arrondis éclairés comme des
    // tubes : flanc tourné vers la lumière plus clair, flanc opposé dans l'ombre.
    const normal = new THREE.Vector3();
    for (const p of cortexPoints()) {
        const h = relief(p.x, p.y, p.z, p.side);
        if (h < 0.03) continue; // fond des sillons : invisible de toute façon

        // On enfonce le point vers le centre du lobe selon la profondeur du sillon
        const [cx, cy, cz] = p.center;
        const k = 1 - SULCUS_DEPTH * (1 - h);
        const x = cx + (p.x - cx) * k, y = cy + (p.y - cy) * k, z = cz + (p.z - cz) * k;

        normal.set(p.x - cx, p.y - cy, p.z - cz).normalize();
        const lambert = 0.45 + 0.55 * Math.max(normal.dot(LIGHT), 0);
        // Pente du gyrus vers la lumière : le relief monte-t-il quand on avance vers elle ?
        const ahead = relief(p.x + LIGHT.x * 0.02, p.y + LIGHT.y * 0.02, p.z + LIGHT.z * 0.02, p.side);
        const slope = THREE.MathUtils.clamp((ahead - h) * 6, -1, 1);
        const lip = h > 0.1 && h < 0.3 ? 0.2 : 0; // liseré au bord des sillons
        const brightness = lambert * (0.08 + 1.4 * h ** 1.5 - 0.55 * slope * h) + lip;
        push(x, y, z, c2.copy(deep).lerp(light, 0.15 + 0.75 * h), Math.max(brightness, 0.02), normal);
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
