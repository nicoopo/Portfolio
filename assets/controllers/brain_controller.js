/* stimulusFetch: 'lazy' */
import { Controller } from '@hotwired/stimulus';
import * as THREE from 'three';
import { OrbitControls } from 'three/addons/controls/OrbitControls.js';
import { dotTexture } from '../cerveau/textures.js';
import { createBrain } from '../cerveau/brain.js';
import { createNeurons } from '../cerveau/neurons.js';
import { Synapses } from '../cerveau/synapses.js';
import { createNebulae } from '../cerveau/nebulae.js';

const HOME_TARGET = new THREE.Vector3(0, 0, 0);
const CLICK_TOLERANCE_PX = 5;
const FOCUS_DISTANCE = { neuron: 1.8, nebula: 5 }; // distance caméra ↔ élément sélectionné

/**
 * Scène du cerveau : interaction (caméra, survol, clic, panneau).
 * La 3D elle-même est dans assets/cerveau/ (cerveau, neurones, synapses, nébuleuses).
 * Chargé uniquement sur les pages qui l'utilisent (lazy).
 */
export default class extends Controller {
    static targets = ['canvas', 'fallback', 'tooltip', 'panel', 'panelCategory', 'panelTitle', 'panelBody'];
    static values = { neurons: Array, passions: Array };

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
        this.controls.maxDistance = 14;  // assez loin pour voir toutes les nébuleuses
        this.controls.autoRotate = !this.reducedMotion;
        this.controls.autoRotateSpeed = 0.6;
        // L'utilisateur reprend la main : on arrête le déplacement automatique de la caméra
        this.controls.addEventListener('start', () => { this.flight = null; });

        this.texture = dotTexture();
        this.brain = createBrain(this.texture);

        this.neurons = createNeurons(this.neuronsValue, this.texture);
        this.synapses = new Synapses(this.neurons, this.texture);
        this.nebulae = createNebulae(this.passionsValue, this.texture);
        // Tout ce qui se clique : neurones et nébuleuses
        this.clickables = [...this.neurons, ...this.nebulae.targets];

        this.scene.add(this.brain, this.synapses.object, this.nebulae.object, ...this.neurons.map((h) => h.userData.anchor));

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
            this.animate(this.reducedMotion ? 0 : clock.getElapsedTime());
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
    // Animation
    // ------------------------------------------------

    animate(time) {
        const selected = this.selected;
        const neighbors = selected?.userData.kind === 'neuron' ? this.synapses.neighborsOf(selected) : new Set();

        // Respiration lente de l'hologramme, atténué quand quelque chose est sélectionné
        this.brain.material.opacity = (selected ? 0.35 : 0.75) + 0.25 * Math.sin(time * 1.5);

        for (const halo of this.neurons) {
            const active = halo === selected || halo === this.hovered;
            const pulse = 1 + 0.2 * Math.sin(time * 2.5 + halo.userData.phase);
            halo.scale.setScalar((active ? 0.38 : 0.22) * pulse);
            // Les neurones reliés au neurone sélectionné restent allumés
            halo.material.opacity = !selected || active || neighbors.has(halo) ? 1 : 0.25;
        }

        for (const core of this.nebulae.targets) {
            const active = core === selected || core === this.hovered;
            core.scale.setScalar((active ? 2.2 : 1.6) * (1 + 0.1 * Math.sin(time + core.userData.phase)));
        }

        this.synapses.update(time);
        this.nebulae.update(time);

        // Vol de caméra vers l'élément sélectionné (ou retour à la vue d'ensemble)
        if (this.flight) {
            const k = this.reducedMotion ? 1 : 0.07;
            this.controls.target.lerp(this.flight.target, k);
            this.camera.position.lerp(this.flight.camera, k);
            if (this.camera.position.distanceTo(this.flight.camera) < 0.01) this.flight = null;
        }
    }

    // ------------------------------------------------
    // Souris
    // ------------------------------------------------

    pick(event) {
        const rect = this.renderer.domElement.getBoundingClientRect();
        this.pointer.set(
            ((event.clientX - rect.left) / rect.width) * 2 - 1,
            -((event.clientY - rect.top) / rect.height) * 2 + 1,
        );
        this.raycaster.setFromCamera(this.pointer, this.camera);
        return this.raycaster.intersectObjects(this.clickables, false)[0]?.object ?? null;
    }

    onPointerMove(event) {
        const target = this.pick(event);
        this.hover(target);
        if (target) {
            const rect = this.element.getBoundingClientRect();
            this.tooltipTarget.style.left = `${event.clientX - rect.left}px`;
            this.tooltipTarget.style.top = `${event.clientY - rect.top}px`;
        }
    }

    hover(target) {
        this.hovered = target;
        this.renderer.domElement.style.cursor = target ? 'pointer' : '';
        this.tooltipTarget.hidden = !target;
        if (target) this.tooltipTarget.textContent = target.userData.data.nom;
    }

    onPointerUp(event) {
        // Un glisser (rotation) n'est pas un clic
        if (!this.downAt) return;
        const moved = Math.hypot(event.clientX - this.downAt[0], event.clientY - this.downAt[1]);
        this.downAt = null;
        if (moved > CLICK_TOLERANCE_PX) return;

        const target = this.pick(event);
        if (target) this.select(target);
    }

    // ------------------------------------------------
    // Sélection + panneau
    // ------------------------------------------------

    /** Depuis la légende ou le panneau (clavier, lecteur d'écran) : data-brain-name-param */
    selectByName({ params: { name }, target }) {
        const found = this.clickables?.find((t) => t.userData.data.nom === name);
        if (!found) return;
        this.select(found);
        this.panelTarget.focus();
        // Mobile : la légende ouverte masquerait le cerveau
        if (window.matchMedia('(max-width: 576px)').matches) {
            const details = target.closest('details');
            if (details) details.open = false;
        }
    }

    select(target) {
        this.selected = target;
        this.controls.autoRotate = false;
        this.synapses.highlight(target.userData.kind === 'neuron' ? target : null);

        // La caméra vient se placer devant l'élément, dans l'axe de la vue actuelle
        const position = target.userData.anchor.getWorldPosition(new THREE.Vector3());
        const direction = this.camera.position.clone().sub(this.controls.target).normalize();
        this.flight = {
            target: position,
            camera: position.clone().addScaledVector(direction, FOCUS_DISTANCE[target.userData.kind]),
        };

        if (target.userData.kind === 'neuron') this.fillNeuronPanel(target);
        else this.fillNebulaPanel(target.userData.data);
        this.panelTarget.hidden = false;
    }

    close() {
        if (!this.selected) return;
        this.selected = null;
        this.panelTarget.hidden = true;
        this.controls.autoRotate = !this.reducedMotion;
        this.synapses.highlight(null);

        const direction = this.camera.position.clone().sub(this.controls.target).normalize();
        this.flight = { target: HOME_TARGET.clone(), camera: direction.multiplyScalar(this.homeDistance) };
    }

    fillNeuronPanel(halo) {
        const { nom, categorie, couleur, projets } = halo.userData.data;
        this.fillPanelHeader(categorie, nom, couleur);
        const body = this.panelBodyTarget;

        if (projets.length === 0) {
            body.append(element('p', { className: 'brain-panel-empty', textContent: 'Pas encore de projet relié à ce neurone.' }));
        } else {
            const list = element('ul');
            for (const { titre, url } of projets) {
                list.append(element('li', {}, element('a', { href: url, textContent: titre })));
            }
            body.append(element('h3', { textContent: projets.length > 1 ? 'Projets liés' : 'Projet lié' }), list);
        }

        // Synapses : on peut sauter de neurone en neurone
        const neighbors = [...this.synapses.neighborsOf(halo)];
        if (neighbors.length > 0) {
            const links = element('ul', { className: 'brain-panel-synapses' });
            for (const neighbor of neighbors) {
                const button = element('button', { type: 'button', textContent: neighbor.userData.data.nom });
                button.dataset.action = 'brain#selectByName';
                button.dataset.brainNameParam = neighbor.userData.data.nom;
                button.style.setProperty('--dot', neighbor.userData.data.couleur);
                links.append(element('li', {}, button));
            }
            body.append(element('h3', { textContent: 'Connecté à' }), links);
        }
    }

    fillNebulaPanel({ nom, couleur, description }) {
        this.fillPanelHeader('Passion', nom, couleur);
        this.panelBodyTarget.append(element('p', { textContent: description }));
    }

    fillPanelHeader(category, title, color) {
        this.panelTarget.style.setProperty('--neuron', color);
        this.panelCategoryTarget.textContent = category;
        this.panelTitleTarget.textContent = title;
        this.panelBodyTarget.replaceChildren();
    }
}

/** Petit utilitaire DOM : element('a', { href, textContent }, ...enfants). */
function element(tag, properties = {}, ...children) {
    const el = Object.assign(document.createElement(tag), properties);
    el.append(...children);
    return el;
}
