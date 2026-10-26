/* stimulusFetch: 'lazy' */
import { Controller } from '@hotwired/stimulus';
import * as THREE from 'three';
import { OrbitControls } from 'three/addons/controls/OrbitControls.js';
import { dotTexture, glowSprite } from '../cerveau/textures.js';
import { atmosphereMaterial, planetTexture, random, ringMesh, sunMaterial } from '../scripts/planetes.js';
import { basseQualite } from '../scripts/preferences.js';
import { reducedMotion } from '../scripts/black_hole.js';

const SUN_RADIUS = 2;
const FIRST_ORBIT = 5.5, ORBIT_GAP = 3.2; // rayon de l'orbite de chaque catégorie
const ORBIT_SPEED = 4;        // vitesse angulaire = ORBIT_SPEED / rayon^1,5 (Kepler) : un tour en 20 s au plus près, 2 min au plus loin
const BELT = 13.5;            // ceinture d'astéroïdes, entre la 3e et la 4e orbite
// Le système file dans la galaxie, en biais par rapport au plan de ses orbites : chaque planète trace une hélice.
// Surtout en travers de l'écran (x), pour que les hélices s'étirent sous les yeux
const DRIFT = new THREE.Vector3(1, 0.45, -0.25).normalize().multiplyScalar(2.2);
const TRAIL_S = 8, TRAIL_STEP = 0.06; // durée des traînées, en secondes, et intervalle entre deux points
const DUST_BOX = 90;          // poussière fixe dans l'espace, dans un cube de ce côté qui suit le soleil
const HOME = new THREE.Vector3(0, 17, 36);  // caméra au repos, par rapport au soleil : tout le système, vu un peu d'en haut
const FLIGHT_SPEED = 3;       // vitesse des vols de caméra (plus grand = plus rapide)
const CLICK_TOLERANCE_PX = 5; // au-delà, c'est un glisser (rotation de la vue), pas un clic

/**
 * Page Compétences : système solaire en 3D. Une orbite par catégorie (sa couleur), une planète par compétence
 * (plus grosse si elle sert dans plus de projets : géante gazeuse, souvent avec des anneaux), une lune par projet.
 * Tout tourne : les planètes autour du soleil et sur elles-mêmes, les lunes autour de leur planète ; et le système
 * file dans l'espace (la caméra le suit) : les planètes laissent leurs traînées en hélice, la poussière défile.
 * Survol : nom ; clic sur une planète : la caméra la rejoint et la suit, sa fiche s'ouvre (Échap ou clic dans
 * le vide pour revenir) ; clic sur une lune : page du projet. Survoler une compétence de la liste allume sa planète.
 * Sans WebGL, la section reste cachée : la liste en dessous dit tout.
 */
export default class SystemeController extends Controller {
    static targets = ['scene', 'label', 'card', 'category', 'name', 'projects', 'brain'];
    static values = { categories: Array, texts: Object };

    connect() {
        try {
            // Transparent : le fond de la page (et ses étoiles) sert d'espace, sans cadre autour du système
            this.renderer = new THREE.WebGLRenderer({ antialias: !basseQualite(), alpha: true });
        } catch {
            return;
        }
        this.element.hidden = false;
        this.reducedMotion = reducedMotion();
        this.renderer.setPixelRatio(basseQualite() ? 1 : Math.min(window.devicePixelRatio, 2));
        this.sceneTarget.appendChild(this.renderer.domElement);

        this.scene = new THREE.Scene();
        this.camera = new THREE.PerspectiveCamera(45, 1, 0.1, 300);
        this.camera.position.copy(HOME);
        this.controls = new OrbitControls(this.camera, this.renderer.domElement);
        Object.assign(this.controls, {
            enableDamping: true,
            enableZoom: false, // la molette fait défiler la page
            enablePan: false,
            autoRotate: !this.reducedMotion,
            autoRotateSpeed: 0.3,
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
            object.material?.map?.dispose();
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
        const low = basseQualite();
        this.texture = dotTexture();
        // Face nuit presque noire sur le ciel sombre ; sur le thème clair, une tache noire : on l'éclaire (animate)
        this.ambient = new THREE.AmbientLight(0xffffff, 0.06);
        this.scene.add(this.ambient);
        // Tout ce qui voyage avec le soleil ; sa position est aussi celle du soleil, pour les atmosphères
        this.system = new THREE.Group();
        this.scene.add(this.system);
        this.system.add(new THREE.PointLight(0xfff1dc, 2.6, 0, 0)); // le soleil éclaire tout le système, sans atténuation

        this.sun = new THREE.Mesh(new THREE.SphereGeometry(SUN_RADIUS, 64, 48), sunMaterial());
        // Lueur du soleil : un halo serré et vif, une couronne large et pâle
        this.sun.add(glowSprite(this.texture, 0xff8a2a, SUN_RADIUS * 5));
        const corona = glowSprite(this.texture, 0xffb347, SUN_RADIUS * 10);
        corona.material.opacity = 0.3;
        this.sun.add(corona);
        this.system.add(this.sun);

        this.belt = this.asteroidBelt(low ? 600 : 1500);
        this.system.add(this.belt);

        this.planets = [];
        this.moons = [];
        const segments = low ? 24 : 48;
        const moonTexture = planetTexture('#9a95a8', 'roche', 3, 128);
        const moonMaterial = new THREE.MeshStandardMaterial({ map: moonTexture, roughness: 1 });

        this.categoriesValue.forEach((categorie, i) => {
            const radius = FIRST_ORBIT + i * ORBIT_GAP;
            const colour = new THREE.Color(categorie.couleur);
            // Chaque orbite légèrement inclinée : on sent le volume quand la vue tourne
            const plane = new THREE.Group();
            plane.rotation.set((i % 2 ? 1 : -1) * 0.04 * i, 0, 0.03 * i);
            plane.add(new THREE.LineLoop(circle(radius), edgeFade(new THREE.LineBasicMaterial({ color: colour, transparent: true, opacity: 0.1 }))));
            this.system.add(plane);

            categorie.competences.forEach((competence, j) => {
                const rand = random(competence.id * 97 + 13);
                const projects = competence.projets.length;
                const size = 0.32 + 0.14 * Math.min(projects, 6);
                const kind = projects >= 4 ? 'gaz' : rand() < 0.5 ? 'roche' : 'glace';

                const pivot = new THREE.Group(); // tourne autour du soleil
                pivot.rotation.y = (j / categorie.competences.length) * Math.PI * 2 + i * 0.9;
                const holder = new THREE.Group(); // la planète sur son orbite
                holder.position.x = radius;
                const tilt = new THREE.Group(); // axe de rotation incliné
                tilt.rotation.z = (rand() - 0.5) * 0.8;
                const body = new THREE.Mesh(
                    new THREE.SphereGeometry(size, segments, segments / 2),
                    new THREE.MeshStandardMaterial({
                        map: planetTexture(colour, kind, competence.id * 31 + 5, size > 0.7 && !low ? 256 : 128),
                        roughness: kind === 'glace' ? 0.45 : 0.95,
                    }),
                );
                const atmosphere = new THREE.Mesh(new THREE.SphereGeometry(size * 1.12, segments, segments / 2), atmosphereMaterial(colour, this.system.position));
                tilt.add(body, atmosphere);
                if (kind === 'gaz' && rand() < 0.75) tilt.add(ringMesh(colour, size * 1.4, size * 2.3, competence.id));
                holder.add(tilt);
                pivot.add(holder);
                plane.add(pivot);
                body.userData = { kind: 'planet', competence, categorie, size, atmosphere, holder };
                this.planets.push({ pivot, body, speed: ORBIT_SPEED / radius ** 1.5, spin: 0.3 + rand() * 0.8 });

                competence.projets.forEach((projet, k) => {
                    const orbit = new THREE.Group();
                    orbit.rotation.set((rand() - 0.5) * 0.8, rand() * Math.PI * 2, 0);
                    const moon = new THREE.Mesh(new THREE.SphereGeometry(0.07 + 0.03 * rand(), 16, 12), moonMaterial);
                    moon.position.x = size * (kind === 'gaz' ? 2.6 : 1.6) + 0.25 * k;
                    moon.userData = { kind: 'moon', projet };
                    orbit.add(moon);
                    holder.add(orbit);
                    this.moons.push({ orbit, moon, speed: 1.2 - 0.12 * k });
                });
            });
        });
        this.pickable = [...this.planets.map(({ body }) => body), ...this.moons.map(({ moon }) => moon)];

        // Traînées : les dernières positions de chaque planète, laissées dans l'espace derrière le système
        const points = Math.ceil(TRAIL_S / TRAIL_STEP) + 2;
        this.trails = this.planets.map(({ body }) => {
            const geometry = new THREE.BufferGeometry();
            geometry.setAttribute('position', new THREE.BufferAttribute(new Float32Array(points * 3), 3));
            geometry.setAttribute('color', new THREE.BufferAttribute(new Float32Array(points * 4), 4)); // l'alpha fait le fondu
            geometry.setDrawRange(0, 0);
            const line = new THREE.Line(geometry, edgeFade(new THREE.LineBasicMaterial({ vertexColors: true, transparent: true, depthWrite: false })));
            line.frustumCulled = false; // ses points changent à chaque image
            this.scene.add(line);
            return { line, holder: body.userData.holder, colour: new THREE.Color(body.userData.categorie.couleur), samples: [] };
        });
        this.system.add(this.sunTrail());
        this.dust = this.spaceDust(low ? 500 : 1400);
        this.scene.add(this.dust);
        this.elapsed = 0;
        this.lastSample = -Infinity;
    }

    /** Sillage du soleil : un trait lumineux droit, qui s'efface en arrière */
    sunTrail() {
        const count = 24, positions = [], colours = [];
        for (let i = 0; i < count; i++) {
            const age = (i / (count - 1)) * TRAIL_S;
            positions.push(...DRIFT.clone().multiplyScalar(-age).toArray());
            colours.push(...new THREE.Color(0xffb347).toArray(), (1 - age / TRAIL_S) ** 2);
        }
        const geometry = new THREE.BufferGeometry();
        geometry.setAttribute('position', new THREE.Float32BufferAttribute(positions, 3));
        geometry.setAttribute('color', new THREE.Float32BufferAttribute(colours, 4));
        return new THREE.Line(geometry, edgeFade(new THREE.LineBasicMaterial({ vertexColors: true, transparent: true, depthWrite: false })));
    }

    /** Poussière fixe dans l'espace, que le système traverse : les grains proches filent, les lointains glissent */
    spaceDust(count) {
        const rand = random(9);
        const positions = new Float32Array(count * 3);
        for (let i = 0; i < positions.length; i++) positions[i] = (rand() - 0.5) * DUST_BOX;
        const geometry = new THREE.BufferGeometry();
        geometry.setAttribute('position', new THREE.BufferAttribute(positions, 3));
        const dust = new THREE.Points(geometry, edgeFade(new THREE.PointsMaterial({
            size: 0.32, color: 0xc8cff5, map: this.texture, transparent: true, opacity: 0.85, depthWrite: false,
        })));
        dust.frustumCulled = false;
        return dust;
    }

    /** Avance d'un pas le voyage du système (et de la caméra qui le suit) : traînées, poussière autour */
    travel(delta) {
        const step = DRIFT.clone().multiplyScalar(delta);
        this.system.position.add(step);
        // Caméra : elle suit le soleil, sauf pendant la visite d'une planète (elle suit alors la planète, voir animate)
        if (!this.following) {
            this.camera.position.add(step);
            this.controls.target.add(step);
            if (this.flight) {
                this.flight.target.add(step);
                this.flight.camera.add(step);
            }
        }
        this.elapsed += delta;
        const now = this.elapsed;
        if (now - this.lastSample >= TRAIL_STEP) {
            this.lastSample = now;
            this.trails.forEach((trail) => {
                trail.samples.unshift({ at: trail.holder.getWorldPosition(new THREE.Vector3()), t: now });
                while (trail.samples.length && now - trail.samples[trail.samples.length - 1].t > TRAIL_S) trail.samples.pop();
            });
        }
        this.trails.forEach(({ line, holder, colour, samples }) => {
            const position = line.geometry.attributes.position, color = line.geometry.attributes.color;
            const write = (i, point, age) => {
                position.setXYZ(i, point.x, point.y, point.z);
                color.setXYZW(i, colour.r, colour.g, colour.b, (1 - age / TRAIL_S) ** 1.5 * 0.8);
            };
            write(0, holder.getWorldPosition(new THREE.Vector3()), 0); // part de la planète, sans à-coup
            samples.forEach((sample, i) => write(i + 1, sample.at, now - sample.t));
            line.geometry.setDrawRange(0, samples.length + 1);
            position.needsUpdate = true;
            color.needsUpdate = true;
        });
        // Un grain laissé trop loin derrière le soleil revient devant : le cube de poussière l'accompagne
        const dust = this.dust.geometry.attributes.position, centre = this.system.position;
        for (let i = 0; i < dust.array.length; i++) {
            const c = centre.getComponent(i % 3), v = dust.array[i];
            if (v < c - DUST_BOX / 2) dust.array[i] = v + DUST_BOX;
            else if (v > c + DUST_BOX / 2) dust.array[i] = v - DUST_BOX;
        }
        dust.needsUpdate = true;
    }

    /** Ceinture de petits cailloux gris-brun, en anneau épais */
    asteroidBelt(count) {
        const rand = random(42);
        const positions = new Float32Array(count * 3), colours = new Float32Array(count * 3);
        for (let i = 0; i < count; i++) {
            const a = rand() * Math.PI * 2, r = BELT + (rand() + rand() - 1) * 0.9;
            positions.set([Math.cos(a) * r, (rand() - 0.5) * 0.35, Math.sin(a) * r], i * 3);
            const shade = 0.35 + rand() * 0.4;
            colours.set([shade, shade * 0.9, shade * 0.8], i * 3);
        }
        const geometry = new THREE.BufferGeometry();
        geometry.setAttribute('position', new THREE.BufferAttribute(positions, 3));
        geometry.setAttribute('color', new THREE.BufferAttribute(colours, 3));
        return new THREE.Points(geometry, new THREE.PointsMaterial({
            size: 0.09, map: this.texture, vertexColors: true, transparent: true, depthWrite: false,
        }));
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

    /** Vol jusqu'à la planète, fiche ouverte ; la caméra la suit ensuite sur son orbite */
    select(body) {
        this.selected = body;
        this.controls.autoRotate = false;
        const { competence, categorie, size, holder } = body.userData;

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

        const target = holder.getWorldPosition(new THREE.Vector3());
        this.following = target.clone();
        // Caméra de trois quarts, côté soleil et un peu au-dessus : on voit la face éclairée et son terminateur
        const outward = target.clone().sub(this.system.position).setY(0).normalize();
        const along = new THREE.Vector3(0, 1, 0).cross(outward); // sens de la marche sur l'orbite
        const camera = target.clone()
            .add(outward.multiplyScalar(-0.55).add(along.multiplyScalar(0.85)).normalize().multiplyScalar(size * 6 + 3))
            .add(new THREE.Vector3(0, size * 3 + 1.5, 0));
        this.flight = { target, camera };
    }

    close() {
        this.selected = null;
        this.following = null;
        this.cardTarget.hidden = true;
        this.controls.autoRotate = !this.reducedMotion;
        this.flight = { target: this.system.position.clone(), camera: HOME.clone().add(this.system.position) };
    }

    // ------------------------------------------------
    // Animation
    // ------------------------------------------------

    animate(delta) {
        this.ambient.intensity = document.documentElement.dataset.theme === 'light' ? 0.9 : 0.06;
        if (!this.reducedMotion) {
            this.planets.forEach(({ pivot, body, speed, spin }) => {
                pivot.rotation.y += speed * delta;
                body.rotation.y += spin * delta;
            });
            this.moons.forEach(({ orbit, speed }) => { orbit.rotation.y += speed * delta; });
            this.belt.rotation.y += 0.015 * delta;
            this.sun.rotation.y += 0.03 * delta;
            this.sun.material.uniforms.uTime.value += delta;
            this.travel(delta);
        }
        // Planète survolée, choisie ou allumée depuis la liste : son atmosphère s'illumine
        this.planets.forEach(({ body }) => {
            const lit = body === this.hovered || body === this.selected || body === this.highlighted;
            const intensity = body.userData.atmosphere.material.uniforms.uIntensity;
            intensity.value += ((lit ? 1.5 : 0.7) - intensity.value) * Math.min(delta * 8, 1);
        });
        // La planète choisie avance sur son orbite : caméra, cible et vol avancent avec elle
        if (this.following) {
            const now = this.selected.userData.holder.getWorldPosition(new THREE.Vector3());
            const step = now.clone().sub(this.following);
            this.following.copy(now);
            this.camera.position.add(step);
            this.controls.target.add(step);
            if (this.flight) {
                this.flight.target.add(step);
                this.flight.camera.add(step);
            }
        }
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
        // Repère de la section : la scène y est décalée (texte d'aide au-dessus, pleine largeur de la fenêtre)
        const x = this.sceneTarget.offsetLeft + (at.x + 1) / 2 * this.sceneTarget.clientWidth;
        const y = this.sceneTarget.offsetTop + (1 - at.y) / 2 * this.sceneTarget.clientHeight;
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

/**
 * Efface ce que dessine le matériau près des bords du canvas (traînées, poussière, orbites) :
 * sans cadre autour de la scène, un trait coupé net au bord se verrait
 */
function edgeFade(material) {
    material.onBeforeCompile = (shader) => {
        shader.vertexShader = shader.vertexShader
            .replace('#include <common>', '#include <common>\nvarying vec2 vScreen;')
            .replace('#include <project_vertex>', '#include <project_vertex>\nvScreen = gl_Position.xy / gl_Position.w;');
        shader.fragmentShader = shader.fragmentShader
            .replace('#include <common>', '#include <common>\nvarying vec2 vScreen;')
            .replace('#include <opaque_fragment>', 'diffuseColor.a *= (1.0 - smoothstep(0.7, 1.0, abs(vScreen.x))) * (1.0 - smoothstep(0.55, 1.0, abs(vScreen.y)));\n#include <opaque_fragment>');
    };
    return material;
}
