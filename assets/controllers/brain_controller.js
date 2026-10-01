/* stimulusFetch: 'lazy' */
import { Controller } from '@hotwired/stimulus';
import * as THREE from 'three';
import { OrbitControls } from 'three/addons/controls/OrbitControls.js';

/**
 * Lobes du cerveau où sont placés les neurones (champ `zone` de src/Data/Competences.php).
 * center : centre de la zone ; radius : étalement des neurones autour.
 */
const ZONES = {
    frontal:   { center: [0, 0.35, 0.7],  radius: 0.25 },
    parietal:  { center: [0, 0.55, -0.2], radius: 0.25 },
    temporal:  { center: [0.4, -0.1, 0.2], radius: 0.18, mirror: true }, // réparti sur les deux côtés
    occipital: { center: [0, 0.2, -0.8],  radius: 0.2 },
    limbique:  { center: [0, 0.05, 0],    radius: 0.15 },
};

const HOME_TARGET = new THREE.Vector3(0, 0, 0);
const CLICK_TOLERANCE_PX = 5;
const FOCUS_DISTANCE = 1.8; // distance caméra ↔ neurone sélectionné

/**
 * Cerveau holographique en nuage de points (Three.js) ; chaque compétence est un
 * neurone cliquable qui ouvre un panneau avec les projets liés.
 * Chargé uniquement sur les pages qui l'utilisent (lazy).
 */
export default class extends Controller {
    static targets = ['canvas', 'fallback', 'tooltip', 'panel', 'panelCategory', 'panelTitle', 'panelProjects'];
    static values = { neurons: Array };

    connect() {
        try {
            this.renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true });
        } catch {
            // Pas de WebGL : on laisse le message de repli visible
            return;
        }
        this.fallbackTarget.hidden = true;

        this.reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

        this.renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
        this.canvasTarget.appendChild(this.renderer.domElement);

        this.scene = new THREE.Scene();
        this.camera = new THREE.PerspectiveCamera(50, 1, 0.01, 100);
        this.camera.position.set(3.2, 1.2, 3.2);
        this.homeDistance = this.camera.position.length();

        this.controls = new OrbitControls(this.camera, this.renderer.domElement);
        this.controls.enableDamping = true;
        this.controls.enablePan = false;
        this.controls.minDistance = 0.6; // assez près pour frôler l'intérieur
        this.controls.maxDistance = 8;
        this.controls.autoRotate = !this.reducedMotion;
        this.controls.autoRotateSpeed = 0.6;
        // L'utilisateur reprend la main : on arrête le déplacement automatique de la caméra
        this.controls.addEventListener('start', () => { this.flight = null; });

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

        this.neurons = this.createNeurons();

        this.raycaster = new THREE.Raycaster();
        this.pointer = new THREE.Vector2();
        this.listen(this.renderer.domElement, 'pointermove', (e) => this.onPointerMove(e));
        this.listen(this.renderer.domElement, 'pointerdown', (e) => { this.downAt = [e.clientX, e.clientY]; });
        this.listen(this.renderer.domElement, 'pointerup', (e) => this.onPointerUp(e));
        this.listen(this.renderer.domElement, 'pointerleave', () => this.hover(null));

        this.resizeObserver = new ResizeObserver(() => this.resize());
        this.resizeObserver.observe(this.canvasTarget);
        this.resize();

        const clock = new THREE.Clock();
        const loop = () => {
            this.frame = requestAnimationFrame(loop);
            this.animate(clock.getElapsedTime());
            this.controls.update();
            this.renderer.render(this.scene, this.camera);
        };
        loop();
    }

    disconnect() {
        if (!this.renderer) return;
        cancelAnimationFrame(this.frame);
        this.resizeObserver.disconnect();
        this.listeners.forEach(([el, type, fn]) => el.removeEventListener(type, fn));
        this.controls.dispose();
        this.scene.traverse((object) => {
            object.geometry?.dispose();
            object.material?.dispose();
        });
        this.texture.dispose();
        this.renderer.dispose();
        this.renderer.domElement.remove();
        this.renderer = null;
    }

    listen(el, type, fn) {
        (this.listeners ??= []).push([el, type, fn]);
        el.addEventListener(type, fn);
    }

    resize() {
        const { clientWidth: w, clientHeight: h } = this.canvasTarget;
        this.renderer.setSize(w, h);
        this.camera.aspect = w / h;
        this.camera.fov = w < h ? 75 : 50; // écran vertical (mobile) : on élargit le champ pour garder le cerveau entier
        this.camera.updateProjectionMatrix();
    }

    // ------------------------------------------------
    // Neurones
    // ------------------------------------------------

    createNeurons() {
        const core = new THREE.IcosahedronGeometry(0.03, 2);
        // Une seule lecture : chaque accès à neuronsValue reparse le JSON (nouveaux objets)
        const neurons = this.neuronsValue;
        const byZone = Object.groupBy(neurons, (n) => n.zone);

        return neurons.map((data, i) => {
            const siblings = byZone[data.zone];
            const position = neuronPosition(ZONES[data.zone], siblings.indexOf(data), siblings.length);
            const color = new THREE.Color(data.couleur);

            const neuron = new THREE.Mesh(core, new THREE.MeshBasicMaterial({ color }));
            neuron.position.copy(position);

            // Halo lumineux : c'est aussi lui qu'on vise à la souris (plus large que le noyau)
            const halo = new THREE.Sprite(new THREE.SpriteMaterial({
                map: this.texture,
                color,
                transparent: true,
                depthWrite: false,
                blending: THREE.AdditiveBlending,
            }));
            halo.scale.setScalar(0.22);
            neuron.add(halo);

            halo.userData = { data, neuron, phase: i * 1.7 };
            this.scene.add(neuron);
            return halo;
        });
    }

    animate(time) {
        const selected = this.selected;
        const t = this.reducedMotion ? 0 : time;

        // Respiration lente de l'hologramme, atténué quand un neurone est sélectionné
        this.brain.material.opacity = (selected ? 0.35 : 0.75) + 0.25 * Math.sin(t * 1.5);

        for (const halo of this.neurons) {
            const active = halo === selected || halo === this.hovered;
            const pulse = 1 + 0.2 * Math.sin(t * 2.5 + halo.userData.phase);
            halo.scale.setScalar((active ? 0.38 : 0.22) * pulse);
            halo.material.opacity = selected && !active ? 0.35 : 1;
        }

        // Vol de caméra vers un neurone (ou retour à la vue d'ensemble)
        if (this.flight) {
            const k = this.reducedMotion ? 1 : 0.07;
            this.controls.target.lerp(this.flight.target, k);
            this.camera.position.lerp(this.flight.camera, k);
            if (this.camera.position.distanceTo(this.flight.camera) < 0.01) this.flight = null;
        }
    }

    pick(event) {
        const rect = this.renderer.domElement.getBoundingClientRect();
        this.pointer.set(
            ((event.clientX - rect.left) / rect.width) * 2 - 1,
            -((event.clientY - rect.top) / rect.height) * 2 + 1,
        );
        this.raycaster.setFromCamera(this.pointer, this.camera);
        return this.raycaster.intersectObjects(this.neurons, false)[0]?.object ?? null;
    }

    onPointerMove(event) {
        const halo = this.pick(event);
        this.hover(halo);
        if (halo) {
            const rect = this.element.getBoundingClientRect();
            this.tooltipTarget.style.left = `${event.clientX - rect.left}px`;
            this.tooltipTarget.style.top = `${event.clientY - rect.top}px`;
        }
    }

    hover(halo) {
        this.hovered = halo;
        this.renderer.domElement.style.cursor = halo ? 'pointer' : '';
        this.tooltipTarget.hidden = !halo;
        if (halo) this.tooltipTarget.textContent = halo.userData.data.nom;
    }

    onPointerUp(event) {
        // Un glisser (rotation) n'est pas un clic
        if (!this.downAt) return;
        const moved = Math.hypot(event.clientX - this.downAt[0], event.clientY - this.downAt[1]);
        this.downAt = null;
        if (moved > CLICK_TOLERANCE_PX) return;

        const halo = this.pick(event);
        if (halo) this.select(halo);
    }

    // ------------------------------------------------
    // Sélection + panneau
    // ------------------------------------------------

    /** Depuis la légende (clavier, lecteur d'écran) : data-brain-name-param */
    selectByName({ params: { name }, target }) {
        const halo = this.neurons?.find((h) => h.userData.data.nom === name);
        if (!halo) return;
        this.select(halo);
        this.panelTarget.focus();
        // Mobile : la légende ouverte masquerait le cerveau
        if (window.matchMedia('(max-width: 576px)').matches) target.closest('details').open = false;
    }

    select(halo) {
        this.selected = halo;
        this.controls.autoRotate = false;

        // La caméra vient se placer devant le neurone, dans l'axe de la vue actuelle
        const target = halo.userData.neuron.position.clone();
        const direction = this.camera.position.clone().sub(this.controls.target).normalize();
        this.flight = { target, camera: target.clone().addScaledVector(direction, FOCUS_DISTANCE) };

        this.fillPanel(halo.userData.data);
    }

    close() {
        if (!this.selected) return;
        this.selected = null;
        this.panelTarget.hidden = true;
        this.controls.autoRotate = !this.reducedMotion;

        const direction = this.camera.position.clone().sub(this.controls.target).normalize();
        this.flight = { target: HOME_TARGET.clone(), camera: direction.multiplyScalar(this.homeDistance) };
    }

    fillPanel({ nom, categorie, couleur, projets }) {
        this.panelTarget.style.setProperty('--neuron', couleur);
        this.panelCategoryTarget.textContent = categorie;
        this.panelTitleTarget.textContent = nom;

        const container = this.panelProjectsTarget;
        container.replaceChildren();
        if (projets.length === 0) {
            const empty = document.createElement('p');
            empty.className = 'brain-panel-empty';
            empty.textContent = 'Pas encore de projet relié à ce neurone.';
            container.append(empty);
        } else {
            const heading = document.createElement('h3');
            heading.textContent = projets.length > 1 ? 'Projets liés' : 'Projet lié';
            const list = document.createElement('ul');
            for (const { titre, url } of projets) {
                const item = document.createElement('li');
                item.append(Object.assign(document.createElement('a'), { href: url, textContent: titre }));
                list.append(item);
            }
            container.append(heading, list);
        }
        this.panelTarget.hidden = false;
    }
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
