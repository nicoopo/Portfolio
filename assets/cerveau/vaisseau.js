import * as THREE from 'three';
import { glowPointsMaterial, glowSprite } from './textures.js';

const EDGE = new THREE.Color('#8be9ff');
const HULL = new THREE.Color('#0b3a4a');
const ENGINE = new THREE.Color('#5fd4ff');

/** Triangles (liste de sommets [x, y, z]) en une géométrie. */
function triangles(...points) {
    return new THREE.BufferGeometry().setFromPoints(points.map((p) => new THREE.Vector3(...p)));
}

/**
 * Vaisseau du mode exploration, dans le style hologramme du cerveau :
 * coque sombre translucide, arêtes lumineuses, réacteur qui s'allume avec la poussée.
 * Pointe vers -z. Retourne un Group ; userData.update(power, turn, climb, still) l'anime.
 */
export function createShip(texture) {
    const ship = new THREE.Group();
    // Modèle à part : il s'incline dans les virages sans changer le cap du vaisseau
    const model = new THREE.Group();
    ship.add(model);

    const parts = [
        // Fuselage : pyramide allongée à section en losange
        new THREE.ConeGeometry(0.022, 0.13, 4).rotateX(-Math.PI / 2).rotateZ(Math.PI / 4),
        // Ailes en flèche
        triangles([0, 0, -0.01], [-0.085, 0, 0.06], [0, 0, 0.045], [0, 0, -0.01], [0, 0, 0.045], [0.085, 0, 0.06]),
        // Dérive
        triangles([0, 0, 0.005], [0, 0.035, 0.065], [0, 0, 0.06]),
    ];
    const hull = new THREE.MeshBasicMaterial({ color: HULL, transparent: true, opacity: 0.8, side: THREE.DoubleSide });
    const edges = new THREE.LineBasicMaterial({ color: EDGE, transparent: true, blending: THREE.AdditiveBlending });
    for (const geometry of parts) {
        model.add(new THREE.Mesh(geometry, hull), new THREE.LineSegments(new THREE.EdgesGeometry(geometry), edges));
    }

    const engine = glowSprite(texture, ENGINE, 0.02);
    engine.position.z = 0.07;
    // Feux au bout des ailes
    const tips = [-1, 1].map((side) => {
        const tip = glowSprite(texture, EDGE, 0.012);
        tip.position.set(0.085 * side, 0, 0.06);
        return tip;
    });
    model.add(engine, ...tips);

    // Toujours visible, même à travers la paroi du cerveau (comme les neurones)
    ship.traverse((child) => {
        child.renderOrder = 2;
        if (child.material) child.material.depthTest = false;
    });

    /** power : 0 à 1 en croisière, jusqu'à 3 en boost ; turn, climb : -1 à 1 ; still : mouvements réduits, pas de scintillement */
    ship.userData.update = (power, turn, climb, still) => {
        // S'incline dans les virages, lève ou baisse un peu le nez
        model.rotation.z += (turn * 0.45 - model.rotation.z) * 0.15;
        model.rotation.x += (climb * 0.25 - model.rotation.x) * 0.15;
        const flicker = still ? 1 : 0.85 + 0.3 * Math.random();
        engine.scale.setScalar((0.02 + 0.03 * power) * flicker);
    };

    return ship;
}

/**
 * Champ d'étoiles du mode exploration : des repères dans le grand vide, pour sentir la vitesse.
 * Points de taille fixe à l'écran, répartis dans une coquille autour du cerveau.
 */
export function createStarfield(texture, count = 1500, inner = 20, outer = 70) {
    const positions = new Float32Array(count * 3);
    const colors = new Float32Array(count * 3);
    const point = new THREE.Vector3();
    const color = new THREE.Color();
    for (let i = 0; i < count; i++) {
        point.randomDirection().multiplyScalar(THREE.MathUtils.lerp(inner, outer, Math.random()));
        point.toArray(positions, i * 3);
        color.setHSL(0.55 + 0.1 * Math.random(), 0.5, 0.5 + 0.4 * Math.random()).toArray(colors, i * 3);
    }
    const geometry = new THREE.BufferGeometry();
    geometry.setAttribute('position', new THREE.BufferAttribute(positions, 3));
    geometry.setAttribute('color', new THREE.BufferAttribute(colors, 3));
    return new THREE.Points(geometry, glowPointsMaterial(texture, 3, { sizeAttenuation: false }));
}
