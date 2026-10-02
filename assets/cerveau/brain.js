import * as THREE from 'three';
import { glowPointsMaterial } from './textures.js';

/** Hologramme du cerveau : nuage de points cyan → violet. */
export function createBrain(texture, count = 14000) {
    return new THREE.Points(brainGeometry(count), glowPointsMaterial(texture, 0.035));
}

/**
 * Forme de cerveau procédurale : deux hémisphères plissés + cervelet + tronc.
 * Surtout des points en surface (effet hologramme), quelques-uns à l'intérieur.
 */
function brainGeometry(count) {
    const positions = [];
    const colors = [];
    const cyan = new THREE.Color('#00d4ff');
    const violet = new THREE.Color('#7f5af0');
    const c = new THREE.Color();

    const push = (x, y, z) => {
        positions.push(x, y, z);
        // Dégradé avant (cyan) → arrière (violet)
        c.copy(cyan).lerp(violet, THREE.MathUtils.clamp((z + 1.3) / 2.6, 0, 1));
        colors.push(c.r, c.g, c.b);
    };

    for (let i = 0; i < count; i++) {
        // Direction aléatoire uniforme
        const u = Math.random() * 2 - 1;
        const phi = Math.random() * Math.PI * 2;
        const s = Math.sqrt(1 - u * u);
        const dx = s * Math.cos(phi), dy = u, dz = s * Math.sin(phi);

        // 85 % en surface, 15 % dans le volume
        const depth = Math.random() < 0.85 ? 1 : Math.cbrt(Math.random()) * 0.9;
        const roll = Math.random();

        if (roll < 0.86) {
            // Hémisphères : ellipsoïde plissé (sillons), aplati côté médian
            const side = Math.random() < 0.5 ? -1 : 1;
            const gyri = 1 + 0.05 * Math.sin(dx * 11 + dy * 7) * Math.cos(dz * 9 - dy * 5)
                           + 0.03 * Math.sin(dz * 23 + dx * 17);
            let x = Math.abs(dx) * 0.62 * gyri * depth;
            const y = dy * (dy < 0 ? 0.55 : 0.8) * gyri * depth;
            const z = dz * 1.2 * gyri * depth;
            x = side * (x + 0.06); // fissure inter-hémisphérique
            push(x, y + 0.1, z);
        } else if (roll < 0.96) {
            // Cervelet : petit ellipsoïde strié, en bas à l'arrière
            const stripes = 1 + 0.04 * Math.sin(dy * 40);
            push(dx * 0.55 * stripes * depth, dy * 0.28 * depth - 0.5, dz * 0.35 * depth - 0.85);
        } else {
            // Tronc cérébral : cylindre qui descend
            const a = Math.random() * Math.PI * 2;
            const r = 0.13 * (depth === 1 ? 1 : Math.random());
            push(Math.cos(a) * r, -0.4 - Math.random() * 0.75, Math.sin(a) * r - 0.4);
        }
    }

    const geometry = new THREE.BufferGeometry();
    geometry.setAttribute('position', new THREE.Float32BufferAttribute(positions, 3));
    geometry.setAttribute('color', new THREE.Float32BufferAttribute(colors, 3));
    return geometry;
}
