/*
 * Planètes du système solaire des compétences (systeme_controller.js) : textures dessinées au chargement
 * (géante gazeuse à bandes et tempêtes, planète rocheuse à cratères, planète de glace), atmosphère qui
 * s'allume sur le bord, anneaux et soleil en shader qui bouillonne.
 */
import * as THREE from 'three';

/** Générateur pseudo-aléatoire reproductible : une planète garde son aspect d'une visite à l'autre */
export function random(seed) {
    let s = seed >>> 0 || 1;
    return () => {
        s = (s * 1664525 + 1013904223) >>> 0;
        return s / 4294967296;
    };
}

/** Bruit lisse 2D, périodique en x (période entière) : la texture fait le tour de la sphère sans couture */
function noise2(seed) {
    const hash = (x, y) => {
        let h = (x * 374761393 + y * 668265263 + seed * 2147483647) | 0;
        h = Math.imul(h ^ (h >>> 13), 1274126177);
        return ((h ^ (h >>> 16)) >>> 0) / 4294967296;
    };
    return (x, y, period) => {
        const xi = Math.floor(x), yi = Math.floor(y), fx = x - xi, fy = y - yi;
        const u = fx * fx * (3 - 2 * fx), v = fy * fy * (3 - 2 * fy);
        const x0 = ((xi % period) + period) % period, x1 = (x0 + 1) % period;
        const a = hash(x0, yi), b = hash(x1, yi), c = hash(x0, yi + 1), d = hash(x1, yi + 1);
        return a + (b - a) * u + (c - a) * v + (a - b - c + d) * u * v;
    };
}

/** Somme de bruits de plus en plus fins (0 → 1) */
function fbm(noise, x, y, period, octaves = 4) {
    let sum = 0, amplitude = 0.5, total = 0;
    for (let i = 0; i < octaves; i++) {
        sum += noise(x, y, period) * amplitude;
        total += amplitude;
        x *= 2; y *= 2; period *= 2; amplitude /= 2;
    }
    return sum / total;
}

const mixColour = (a, b, t) => a.clone().lerp(b, Math.min(Math.max(t, 0), 1));

/**
 * Texture d'une planète (équirectangulaire), teintée par colour (couleur de sa catégorie).
 * kind : 'gaz', 'roche' ou 'glace'.
 */
export function planetTexture(colour, kind, seed, width = 256) {
    const height = width / 2;
    const canvas = Object.assign(document.createElement('canvas'), { width, height });
    const ctx = canvas.getContext('2d');
    const image = ctx.createImageData(width, height);
    const noise = noise2(seed);
    const rand = random(seed);
    const base = new THREE.Color(colour);
    const dark = base.clone().multiplyScalar(0.35), light = mixColour(base, new THREE.Color(0xffffff), 0.55);
    const period = 8; // cellules de bruit sur le tour de la planète

    // Tempêtes (gaz) ou cratères (roche) : quelques taches placées au hasard
    const spots = Array.from({ length: kind === 'gaz' ? 2 : 14 }, () => ({
        x: rand() * width, y: height * (0.15 + rand() * 0.7), r: (kind === 'gaz' ? 0.05 : 0.015 + rand() * 0.04) * width,
    }));
    const bands = 4 + Math.floor(rand() * 5);

    for (let y = 0; y < height; y++) {
        for (let x = 0; x < width; x++) {
            const nx = (x / width) * period, ny = (y / height) * period / 2;
            let c;
            if (kind === 'gaz') {
                // Bandes horizontales tordues par la turbulence
                const warp = fbm(noise, nx * 2, ny * 2, period * 2) * 2.5;
                const band = 0.5 + 0.5 * Math.sin((y / height) * Math.PI * bands * 2 + warp);
                c = mixColour(dark, light, band * 0.85 + fbm(noise, nx * 4, ny * 8, period * 4, 3) * 0.3);
            } else if (kind === 'roche') {
                const relief = fbm(noise, nx, ny, period, 5);
                c = mixColour(dark, light, relief * 1.4 - 0.2);
            } else {
                // Glace : pâle, veinée de fines fissures
                const relief = fbm(noise, nx * 1.5, ny * 1.5, period * 1.5, 4);
                const crack = Math.abs(fbm(noise, nx * 3 + 11, ny * 3, period * 3, 3) - 0.5) < 0.02 ? 0.5 : 0;
                c = mixColour(light, new THREE.Color(0xffffff), relief * 0.6).multiplyScalar(1 - crack * 0.5);
            }
            for (const spot of spots) {
                let dx = Math.abs(x - spot.x);
                dx = Math.min(dx, width - dx); // la tache fait le tour sans couture
                const d = Math.hypot(dx, (y - spot.y) * (kind === 'gaz' ? 1.8 : 1)) / spot.r;
                if (d > 1.2) continue;
                if (kind === 'gaz') c = mixColour(c, light.clone().lerp(new THREE.Color(0xfff3e0), 0.4), (1 - d) * 0.9);
                else c = c.clone().multiplyScalar(d < 0.85 ? 0.75 : d < 1.05 ? 1.25 : 1); // fond sombre, bord clair
            }
            const i = (y * width + x) * 4;
            image.data[i] = c.r * 255;
            image.data[i + 1] = c.g * 255;
            image.data[i + 2] = c.b * 255;
            image.data[i + 3] = 255;
        }
    }
    ctx.putImageData(image, 0, 0);
    const texture = new THREE.CanvasTexture(canvas);
    texture.colorSpace = THREE.SRGBColorSpace;
    return texture;
}

/**
 * Atmosphère : un halo sur le bord de la planète, côté jour seulement ; plus fort quand intensity monte.
 * sun : position du soleil (le même vecteur, que l'appelant déplace quand le système voyage)
 */
export function atmosphereMaterial(colour, sun) {
    return new THREE.ShaderMaterial({
        uniforms: { uColour: { value: new THREE.Color(colour) }, uIntensity: { value: 0.6 }, uSun: { value: sun } },
        vertexShader: `
            varying vec3 vNormal;
            varying vec3 vView;
            varying float vDay;
            uniform vec3 uSun;
            void main() {
                vec4 world = modelMatrix * vec4(position, 1.0);
                vec4 view = viewMatrix * world;
                vNormal = normalize(normalMatrix * normal);
                vView = normalize(-view.xyz);
                vDay = dot(normalize(mat3(modelMatrix) * normal), normalize(uSun - world.xyz)); // > 0 : tourné vers le soleil
                gl_Position = projectionMatrix * view;
            }`,
        fragmentShader: `
            uniform vec3 uColour;
            uniform float uIntensity;
            varying vec3 vNormal;
            varying vec3 vView;
            varying float vDay;
            void main() {
                float rim = pow(1.0 - max(dot(vNormal, vView), 0.0), 3.0);
                float day = 0.1 + 0.9 * smoothstep(-0.3, 0.5, vDay);
                vec3 glow = uColour * rim * day * uIntensity;
                // Opacité = lueur : sur un canvas transparent, une opacité de 1 ferait un anneau sombre opaque
                gl_FragColor = vec4(glow, clamp(max(glow.r, max(glow.g, glow.b)), 0.0, 1.0));
            }`,
        blending: THREE.AdditiveBlending,
        transparent: true,
        depthWrite: false,
    });
}

/** Anneaux (comme Saturne) entre inner et outer : bandes plus ou moins denses, teintées par colour */
export function ringMesh(colour, inner, outer, seed) {
    const rand = random(seed);
    const canvas = Object.assign(document.createElement('canvas'), { width: 256, height: 1 });
    const ctx = canvas.getContext('2d');
    for (let x = 0; x < 256; x++) {
        const density = (0.3 + 0.7 * rand()) * Math.sin((x / 256) * Math.PI) ** 0.5;
        ctx.fillStyle = `rgba(255,255,255,${density * (rand() < 0.08 ? 0.1 : 0.75)})`; // quelques divisions presque vides
        ctx.fillRect(x, 0, 1, 1);
    }
    const geometry = new THREE.RingGeometry(inner, outer, 96, 1);
    // Coordonnées de texture le long du rayon : la bande se lit du bord intérieur à l'extérieur
    const position = geometry.attributes.position, uv = geometry.attributes.uv;
    for (let i = 0; i < position.count; i++) {
        const r = Math.hypot(position.getX(i), position.getY(i));
        uv.setXY(i, (r - inner) / (outer - inner), 0.5);
    }
    const texture = new THREE.CanvasTexture(canvas);
    const ring = new THREE.Mesh(geometry, new THREE.MeshBasicMaterial({
        map: texture, color: mixColour(new THREE.Color(colour), new THREE.Color(0xfff0dd), 0.5),
        transparent: true, side: THREE.DoubleSide, depthWrite: false,
    }));
    ring.rotation.x = -Math.PI / 2;
    return ring;
}

/** Soleil : surface en mouvement (bruit animé), plus chaude au centre, assombrie au bord */
export function sunMaterial() {
    return new THREE.ShaderMaterial({
        uniforms: { uTime: { value: 0 } },
        vertexShader: `
            varying vec3 vPosition;
            varying vec3 vNormal;
            varying vec3 vView;
            void main() {
                vPosition = position;
                vec4 view = modelViewMatrix * vec4(position, 1.0);
                vNormal = normalize(normalMatrix * normal);
                vView = normalize(-view.xyz);
                gl_Position = projectionMatrix * view;
            }`,
        fragmentShader: `
            uniform float uTime;
            varying vec3 vPosition;
            varying vec3 vNormal;
            varying vec3 vView;
            float hash(vec3 p) { return fract(sin(dot(p, vec3(127.1, 311.7, 74.7))) * 43758.5453); }
            float noise(vec3 p) {
                vec3 i = floor(p), f = fract(p);
                f = f * f * (3.0 - 2.0 * f);
                return mix(mix(mix(hash(i), hash(i + vec3(1, 0, 0)), f.x), mix(hash(i + vec3(0, 1, 0)), hash(i + vec3(1, 1, 0)), f.x), f.y),
                           mix(mix(hash(i + vec3(0, 0, 1)), hash(i + vec3(1, 0, 1)), f.x), mix(hash(i + vec3(0, 1, 1)), hash(i + vec3(1, 1, 1)), f.x), f.y), f.z);
            }
            float fbm(vec3 p) {
                float sum = 0.0, amplitude = 0.5;
                for (int i = 0; i < 5; i++) { sum += noise(p) * amplitude; p *= 2.1; amplitude *= 0.5; }
                return sum;
            }
            void main() {
                vec3 p = normalize(vPosition) * 3.0;
                // Granulation qui bouillonne : bruit déformé par un autre bruit, les deux animés
                float flow = fbm(p + vec3(0.0, uTime * 0.05, 0.0));
                float cells = fbm(p * 2.0 + flow * 2.0 + vec3(uTime * 0.03));
                float limb = max(dot(vNormal, vView), 0.0);
                vec3 deep = vec3(0.85, 0.25, 0.05), warm = vec3(1.0, 0.62, 0.15), hot = vec3(1.0, 0.95, 0.75);
                vec3 colour = mix(deep, warm, smoothstep(0.25, 0.6, cells));
                colour = mix(colour, hot, smoothstep(0.55, 0.85, cells));
                colour *= 0.55 + 0.75 * pow(limb, 0.45); // assombrissement centre-bord
                gl_FragColor = vec4(colour * 1.6, 1.0);
            }`,
    });
}
