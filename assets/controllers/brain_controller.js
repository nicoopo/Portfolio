/* stimulusFetch: 'lazy' */
import { Controller } from '@hotwired/stimulus';
import * as THREE from 'three';
import { OrbitControls } from 'three/addons/controls/OrbitControls.js';

/**
 * Cerveau holographique en nuage de points (Three.js).
 * Chargé uniquement sur les pages qui l'utilisent (lazy).
 */
export default class extends Controller {
    static targets = ['fallback'];

    connect() {
        try {
            this.renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true });
        } catch {
            // Pas de WebGL : on laisse le message de repli visible
            return;
        }
        this.fallbackTarget.hidden = true;

        const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

        this.renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
        this.element.appendChild(this.renderer.domElement);

        this.scene = new THREE.Scene();
        this.camera = new THREE.PerspectiveCamera(50, 1, 0.01, 100);
        this.camera.position.set(3.2, 1.2, 3.2);

        this.controls = new OrbitControls(this.camera, this.renderer.domElement);
        this.controls.enableDamping = true;
        this.controls.enablePan = false;
        this.controls.minDistance = 0.6; // assez près pour frôler l'intérieur
        this.controls.maxDistance = 8;
        this.controls.autoRotate = !reducedMotion;
        this.controls.autoRotateSpeed = 0.6;

        this.texture = dotTexture();
        this.brain = new THREE.Points(
            brainGeometry(),
            new THREE.PointsMaterial({
                size: 0.035,
                map: this.texture,
                vertexColors: true,
                transparent: true,
                depthWrite: false,
                blending: THREE.AdditiveBlending,
            }),
        );
        this.scene.add(this.brain);

        this.resizeObserver = new ResizeObserver(() => this.resize());
        this.resizeObserver.observe(this.element);
        this.resize();

        const clock = new THREE.Clock();
        const loop = () => {
            this.frame = requestAnimationFrame(loop);
            if (!reducedMotion) {
                // Respiration lente de l'hologramme
                this.brain.material.opacity = 0.75 + 0.25 * Math.sin(clock.getElapsedTime() * 1.5);
            }
            this.controls.update();
            this.renderer.render(this.scene, this.camera);
        };
        loop();
    }

    disconnect() {
        if (!this.renderer) return;
        cancelAnimationFrame(this.frame);
        this.resizeObserver.disconnect();
        this.controls.dispose();
        this.brain.geometry.dispose();
        this.brain.material.dispose();
        this.texture.dispose();
        this.renderer.dispose();
        this.renderer.domElement.remove();
        this.renderer = null;
    }

    resize() {
        const { clientWidth: w, clientHeight: h } = this.element;
        this.renderer.setSize(w, h);
        this.camera.aspect = w / h;
        this.camera.fov = w < h ? 75 : 50; // écran vertical (mobile) : on élargit le champ pour garder le cerveau entier
        this.camera.updateProjectionMatrix();
    }
}

/**
 * Forme de cerveau procédurale : deux hémisphères plissés + cervelet + tronc.
 * Surtout des points en surface (effet hologramme), quelques-uns à l'intérieur.
 */
function brainGeometry(count = 14000) {
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

/** Point lumineux rond et flou, dessiné une fois dans un canvas. */
function dotTexture() {
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
