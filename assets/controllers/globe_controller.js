/* stimulusFetch: 'lazy' */
import { Controller } from '@hotwired/stimulus';
import * as THREE from 'three';
import { OrbitControls } from 'three/addons/controls/OrbitControls.js';
import { dotTexture } from '../cerveau/textures.js';
import { atmosphereMaterial } from '../scripts/planetes.js';
import { basseQualite } from '../scripts/preferences.js';
import { reducedMotion } from '../scripts/black_hole.js';
import terres from '../data/terres.js';

const POLL_MS = 15000;   // nouvelles arrivées : une petite requête toutes les 15 s, seulement si le globe est à l'écran
const FACE = { lat: 42, lon: 5 };  // vue de départ : l'Europe, d'où arrivent la plupart des visiteurs
const FOCUS_S = 4;       // une nouvelle arrivée : le globe se tourne vers elle, puis reprend sa rotation
const PULSE_S = 2.5;     // durée de l'onde qui marque une nouvelle arrivée

/** Point de la sphère de rayon r à cette latitude et longitude (en degrés) */
function surface(lat, lon, r = 1) {
    const phi = THREE.MathUtils.degToRad(90 - lat), theta = THREE.MathUtils.degToRad(lon + 180);
    return new THREE.Vector3(-r * Math.sin(phi) * Math.cos(theta), r * Math.cos(phi), r * Math.sin(phi) * Math.sin(theta));
}

/**
 * /stats : globe des arrivées des 30 derniers jours, placées d'après le fuseau horaire du navigateur
 * (ArriveeController). Une colonne de lumière par fuseau, plus haute s'il y a plus d'arrivées ; une nouvelle
 * arrivée allume une onde. Survol : ville du fuseau et nombre d'arrivées. Décor : la liste sous le globe dit tout.
 */
export default class extends Controller {
    static targets = ['scene', 'label', 'total'];
    static values = { url: String };

    connect() {
        try {
            this.renderer = new THREE.WebGLRenderer({ antialias: !basseQualite(), alpha: true });
        } catch {
            return;
        }
        this.element.hidden = false;
        this.reducedMotion = reducedMotion();
        this.renderer.setPixelRatio(basseQualite() ? 1 : Math.min(window.devicePixelRatio, 2));
        this.sceneTarget.appendChild(this.renderer.domElement);

        this.scene = new THREE.Scene();
        this.camera = new THREE.PerspectiveCamera(35, 1, 0.1, 50);
        this.camera.position.set(0, 0, 4.2);
        this.controls = new OrbitControls(this.camera, this.renderer.domElement);
        Object.assign(this.controls, {
            enableDamping: true, enableZoom: false, enablePan: false,
            autoRotate: !this.reducedMotion, autoRotateSpeed: 0.5,
        });
        if (!window.matchMedia('(pointer: fine)').matches) {
            this.controls.enableRotate = false; // au doigt, la page défile
            this.renderer.domElement.style.touchAction = 'pan-y';
        }

        this.build();
        this.places = new Map(); // fuseau → { count, pillar, ... }
        this.pulses = [];
        this.lastId = 0;

        this.listeners = [];
        const listen = (target, type, handler) => {
            target.addEventListener(type, handler);
            this.listeners.push(() => target.removeEventListener(type, handler));
        };
        this.raycaster = new THREE.Raycaster();
        listen(this.renderer.domElement, 'pointermove', (e) => { this.pointer = e; });
        listen(this.renderer.domElement, 'pointerleave', () => { this.pointer = null; });

        this.resizeObserver = new ResizeObserver(() => this.resize());
        this.resizeObserver.observe(this.sceneTarget);
        this.resize();
        this.visible = false;
        this.intersectionObserver = new IntersectionObserver(([entry]) => {
            this.visible = entry.isIntersecting;
            if (this.visible) this.poll();
        });
        this.intersectionObserver.observe(this.element);

        this.load(true);
        this.timer = new THREE.Timer();
        this.timer.connect(document);
        const loop = () => {
            this.frame = requestAnimationFrame(loop);
            this.timer.update();
            if (!this.visible) return;
            this.animate(Math.min(this.timer.getDelta(), 0.1));
            this.controls.update();
            this.renderer.render(this.scene, this.camera);
        };
        loop();
    }

    disconnect() {
        if (!this.renderer) return;
        cancelAnimationFrame(this.frame);
        clearTimeout(this.pollTimer);
        this.listeners.forEach((remove) => remove());
        this.resizeObserver.disconnect();
        this.intersectionObserver.disconnect();
        this.timer.dispose();
        this.controls.dispose();
        this.scene.traverse((object) => {
            object.geometry?.dispose();
            object.material?.dispose();
        });
        this.texture.dispose();
        this.renderer.dispose();
        this.renderer.domElement.remove();
    }

    build() {
        this.texture = dotTexture();
        // Vue de départ face à FACE : le globe tourne d'abord sur son axe (longitude), puis un groupe parent
        // l'incline vers la caméra (latitude) ; deux groupes, pour que l'ordre des rotations soit sûr
        const tilt = new THREE.Group();
        tilt.rotation.x = THREE.MathUtils.degToRad(FACE.lat);
        this.globe = new THREE.Group();
        this.globe.rotation.y = -THREE.MathUtils.degToRad(FACE.lon + 90);
        tilt.add(this.globe);
        this.scene.add(tilt);

        // Océans : une sphère sombre qui cache les terres de l'autre face
        this.ocean = new THREE.Mesh(new THREE.SphereGeometry(0.995, 64, 48), new THREE.MeshBasicMaterial({ color: 0x0c0a1f }));
        this.globe.add(this.ocean);
        // Terres : des points
        const positions = new Float32Array(terres.length / 2 * 3);
        for (let i = 0; i < terres.length; i += 2) surface(terres[i], terres[i + 1]).toArray(positions, i / 2 * 3);
        const geometry = new THREE.BufferGeometry();
        geometry.setAttribute('position', new THREE.BufferAttribute(positions, 3));
        this.land = new THREE.Points(geometry, new THREE.PointsMaterial({ size: 0.032, map: this.texture, transparent: true, depthWrite: false, color: 0xb9a8ff }));
        this.globe.add(this.land);
        // Atmosphère éclairée du côté de la caméra (cachée sur le thème clair : une lueur n'éclaire pas un fond clair)
        this.atmosphere = new THREE.Mesh(new THREE.SphereGeometry(1.07, 64, 48), atmosphereMaterial('#7f5af0', this.camera.position));
        this.scene.add(this.atmosphere);
    }

    resize() {
        const { clientWidth: w, clientHeight: h } = this.sceneTarget;
        this.renderer.setSize(w, h);
        this.camera.aspect = w / h;
        this.camera.updateProjectionMatrix();
    }

    // ------------------------------------------------
    // Arrivées
    // ------------------------------------------------

    /** Arrivées nouvelles depuis la dernière reçue ; la première fois, celles des 30 jours (sans onde) */
    async load(first = false) {
        try {
            const response = await fetch(`${this.urlValue}?depuis=${this.lastId}`, { headers: { Accept: 'application/json' } });
            if (!response.ok) return;
            const arrivals = await response.json();
            arrivals.forEach((arrival) => this.add(arrival, !first));
            if (arrivals.length) this.lastId = arrivals[arrivals.length - 1].id;
            this.updateTotal();
        } catch { /* hors ligne : on réessaiera */ }
    }

    /** Relève suivante, seulement quand le globe est à l'écran et l'onglet ouvert */
    poll() {
        clearTimeout(this.pollTimer);
        this.pollTimer = setTimeout(async () => {
            if (this.visible && !document.hidden) await this.load();
            if (this.visible) this.poll();
        }, POLL_MS);
    }

    add({ fuseau, lat, lon }, live) {
        let place = this.places.get(fuseau);
        if (!place) {
            const base = surface(lat, lon);
            // Colonne de lumière qui part du sol, vers l'extérieur ; mise à l'échelle selon le nombre d'arrivées
            const pillar = new THREE.Mesh(
                new THREE.CylinderGeometry(0.008, 0.008, 1, 8).translate(0, 0.5, 0),
                new THREE.MeshBasicMaterial({ color: 0xff8906, transparent: true, opacity: 0.9 }),
            );
            pillar.position.copy(base);
            pillar.quaternion.setFromUnitVectors(new THREE.Vector3(0, 1, 0), base.clone().normalize());
            const glow = new THREE.Sprite(new THREE.SpriteMaterial({ map: this.texture, color: 0xffb347, transparent: true, depthWrite: false, blending: THREE.AdditiveBlending }));
            glow.position.copy(surface(lat, lon, 1.005));
            glow.scale.setScalar(0.07);
            this.globe.add(pillar, glow);
            place = { fuseau, count: 0, pillar, glow, height: 0 };
            pillar.userData.place = place;
            this.places.set(fuseau, place);
        }
        place.count++;
        place.height = 0.06 + 0.06 * Math.log2(1 + place.count);
        if (live && !this.reducedMotion) {
            this.pulse(lat, lon);
            // La caméra vient en face de l'arrivée, à la même distance du globe
            const at = this.globe.localToWorld(surface(lat, lon));
            this.focus = { camera: at.normalize().multiplyScalar(this.camera.position.length()), until: FOCUS_S };
        }
    }

    /** Onde qui s'élargit autour d'une nouvelle arrivée */
    pulse(lat, lon) {
        const ring = new THREE.Mesh(
            new THREE.RingGeometry(0.9, 1, 48),
            new THREE.MeshBasicMaterial({ color: 0xffd28a, transparent: true, side: THREE.DoubleSide, depthWrite: false }),
        );
        const at = surface(lat, lon, 1.006);
        ring.position.copy(at);
        ring.lookAt(at.clone().multiplyScalar(2));
        this.globe.add(ring);
        this.pulses.push({ ring, age: 0 });
    }

    updateTotal() {
        if (!this.hasTotalTarget) return;
        const total = [...this.places.values()].reduce((sum, place) => sum + place.count, 0);
        this.totalTarget.textContent = total.toLocaleString(document.documentElement.lang);
    }

    // ------------------------------------------------
    // Animation
    // ------------------------------------------------

    animate(delta) {
        const light = document.documentElement.dataset.theme === 'light';
        this.ocean.material.color.set(light ? 0xe6e0ff : 0x0c0a1f);
        this.land.material.color.set(light ? 0x5b3fd1 : 0xb9a8ff);
        this.atmosphere.visible = !light;
        if (this.focus) {
            this.focus.until -= delta;
            const distance = this.camera.position.length();
            this.camera.position.lerp(this.focus.camera, Math.min(delta * 2, 1)).setLength(distance); // sur la sphère, pas à travers
            if (this.focus.until <= 0) this.focus = null;
        }

        this.places.forEach((place) => {
            // Les colonnes poussent jusqu'à leur hauteur (à l'arrivée sur la page, puis à chaque arrivée)
            const current = place.pillar.scale.y;
            place.pillar.scale.y = this.reducedMotion ? place.height : current + (place.height - current) * Math.min(delta * 3, 1);
        });
        this.pulses = this.pulses.filter((pulse) => {
            pulse.age += delta;
            const t = pulse.age / PULSE_S;
            pulse.ring.scale.setScalar(0.02 + 0.2 * t);
            pulse.ring.material.opacity = 1 - t;
            if (t < 1) return true;
            this.globe.remove(pulse.ring);
            pulse.ring.geometry.dispose();
            pulse.ring.material.dispose();
            return false;
        });
        this.hover();
    }

    /** Ville du fuseau sous le pointeur, et son nombre d'arrivées */
    hover() {
        let place = null;
        if (this.pointer) {
            const rect = this.renderer.domElement.getBoundingClientRect();
            this.raycaster.setFromCamera(new THREE.Vector2(
                ((this.pointer.clientX - rect.left) / rect.width) * 2 - 1, -((this.pointer.clientY - rect.top) / rect.height) * 2 + 1,
            ), this.camera);
            const pillars = [...this.places.values()].flatMap(({ pillar, glow }) => [pillar, glow]);
            const hit = this.raycaster.intersectObjects([this.ocean, ...pillars], false)[0];
            place = hit && hit.object !== this.ocean ? (hit.object.userData.place ?? [...this.places.values()].find((p) => p.glow === hit.object)) : null;
        }
        this.controls.autoRotate = !place && !this.focus && !this.reducedMotion; // le globe s'arrête sous le pointeur
        this.labelTarget.hidden = !place;
        if (!place) return;
        this.labelTarget.textContent = `${place.fuseau.split('/').pop().replaceAll('_', ' ')} · ${place.count.toLocaleString(document.documentElement.lang)}`;
        const at = place.glow.getWorldPosition(new THREE.Vector3()).project(this.camera);
        const x = this.sceneTarget.offsetLeft + (at.x + 1) / 2 * this.sceneTarget.clientWidth;
        const y = this.sceneTarget.offsetTop + (1 - at.y) / 2 * this.sceneTarget.clientHeight;
        this.labelTarget.style.transform = `translate(${x}px, ${y}px) translate(-50%, -160%)`;
    }
}
