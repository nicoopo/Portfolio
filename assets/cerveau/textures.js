import * as THREE from 'three';

/** Point lumineux rond et flou, dessiné une fois dans un canvas. */
export function dotTexture() {
    const size = 64;
    const canvas = document.createElement('canvas');
    canvas.width = canvas.height = size;
    const ctx = canvas.getContext('2d');
    const g = ctx.createRadialGradient(size / 2, size / 2, 0, size / 2, size / 2, size / 2);
    g.addColorStop(0, 'rgba(255,255,255,1)');
    g.addColorStop(0.3, 'rgba(255,255,255,0.6)');
    g.addColorStop(1, 'rgba(255,255,255,0)');
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

/**
 * Comme glowPointsMaterial, mais les points s'effacent quand la caméra s'en approche
 * (sinon, en traversant un nuage, chaque point devient une énorme tache à l'écran).
 */
export function fadingPointsMaterial(texture, size, options = {}) {
    const material = glowPointsMaterial(texture, size, options);
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
