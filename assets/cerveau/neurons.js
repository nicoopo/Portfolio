import * as THREE from 'three';
import { glowSprite } from './textures.js';

/**
 * Lobes du cerveau où sont placés les neurones (champ `zone` de src/Data/Competences.php).
 * center : centre de la zone ; radius : étalement des neurones autour.
 */
const ZONES = {
    frontal:   { center: [0, 0.35, 0.58],  radius: 0.24 },
    parietal:  { center: [0, 0.52, -0.18], radius: 0.24 },
    temporal:  { center: [0.38, -0.16, 0.12], radius: 0.15, mirror: true }, // réparti sur les deux côtés
    occipital: { center: [0, 0.2, -0.66],  radius: 0.2 },
    limbique:  { center: [0, 0.05, 0],    radius: 0.15 },
};

/**
 * Un neurone par compétence : noyau coloré + halo.
 * Retourne les halos : ce sont eux qu'on vise à la souris (plus larges que le noyau).
 */
export function createNeurons(neurons, texture) {
    const core = new THREE.IcosahedronGeometry(0.03, 2);
    const byZone = Object.groupBy(neurons, (n) => n.zone);

    return neurons.map((data, i) => {
        const siblings = byZone[data.zone];
        const color = new THREE.Color(data.couleur);

        const anchor = new THREE.Mesh(core, new THREE.MeshBasicMaterial({ color }));
        anchor.position.copy(neuronPosition(ZONES[data.zone], siblings.indexOf(data), siblings.length));

        const halo = glowSprite(texture, color, 0.22);
        anchor.add(halo);
        halo.userData = { kind: 'neuron', data, anchor, phase: i * 1.7 };
        return halo;
    });
}

/**
 * Position du i-ème neurone d'une zone : spirale de Fibonacci sur une petite sphère,
 * pour des neurones bien répartis sans tirage aléatoire (même place à chaque visite).
 */
function neuronPosition(zone, i, count) {
    const [cx, cy, cz] = zone.center;
    let side = 1;
    if (zone.mirror) {
        side = i % 2 === 0 ? 1 : -1;
        i = Math.floor(i / 2);
        count = Math.ceil(count / 2);
    }
    const y = count === 1 ? 0 : 1 - (2 * i) / (count - 1);
    const r = Math.sqrt(1 - y * y);
    const theta = i * Math.PI * (3 - Math.sqrt(5));
    return new THREE.Vector3(
        side * cx + Math.cos(theta) * r * zone.radius,
        cy + y * zone.radius,
        cz + Math.sin(theta) * r * zone.radius,
    );
}
