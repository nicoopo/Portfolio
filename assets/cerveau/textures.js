import * as THREE from 'three';

/** Point lumineux rond et flou, dessiné une fois dans un canvas ; stops = [position, opacité]. */
export function dotTexture(stops = [[0, 1], [0.3, 0.6], [1, 0]]) {
    const size = 64;
    const canvas = document.createElement('canvas');
    canvas.width = canvas.height = size;
    const ctx = canvas.getContext('2d');
    const g = ctx.createRadialGradient(size / 2, size / 2, 0, size / 2, size / 2, size / 2);
    for (const [at, alpha] of stops) g.addColorStop(at, `rgba(255,255,255,${alpha})`);
    ctx.fillStyle = g;
    ctx.fillRect(0, 0, size, size);
    return new THREE.CanvasTexture(canvas);
}

/** Matériau additif commun aux nuages de points lumineux. */
export function glowPointsMaterial(texture, size, options = {}) {
    return new THREE.PointsMaterial({
        size,
        map: texture,
        vertexColors: true,
        transparent: true,
        depthWrite: false,
        blending: THREE.AdditiveBlending,
        ...options,
    });
}

/** Comme glowPointsMaterial, mais les points s'effacent quand la caméra s'en approche. */
export function fadingPointsMaterial(texture, size, options = {}) {
    return fadeNearCamera(glowPointsMaterial(texture, size, options));
}

/**
 * Efface ce que dessine le matériau (points, sprites) à l'approche de la caméra :
 * sinon, en traversant une nébuleuse, chaque point devient une énorme tache à l'écran.
 */
export function fadeNearCamera(material) {
    return fadeShader(material, 'smoothstep(1.0, 4.0, -mvPosition.z)');
}

/** Mode exploration : distance à la caméra sous laquelle neurones et souvenirs s'effacent ; 0 = jamais (hors exploration). */
export const exploreFade = { value: 0 };

/**
 * Comme fadeNearCamera, mais seulement en exploration et de bien plus près : le vaisseau frôle
 * les points pour les ramasser, la caméra derrière lui finit dedans et la lueur remplirait l'écran.
 */
export function fadeWhenExploring(material) {
    return fadeShader(material, 'exploreFade > 0.0 ? smoothstep(0.3 * exploreFade, exploreFade, -mvPosition.z) : 1.0', { exploreFade });
}

/** Multiplie l'opacité de ce que dessine le matériau par `fade` (GLSL calculé par sommet, mvPosition connu). */
function fadeShader(material, fade, uniforms = {}) {
    const declarations = Object.keys(uniforms).map((name) => `uniform float ${name};\n`).join('');
    material.onBeforeCompile = (shader) => {
        Object.assign(shader.uniforms, uniforms);
        shader.vertexShader = shader.vertexShader
            .replace('#include <common>', `#include <common>\n${declarations}varying float vNearFade;`)
            .replace('#include <fog_vertex>', `#include <fog_vertex>\nvNearFade = ${fade};`);
        shader.fragmentShader = shader.fragmentShader
            .replace('#include <common>', '#include <common>\nvarying float vNearFade;')
            .replace('#include <opaque_fragment>', 'diffuseColor.a *= vNearFade;\n#include <opaque_fragment>');
    };
    return material;
}

/** Sprite lumineux (halo) d'une couleur donnée. */
export function glowSprite(texture, color, scale) {
    const sprite = new THREE.Sprite(new THREE.SpriteMaterial({
        map: texture,
        color,
        transparent: true,
        depthWrite: false,
        blending: THREE.AdditiveBlending,
    }));
    sprite.scale.setScalar(scale);
    return sprite;
}
