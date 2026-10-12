/* stimulusFetch: 'lazy' */
import { Controller } from '@hotwired/stimulus';
import * as THREE from 'three';
import { OrbitControls } from 'three/addons/controls/OrbitControls.js';
import { dotTexture, glowSprite } from '../cerveau/textures.js';
import { basseQualite } from '../scripts/preferences.js';
import { reducedMotion } from '../scripts/black_hole.js';

const SUN_RADIUS = 2;
const FIRST_ORBIT = 5.5, ORBIT_GAP = 3.2; // rayon de l'orbite de chaque catégorie
const HOME = new THREE.Vector3(0, 17, 36);  // caméra au repos : tout le système, vu un peu d'en haut
const ORIGIN = new THREE.Vector3();
const FLIGHT_SPEED = 3;       // vitesse des vols de caméra (plus grand = plus rapide)
const CLICK_TOLERANCE_PX = 5; // au-delà, c'est un glisser (rotation de la vue), pas un clic

/**
 * Page Compétences : système solaire en 3D. Une orbite par catégorie (sa couleur), une planète par compétence
 * (plus grosse si elle sert dans plus de projets), une lune par projet. Survol : nom ; clic sur une planète :
 * la caméra s'en approche et sa fiche s'ouvre (Échap ou clic dans le vide pour revenir) ; clic sur une lune :
 * page du projet. Survoler une compétence de la liste allume sa planète.
 * Sans WebGL, la section reste cachée : la liste en dessous dit tout.
 */
export default class extends Controller {
    static targets = ['scene', 'label', 'card', 'category', 'name', 'projects', 'brain'];
    static values = { categories: Array, texts: Object };

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
        this.camera = new THREE.PerspectiveCamera(45, 1, 0.1, 200);
        this.camera.position.copy(HOME);
        this.controls = new OrbitControls(this.camera, this.renderer.domElement);
        Object.assign(this.controls, {
            enableDamping: true,
            enableZoom: false, // la molette fait défiler la page
            enablePan: false,
            autoRotate: !this.reducedMotion,
            autoRotateSpeed: 0.4,
            minPolarAngle: 0.25,
            maxPolarAngle: 1.45,
        });
        // Au doigt, faire tourner la vue empêcherait de faire défiler la page : on garde le défilement, le toucher sélectionne
        if (!window.matchMedia('(pointer: fine)').matches) {
            this.controls.enableRotate = false;
            this.renderer.domElement.style.touchAction = 'pan-y';
        }
        this.controls.addEventListener('start', () => { this.flight = null; });

        this.build();
        this.bind();

        this.resizeObserver = new ResizeObserver(() => this.resize());
        this.resizeObserver.observe(this.sceneTarget);
        this.resize();
        // Hors de l'écran, rien ne tourne : pas de calcul pour rien
        this.visible = false;
        this.intersectionObserver = new IntersectionObserver(([entry]) => { this.visible = entry.isIntersecting; });
        this.intersectionObserver.observe(this.element);

        this.timer = new THREE.Timer();
        this.timer.connect(document);
        const loop = () => {
            this.frame = requestAnimationFrame(loop);
            this.timer.update();
            if (!this.visible) return;
            this.animate(Math.min(this.timer.getDelta(), 0.1));
            this.controls.update();
            this.renderer.render(this.scene, this.camera);
            this.placeLabel();
        };
        loop();
    }

    disconnect() {
        if (!this.renderer) return;
        cancelAnimationFrame(this.frame);
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

    // ------------------------------------------------
    // Construction
    // ------------------------------------------------

    build() {
        this.texture = dotTexture();
        this.scene.add(new THREE.AmbientLight(0xffffff, 0.35));
        this.scene.add(new THREE.PointLight(0xfff0d0, 3, 0, 0)); // le soleil éclaire tout le système, sans atténuation

        const sun = new THREE.Mesh(new THREE.SphereGeometry(SUN_RADIUS, 48, 32), new THREE.MeshBasicMaterial({ color: 0xffc46b }));
        sun.add(glowSprite(this.texture, 0xff8906, SUN_RADIUS * 5));
        this.scene.add(sun);

        this.planets = [];
        this.moons = [];
        const segments = basseQualite() ? 16 : 32;
        const moonGeometry = new THREE.SphereGeometry(0.09, segments / 2, segments / 4);
        const moonMaterial = new THREE.MeshStandardMaterial({ color: 0xe6e0ff, emissive: 0x6b5bb5, emissiveIntensity: 0.4 });

        this.categoriesValue.forEach((categorie, i) => {
            const radius = FIRST_ORBIT + i * ORBIT_GAP;
            const colour = new THREE.Color(categorie.couleur);
            // Chaque orbite légèrement inclinée : on sent le volume quand la vue tourne
            const plane = new THREE.Group();
            plane.rotation.set((i % 2 ? 1 : -1) * 0.04 * i, 0, 0.03 * i);
            plane.add(new THREE.LineLoop(circle(radius), new THREE.LineBasicMaterial({ color: colour, transparent: true, opacity: 0.35 })));
            this.scene.add(plane);

            categorie.competences.forEach((competence, j) => {
                const size = 0.35 + 0.13 * Math.min(competence.projets.length, 6);
                const pivot = new THREE.Group(); // tourne autour du soleil
                pivot.rotation.y = (j / categorie.competences.length) * Math.PI * 2 + i * 0.9;
                const body = new THREE.Mesh(
                    new THREE.SphereGeometry(size, segments, segments / 2),
                    new THREE.MeshStandardMaterial({ color: colour, emissive: colour, emissiveIntensity: 0.25, roughness: 0.55 }),
                );
                body.position.x = radius;
                const halo = glowSprite(this.texture, colour, size * 4);
                halo.material.opacity = 0.3;
                body.add(halo);
                body.userData = { kind: 'planet', competence, categorie, size, halo };
                pivot.add(body);
                plane.add(pivot);
                this.planets.push({ pivot, body, speed: 0.9 / radius ** 1.5 });

                competence.projets.forEach((projet, k) => {
                    const orbit = new THREE.Group();
                    orbit.rotation.set((Math.random() - 0.5) * 0.8, Math.random() * Math.PI * 2, 0);
                    const moon = new THREE.Mesh(moonGeometry, moonMaterial);
                    moon.position.x = size + 0.3 + 0.2 * k;
                    moon.userData = { kind: 'moon', projet };
                    orbit.add(moon);
                    body.add(orbit);
                    this.moons.push({ orbit, moon, speed: 1.6 - 0.15 * k });
                });
            });
        });
        this.pickable = [...this.planets.map(({ body }) => body), ...this.moons.map(({ moon }) => moon)];
    }

    bind() {
        this.listeners = [];
        const listen = (target, type, handler) => {
            target.addEventListener(type, handler);
            this.listeners.push(() => target.removeEventListener(type, handler));
        };
        const canvas = this.renderer.domElement;
        this.raycaster = new THREE.Raycaster();
        listen(canvas, 'pointermove', (e) => { this.hovered = this.pick(e); canvas.style.cursor = this.hovered ? 'pointer' : ''; });
        listen(canvas, 'pointerleave', () => { this.hovered = null; });
        listen(canvas, 'pointerdown', (e) => { this.down = [e.clientX, e.clientY]; });
        listen(canvas, 'pointerup', (e) => {
            if (this.down && Math.hypot(e.clientX - this.down[0], e.clientY - this.down[1]) < CLICK_TOLERANCE_PX) this.click(e);
            this.down = null;
        });
        listen(document, 'keydown', (e) => { if (e.key === 'Escape' && this.selected) this.close(); });
        // Liste des compétences sous le système : la survoler allume la planète
        listen(document, 'pointerover', (e) => {
            const id = e.target.closest?.('.skill-card')?.dataset.competence;
            this.highlighted = this.planets.find(({ body }) => String(body.userData.competence.id) === id)?.body ?? null;
        });
    }

    resize() {
        const { clientWidth: w, clientHeight: h } = this.sceneTarget;
        this.renderer.setSize(w, h);
        this.camera.aspect = w / h;
        this.camera.fov = w < h ? 70 : 45; // écran vertical : champ plus large pour garder tout le système
        this.camera.updateProjectionMatrix();
    }

    // ------------------------------------------------
    // Interaction
    // ------------------------------------------------

    /** Planète ou lune sous le pointeur */
    pick(e) {
        const rect = this.renderer.domElement.getBoundingClientRect();
        const pointer = new THREE.Vector2(((e.clientX - rect.left) / rect.width) * 2 - 1, -((e.clientY - rect.top) / rect.height) * 2 + 1);
        this.raycaster.setFromCamera(pointer, this.camera);
        return this.raycaster.intersectObjects(this.pickable, false)[0]?.object ?? null;
    }

    click(e) {
        const object = this.pick(e);
        if (!object) {
            if (this.selected) this.close();
        } else if (object.userData.kind === 'moon') {
            window.location.href = object.userData.projet.url;
        } else {
            this.select(object);
        }
    }

    /** Vol jusqu'à la planète, fiche ouverte ; les orbites s'arrêtent le temps de la visite */
    select(body) {
        this.selected = body;
        this.controls.autoRotate = false;
        const { competence, categorie, size } = body.userData;

        this.categoryTarget.textContent = categorie.nom;
        this.categoryTarget.style.color = categorie.couleur;
        this.nameTarget.textContent = competence.nom;
        this.projectsTarget.replaceChildren(...(competence.projets.length
            ? competence.projets.map((projet) => {
                const link = Object.assign(document.createElement('a'), { href: projet.url, textContent: projet.titre, tabIndex: -1 });
                const item = document.createElement('li');
                item.append(link);
                return item;
            })
            : [Object.assign(document.createElement('li'), { textContent: this.textsValue.aucun, className: 'systeme-aucun' })]));
        this.brainTarget.href = competence.cerveau;
        this.brainTarget.textContent = `${this.textsValue.cerveau} →`;
        this.cardTarget.hidden = false;

        const target = body.getWorldPosition(new THREE.Vector3());
        // Caméra un peu au-dehors de l'orbite et au-dessus, la planète au centre
        const outward = target.clone().setY(0).normalize();
        const camera = target.clone().add(outward.multiplyScalar(size * 6 + 3)).add(new THREE.Vector3(0, size * 3 + 1.5, 0));
        this.flight = { target, camera };
    }

    close() {
        this.selected = null;
        this.cardTarget.hidden = true;
        this.controls.autoRotate = !this.reducedMotion;
        this.flight = { target: ORIGIN.clone(), camera: HOME.clone() };
    }

    // ------------------------------------------------
    // Animation
    // ------------------------------------------------

    animate(delta) {
        if (!this.reducedMotion) {
            if (!this.selected) this.planets.forEach(({ pivot, speed }) => { pivot.rotation.y += speed * delta; });
            this.moons.forEach(({ orbit, speed }) => { orbit.rotation.y += speed * delta; });
        }
        // Planète survolée, choisie ou allumée depuis la liste : plus vive
        this.planets.forEach(({ body }) => {
            const lit = body === this.hovered || body === this.selected || body === this.highlighted;
            const material = body.material, halo = body.userData.halo.material;
            material.emissiveIntensity += ((lit ? 0.55 : 0.25) - material.emissiveIntensity) * Math.min(delta * 8, 1);
            halo.opacity += ((lit ? 0.8 : 0.3) - halo.opacity) * Math.min(delta * 8, 1);
        });
        if (this.flight) {
            const k = this.reducedMotion ? 1 : 1 - Math.exp(-FLIGHT_SPEED * delta);
            this.controls.target.lerp(this.flight.target, k);
            this.camera.position.lerp(this.flight.camera, k);
            if (this.camera.position.distanceTo(this.flight.camera) < 0.01) this.flight = null;
        }
    }

    /** Nom de ce qui est survolé, au-dessus de l'objet à l'écran */
    placeLabel() {
        const object = this.hovered;
        this.labelTarget.hidden = !object;
        if (!object) return;
        const { kind } = object.userData;
        this.labelTarget.textContent = kind === 'moon' ? object.userData.projet.titre : object.userData.competence.nom;
        const at = object.getWorldPosition(new THREE.Vector3()).project(this.camera);
        const x = (at.x + 1) / 2 * this.sceneTarget.clientWidth, y = (1 - at.y) / 2 * this.sceneTarget.clientHeight;
        this.labelTarget.style.transform = `translate(${x}px, ${y}px) translate(-50%, -150%)`;
    }
}

/** Cercle de rayon r dans le plan horizontal, pour tracer une orbite */
function circle(r, segments = 128) {
    const points = Array.from({ length: segments }, (_, i) => {
        const a = (i / segments) * Math.PI * 2;
        return new THREE.Vector3(Math.cos(a) * r, 0, Math.sin(a) * r);
    });
    return new THREE.BufferGeometry().setFromPoints(points);
}
