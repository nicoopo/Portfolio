import * as THREE from 'three';
import { ImprovedNoise } from 'three/addons/math/ImprovedNoise.js';
import { fadeNearCamera, fadingPointsMaterial, glowSprite } from './textures.js';

const ORBIT_RADIUS = 9.5; // assez loin pour qu'il faille dézoomer pour les découvrir
const SHELL_RADIUS = 1.2;  // rayon moyen de la coquille de gaz
const GAS_POINTS = 6000;    // grain fin du gaz
const SMOOTH_POINTS = 2500; // gaz lisse
const DUST_POINTS = 1500;   // poussière sombre
const STAR_POINTS = 60;

const WHITE = new THREE.Color('#ffffff');
const STAR_BLUE = new THREE.Color('#9fc4ff');

const perlin = new ImprovedNoise();

/**
 * Nébuleuses des passions, en orbite autour du cerveau.
 * Retourne { object, targets, update } ; targets = cœurs lumineux qu'on vise à la souris.
 */
export function createNebulae(passions, texture) {
    const object = new THREE.Group();
    const clouds = [];

    const targets = passions.map((data, i) => {
        const angle = (i / passions.length) * Math.PI * 2 + 0.4;
        const anchor = new THREE.Group();
        anchor.position.set(
            Math.cos(angle) * ORBIT_RADIUS,
            Math.sin(i * 2.1) * 2,
            Math.sin(angle) * ORBIT_RADIUS,
        );

        const cloud = createCloud(new THREE.Color(data.couleur), i * 17.3, texture);
        // Chaque nébuleuse a sa propre inclinaison
        cloud.rotation.set(i * 0.9, i * 0.5, i * 1.3);
        clouds.push(cloud);

        // Lueur de l'amas central : c'est elle qu'on vise à la souris
        const core = glowSprite(texture, new THREE.Color(data.couleur).lerp(WHITE, 0.5), 1.6);
        core.material.opacity = 0.2;
        fadeNearCamera(core.material);
        core.userData = { kind: 'nebula', data, anchor, phase: i * 1.1 };

        anchor.add(cloud, core);
        object.add(anchor);
        return core;
    });

    const update = (time) => {
        clouds.forEach((cloud, i) => { cloud.rotation.y = i * 0.5 + time * 0.05; });
    };

    return { object, targets, update };
}

/**
 * Nébuleuse en coquille (comme la Rosette ou l'Hélice) : une bulle de gaz irrégulière,
 * vue de n'importe quel angle comme un anneau plus brillant au bord.
 * - extérieur dans la couleur de la passion, intérieur dans sa couleur complémentaire
 *   (comme l'hydrogène rouge autour de l'oxygène bleu-vert sur les photos) ;
 * - grain fin + gaz lisse, poussière sombre qui assombrit vraiment le gaz ;
 * - amas d'étoiles jeunes dans la cavité centrale.
 * `seed` décale le bruit : chaque nébuleuse a sa forme.
 */
function createCloud(color, seed, texture) {
    const cloud = new THREE.Group();
    const outer = color.clone();
    const inner = color.clone().offsetHSL(0.5, 0, 0);
    const c = new THREE.Color();

    /**
     * Point du nuage au hasard, tiré directement autour de la coquille (rapide : peu de rejets).
     * r = distance au centre rapportée au rayon de la coquille dans cette direction (1 = sur la coquille).
     */
    const sample = () => {
        const [dx, dy, dz] = randomDirection();
        // Coquille irrégulière : son rayon varie selon la direction
        const shellRadius = SHELL_RADIUS * (1 + 0.45 * perlin.noise(dx * 1.3 + seed, dy * 1.3, dz * 1.3));
        // 95 % autour de la coquille, le reste remplit un peu la cavité
        const r = Math.random() < 0.95 ? 1 + gaussian() * 0.3 : Math.abs(gaussian()) * 0.6;
        const x = dx * r * shellRadius, y = dy * r * shellRadius * 0.75, z = dz * r * shellRadius;
        const filament = smoothstep(0.35, 0.72, fbm(x * 1.1 + seed, y * 1.1, z * 1.1));
        return { x, y, z, r, filament };
    };
    const gasColor = ({ r, filament }) => c.copy(inner)
        .lerp(outer, smoothstep(0.6, 1.1, r))
        .multiplyScalar((0.25 + 0.55 * filament)
            * Math.exp(-((Math.max(r - 1, 0) / 0.5) ** 2)) // s'éteint au-delà de la coquille
            * (0.35 + 0.65 * smoothstep(0.2, 0.8, r)));      // cavité plus sombre

    // Gaz : grain fin, et gaz lisse (points plus grands, très transparents, qui se fondent)
    const grain = { positions: [], colors: [] };
    const smooth = { positions: [], colors: [] };
    while (grain.positions.length < GAS_POINTS * 3 || smooth.positions.length < SMOOTH_POINTS * 3) {
        const p = sample();
        if (Math.random() > p.filament) continue;
        const layer = smooth.positions.length < SMOOTH_POINTS * 3 && Math.random() < 0.3 ? smooth : grain;
        if (layer === grain && grain.positions.length >= GAS_POINTS * 3) continue;
        gasColor(p);
        layer.positions.push(p.x, p.y, p.z);
        layer.colors.push(c.r, c.g, c.b);
    }
    cloud.add(
        new THREE.Points(pointsGeometry(smooth), fadingPointsMaterial(texture, 0.55, { opacity: 0.12 })),
        new THREE.Points(pointsGeometry(grain), fadingPointsMaterial(texture, 0.08, { opacity: 0.6 })),
    );

    // Poussière : globules et filaments sombres posés sur le gaz (mélange normal, pas additif)
    const dust = { positions: [], colors: [] };
    while (dust.positions.length < DUST_POINTS * 3) {
        const p = sample();
        if (p.filament < 0.2 || fbm(p.x * 1.9 + seed + 40, p.y * 1.9, p.z * 1.9) < 0.6) continue;
        dust.positions.push(p.x, p.y, p.z);
        c.copy(outer).multiplyScalar(0.06);
        dust.colors.push(c.r, c.g, c.b);
    }
    const dustPoints = new THREE.Points(pointsGeometry(dust), fadingPointsMaterial(texture, 0.2, { opacity: 0.55, blending: THREE.NormalBlending }));
    dustPoints.renderOrder = 1; // après le gaz, pour l'assombrir
    cloud.add(dustPoints);

    // Étoiles : un amas jeune et brillant dans la cavité, d'autres éparpillées
    const faint = { positions: [], colors: [] };
    const bright = { positions: [], colors: [] };
    for (let i = 0; i < STAR_POINTS; i++) {
        const cluster = i < STAR_POINTS / 3;
        const spread = cluster ? 0.35 : 1.4;
        const layer = cluster && i % 3 === 0 ? bright : faint;
        layer.positions.push(gaussian() * spread, gaussian() * spread * 0.75, gaussian() * spread);
        c.set('#ffffff').lerp(STAR_BLUE, Math.random() * 0.6);
        layer.colors.push(c.r, c.g, c.b);
    }
    const brightStars = new THREE.Points(pointsGeometry(bright), fadingPointsMaterial(texture, 0.18));
    brightStars.renderOrder = 2; // devant la poussière
    cloud.add(new THREE.Points(pointsGeometry(faint), fadingPointsMaterial(texture, 0.06)), brightStars);

    return cloud;
}

function pointsGeometry({ positions, colors }) {
    const geometry = new THREE.BufferGeometry();
    geometry.setAttribute('position', new THREE.Float32BufferAttribute(positions, 3));
    geometry.setAttribute('color', new THREE.Float32BufferAttribute(colors, 3));
    return geometry;
}

/** Bruit fractal (4 octaves), ramené entre 0 et 1, déformé pour des volutes. */
function fbm(x, y, z) {
    const warp = perlin.noise(x * 0.5, y * 0.5, z * 0.5) * 1.5;
    let sum = 0, amplitude = 0.5, frequency = 1;
    for (let o = 0; o < 4; o++) {
        sum += amplitude * perlin.noise((x + warp) * frequency, (y + warp) * frequency, (z + warp) * frequency);
        amplitude *= 0.5;
        frequency *= 2.1;
    }
    return THREE.MathUtils.clamp(0.5 + sum, 0, 1);
}

function smoothstep(edge0, edge1, x) {
    return THREE.MathUtils.smoothstep(x, edge0, edge1);
}

/** Direction aléatoire uniforme. */
function randomDirection() {
    const u = Math.random() * 2 - 1;
    const phi = Math.random() * Math.PI * 2;
    const s = Math.sqrt(1 - u * u);
    return [s * Math.cos(phi), u, s * Math.sin(phi)];
}

/** Tirage gaussien (Box-Muller). */
function gaussian() {
    return Math.sqrt(-2 * Math.log(1 - Math.random())) * Math.cos(2 * Math.PI * Math.random());
}
