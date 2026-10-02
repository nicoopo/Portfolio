import * as THREE from 'three';
import { glowPointsMaterial } from './textures.js';

const SEGMENTS = 24; // finesse des courbes
const BRIGHTNESS = { idle: 0.45, linked: 1, dimmed: 0.06 };

/**
 * Synapses : une courbe entre deux neurones dès que leurs compétences ont servi
 * dans un même projet, avec un signal lumineux qui circule dessus.
 */
export class Synapses {
    constructor(halos, texture) {
        this.links = linksFromProjects(halos);
        this.object = new THREE.Group();

        // Toutes les courbes dans un seul LineSegments (un seul appel de dessin)
        const positions = [];
        this.baseColors = [];
        for (const link of this.links) {
            const a = link.a.userData.anchor.position;
            const b = link.b.userData.anchor.position;
            link.curve = synapseCurve(a, b);
            const points = link.curve.getPoints(SEGMENTS);
            const ca = new THREE.Color(link.a.userData.data.couleur);
            const cb = new THREE.Color(link.b.userData.data.couleur);
            for (let i = 0; i < SEGMENTS; i++) {
                // Dégradé de la couleur du neurone A vers celle du neurone B
                for (const k of [i, i + 1]) {
                    positions.push(points[k].x, points[k].y, points[k].z);
                    const c = ca.clone().lerp(cb, k / SEGMENTS);
                    this.baseColors.push(c.r, c.g, c.b);
                }
            }
        }
        const lines = new THREE.BufferGeometry();
        lines.setAttribute('position', new THREE.Float32BufferAttribute(positions, 3));
        lines.setAttribute('color', new THREE.Float32BufferAttribute(this.baseColors, 3));
        this.lines = new THREE.LineSegments(lines, new THREE.LineBasicMaterial({
            vertexColors: true,
            transparent: true,
            depthWrite: false,
            blending: THREE.AdditiveBlending,
        }));

        // Un signal par synapse (plus rapide quand le lien est fort)
        const signals = new THREE.BufferGeometry();
        signals.setAttribute('position', new THREE.Float32BufferAttribute(new Float32Array(this.links.length * 3), 3));
        signals.setAttribute('color', new THREE.Float32BufferAttribute(new Float32Array(this.links.length * 3), 3));
        this.signals = new THREE.Points(signals, glowPointsMaterial(texture, 0.07));

        this.object.add(this.lines, this.signals);
        this.highlight(null);
        this.update(0);
    }

    /** Neurones reliés à un neurone donné. */
    neighborsOf(halo) {
        const neighbors = new Set();
        for (const { a, b } of this.links) {
            if (a === halo) neighbors.add(b);
            if (b === halo) neighbors.add(a);
        }
        return neighbors;
    }

    /** Fait avancer chaque signal le long de sa synapse (aller-retour). */
    update(time) {
        const position = this.signals.geometry.attributes.position;
        this.links.forEach((link, i) => {
            const progress = (time * 0.25 * link.weight + link.phase) % 2;
            const p = link.curve.getPointAt(progress < 1 ? progress : 2 - progress);
            position.setXYZ(i, p.x, p.y, p.z);
        });
        position.needsUpdate = true;
    }

    /** Neurone sélectionné : ses synapses s'allument, les autres s'effacent. null = tout normal. */
    highlight(selected) {
        const lineColor = this.lines.geometry.attributes.color;
        const signalColor = this.signals.geometry.attributes.color;
        const perLink = SEGMENTS * 2;

        this.links.forEach((link, i) => {
            let level = BRIGHTNESS.idle;
            if (selected) level = link.a === selected || link.b === selected ? BRIGHTNESS.linked : BRIGHTNESS.dimmed;

            for (let v = i * perLink; v < (i + 1) * perLink; v++) {
                lineColor.setXYZ(v, this.baseColors[v * 3] * level, this.baseColors[v * 3 + 1] * level, this.baseColors[v * 3 + 2] * level);
            }
            // En mélange additif, une couleur noire est invisible
            const s = selected && level === BRIGHTNESS.dimmed ? 0 : level;
            signalColor.setXYZ(i, s, s, s);
        });
        lineColor.needsUpdate = true;
        signalColor.needsUpdate = true;
    }
}

/** Une synapse par paire de neurones utilisés dans un même projet ; weight = nombre de projets communs. */
function linksFromProjects(halos) {
    const byProject = new Map();
    for (const halo of halos) {
        for (const { titre } of halo.userData.data.projets) {
            byProject.set(titre, [...(byProject.get(titre) ?? []), halo]);
        }
    }

    const links = new Map();
    for (const group of byProject.values()) {
        for (let i = 0; i < group.length; i++) {
            for (let j = i + 1; j < group.length; j++) {
                const key = `${halos.indexOf(group[i])}-${halos.indexOf(group[j])}`;
                const link = links.get(key) ?? { a: group[i], b: group[j], weight: 0, phase: links.size * 0.37 };
                link.weight++;
                links.set(key, link);
            }
        }
    }
    return [...links.values()];
}

/** Courbe qui se bombe vers l'extérieur du cerveau, comme une fibre, au lieu d'une ligne droite. */
function synapseCurve(a, b) {
    const middle = a.clone().add(b).multiplyScalar(0.5);
    const outward = middle.lengthSq() > 1e-4 ? middle.clone().normalize() : new THREE.Vector3(0, 1, 0);
    const control = middle.addScaledVector(outward, 0.15 + a.distanceTo(b) * 0.25);
    return new THREE.QuadraticBezierCurve3(a.clone(), control, b.clone());
}
