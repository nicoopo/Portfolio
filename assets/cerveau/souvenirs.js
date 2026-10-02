import * as THREE from 'three';
import { glowSprite } from './textures.js';

const COLOR = new THREE.Color('#ffe8a3');

/**
 * Fil de la mémoire : une courbe sous le centre du cerveau (à la place de l'hippocampe),
 * de l'arrière (souvenir le plus ancien) vers l'avant (le plus récent).
 */
const PATH = new THREE.CatmullRomCurve3([
    new THREE.Vector3(0, -0.15, -0.5),
    new THREE.Vector3(0, -0.28, -0.17),
    new THREE.Vector3(0, -0.28, 0.17),
    new THREE.Vector3(0, -0.15, 0.5),
]);

/**
 * Un souvenir par étape du parcours (src/Data/Parcours.php, du plus récent au plus ancien).
 * Retourne { object, targets, update } ; targets = souvenirs qu'on vise à la souris.
 */
export function createSouvenirs(parcours, texture) {
    const object = new THREE.Group();

    const line = new THREE.Line(
        new THREE.BufferGeometry().setFromPoints(PATH.getPoints(64)),
        new THREE.LineBasicMaterial({ color: COLOR, transparent: true, opacity: 0.35, blending: THREE.AdditiveBlending, depthWrite: false }),
    );
    // Signal qui remonte le temps le long du fil
    const signal = glowSprite(texture, COLOR, 0.12);
    object.add(line, signal);

    const targets = parcours.map((data, i) => {
        const anchor = new THREE.Group();
        // Le plus ancien (dernier de la liste) à l'arrière
        const t = parcours.length === 1 ? 0.5 : 0.1 + 0.8 * (1 - i / (parcours.length - 1));
        anchor.position.copy(PATH.getPoint(t));

        const halo = glowSprite(texture, COLOR, 0.2);
        halo.userData = { kind: 'souvenir', data, anchor, phase: i * 2.3 };
        anchor.add(halo);
        object.add(anchor);
        return halo;
    });

    const update = (time) => {
        signal.position.copy(PATH.getPoint((time * 0.15) % 1));
    };

    return { object, targets, update };
}
