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
    material.onBeforeCompile = (shader) => {
        shader.vertexShader = shader.vertexShader
            .replace('#include <common>', '#include <common>\nvarying float vNearFade;')
            .replace('#include <fog_vertex>', '#include <fog_vertex>\nvNearFade = smoothstep(1.0, 4.0, -mvPosition.z);');
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
