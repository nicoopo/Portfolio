import * as THREE from 'three';
import { ImprovedNoise } from 'three/addons/math/ImprovedNoise.js';
import { fadingPointsMaterial, glowSprite } from './textures.js';

const ORBIT_RADIUS = 6.5; // assez loin pour qu'il faille dézoomer pour les découvrir
const GAS_POINTS = 4000;  // filaments de gaz
const STAR_POINTS = 30;   // étoiles jeunes nichées dans le gaz

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
            Math.sin(i * 2.1) * 1.4,
            Math.sin(angle) * ORBIT_RADIUS,
        );

        const cloud = createCloud(new THREE.Color(data.couleur), i * 17.3, texture);
        // Chaque nébuleuse a sa propre inclinaison
        cloud.rotation.set(i * 0.9, i * 0.5, i * 1.3);
        clouds.push(cloud);

        const core = glowSprite(texture, new THREE.Color(data.couleur), 1.6);
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
 * Nébuleuse : filaments de gaz sculptés par du bruit de Perlin, traversés de bandes
 * de poussière sombres, cœur plus chaud, bords plus sombres ; plus quelques étoiles
 * (le halo vient du bloom). `seed` décale le bruit : chaque nébuleuse a sa forme.
 */
function createCloud(color, seed, texture) {
    const cloud = new THREE.Group();
    const hot = color.clone().lerp(new THREE.Color('#ffffff'), 0.65);
    const edge = color.clone().offsetHSL(-0.03, 0, -0.2); // bords plus sombres, teinte à peine décalée
    const c = new THREE.Color();

    // Gaz : on tire des points dans un disque épais et on ne garde que ceux qui tombent
    // dans un filament (bruit fort) et hors d'une bande de poussière
    const gas = { positions: [], colors: [] };
    while (gas.positions.length < GAS_POINTS * 3) {
        const x = gaussian() * 1.1, y = gaussian() * 0.4, z = gaussian() * 1.1;
        const density = fbm(x * 0.9 + seed, y * 0.9, z * 0.9);
        const dust = perlin.noise(x * 1.6 + seed + 50, y * 1.6, z * 1.6);
        if (Math.random() > smoothstep(0.45, 0.8, density) || dust > 0.3 + Math.random() * 0.15) continue;

        const r = Math.hypot(x, y, z);
        c.copy(hot).lerp(color, smoothstep(0, 1, r)).lerp(edge, smoothstep(1, 2.2, r))
            .multiplyScalar((0.35 + 0.65 * density) * Math.exp(-r * r / 3)); // s'éteint vers l'extérieur
        gas.positions.push(x, y, z);
        gas.colors.push(c.r, c.g, c.b);
    }
    cloud.add(new THREE.Points(pointsGeometry(gas), fadingPointsMaterial(texture, 0.13, { opacity: 0.8 })));

    // Étoiles : blanches, plus nettes
    const stars = { positions: [], colors: [] };
    for (let i = 0; i < STAR_POINTS; i++) {
        stars.positions.push(gaussian() * 1.2, gaussian() * 0.4, gaussian() * 1.2);
        c.set('#ffffff').lerp(hot, Math.random() * 0.5);
        stars.colors.push(c.r, c.g, c.b);
    }
    cloud.add(new THREE.Points(pointsGeometry(stars), fadingPointsMaterial(texture, 0.07)));

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

/** Tirage gaussien (Box-Muller). */
function gaussian() {
    return Math.sqrt(-2 * Math.log(1 - Math.random())) * Math.cos(2 * Math.PI * Math.random());
}
