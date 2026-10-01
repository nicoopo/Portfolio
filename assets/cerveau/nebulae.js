import * as THREE from 'three';
import { glowPointsMaterial, glowSprite } from './textures.js';

const ORBIT_RADIUS = 6.5; // assez loin pour qu'il faille dézoomer pour les découvrir
const CLOUD_POINTS = 1500;

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

        const cloud = new THREE.Points(cloudGeometry(new THREE.Color(data.couleur)), glowPointsMaterial(texture, 0.2, { opacity: 0.5 }));
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

/** Nuage de gaz : disque épais, dense au centre (répartition gaussienne), teinte variée. */
function cloudGeometry(color) {
    const positions = [];
    const colors = [];
    const white = new THREE.Color('#ffffff');
    const c = new THREE.Color();

    for (let i = 0; i < CLOUD_POINTS; i++) {
        positions.push(gaussian() * 0.9, gaussian() * 0.3, gaussian() * 0.9);
        // Surtout la couleur de la passion, quelques points plus clairs
        c.copy(color).lerp(white, Math.random() * 0.35).multiplyScalar(0.6 + Math.random() * 0.4);
        colors.push(c.r, c.g, c.b);
    }

    const geometry = new THREE.BufferGeometry();
    geometry.setAttribute('position', new THREE.Float32BufferAttribute(positions, 3));
    geometry.setAttribute('color', new THREE.Float32BufferAttribute(colors, 3));
    return geometry;
}

/** Tirage gaussien (Box-Muller). */
function gaussian() {
    return Math.sqrt(-2 * Math.log(1 - Math.random())) * Math.cos(2 * Math.PI * Math.random());
}
