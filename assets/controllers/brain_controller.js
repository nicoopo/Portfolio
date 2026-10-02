/* stimulusFetch: 'lazy' */
import { Controller } from '@hotwired/stimulus';
import * as THREE from 'three';
import { OrbitControls } from 'three/addons/controls/OrbitControls.js';
import { EffectComposer } from 'three/addons/postprocessing/EffectComposer.js';
import { RenderPass } from 'three/addons/postprocessing/RenderPass.js';
import { UnrealBloomPass } from 'three/addons/postprocessing/UnrealBloomPass.js';
import { OutputPass } from 'three/addons/postprocessing/OutputPass.js';
import { dotTexture } from '../cerveau/textures.js';
import { createBrain } from '../cerveau/brain.js';
import { createNeurons } from '../cerveau/neurons.js';
import { Synapses } from '../cerveau/synapses.js';
import { createNebulae } from '../cerveau/nebulae.js';
import { createSouvenirs } from '../cerveau/souvenirs.js';
import { Ambiance } from '../cerveau/ambiance.js';

const HOME_TARGET = new THREE.Vector3(0, 0, 0);
const CLICK_TOLERANCE_PX = 5;
const FOCUS_DISTANCE = { neuron: 1.8, nebula: 5, souvenir: 1.8 }; // distance caméra ↔ élément sélectionné
const TOUR_STOPS = 6;       // neurones visités : ceux qui ont le plus de projets
const TOUR_PAUSE_MS = 6000; // temps passé sur chaque neurone
const VIEW_PREFIX = 'vue='; // lien vers une vue : /cerveau#vue=x,y,z;x,y,z

/**
 * Scène du cerveau : interaction (caméra, survol, clic, panneau).
 * La 3D elle-même est dans assets/cerveau/ (cerveau, neurones, synapses, nébuleuses, souvenirs).
 * Chargé uniquement sur les pages qui l'utilisent (lazy).
 */
export default class extends Controller {
    static targets = ['canvas', 'fallback', 'tooltip', 'panel', 'panelCategory', 'panelTitle', 'panelBody', 'tourButton', 'fullscreenButton', 'search', 'shareButton', 'soundButton'];
    static values = { neurons: Array, passions: Array, souvenirs: Array };

    connect() {
        try {
            this.renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true });
        } catch {
            // Pas de WebGL : on laisse le message de repli visible
            return;
        }
        this.fallbackTarget.hidden = true;
        this.tourButtonTarget.hidden = false;
        // Pas de plein écran possible (ex. Safari sur iPhone) : pas de bouton
        this.fullscreenButtonTarget.hidden = !document.fullscreenEnabled;
        this.shareButtonTarget.hidden = false;
        this.soundButtonTarget.hidden = !window.AudioContext;

        this.reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

        this.renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
        this.canvasTarget.appendChild(this.renderer.domElement);

        this.scene = new THREE.Scene();
        this.camera = new THREE.PerspectiveCamera(50, 1, 0.01, 100);
        this.camera.position.set(3.2, 1.2, 3.2);
        this.homeDistance = this.camera.position.length();

        this.controls = new OrbitControls(this.camera, this.renderer.domElement);
        this.controls.enableDamping = true;
        this.controls.listenToKeyEvents(window); // flèches du clavier : se déplacer
        this.controls.minDistance = 0.6; // assez près pour frôler l'intérieur
        this.controls.maxDistance = 30;  // assez loin pour voir tout le système de nébuleuses
        this.controls.autoRotate = !this.reducedMotion;
        this.controls.autoRotateSpeed = 0.6;
        // L'utilisateur reprend la main : on arrête le déplacement automatique de la caméra
        this.controls.addEventListener('start', () => {
            this.flight = null;
            this.stopTour();
            // La vue partagée n'est plus celle qu'on regarde
            if (location.hash.startsWith(`#${VIEW_PREFIX}`)) history.replaceState(null, '', location.pathname);
        });

        this.texture = dotTexture();
        this.brain = createBrain(this.texture);

        this.neurons = createNeurons(this.neuronsValue, this.texture);
        this.synapses = new Synapses(this.neurons, this.texture);
        this.nebulae = createNebulae(this.passionsValue, this.texture);
        this.souvenirs = createSouvenirs(this.souvenirsValue, this.texture);
        // Tout ce qui se clique : neurones, nébuleuses et souvenirs
        this.clickables = [...this.neurons, ...this.nebulae.targets, ...this.souvenirs.targets];

        this.scene.add(this.brain, this.synapses.object, this.nebulae.object, this.souvenirs.object, ...this.neurons.map((h) => h.userData.anchor));
        // Neurones, synapses et souvenirs toujours dessinés après le corps sombre du cerveau
        for (const object of [this.synapses.object, this.souvenirs.object, ...this.neurons.map((h) => h.userData.anchor)]) {
            object.traverse((child) => { child.renderOrder = 1; });
        }

        this.raycaster = new THREE.Raycaster();
        this.pointer = new THREE.Vector2();
        this.listen(this.renderer.domElement, 'pointermove', (e) => this.onPointerMove(e));
        this.listen(this.renderer.domElement, 'pointerdown', (e) => { this.downAt = [e.clientX, e.clientY]; });
        this.listen(this.renderer.domElement, 'pointerup', (e) => this.onPointerUp(e));
        this.listen(this.renderer.domElement, 'pointerleave', () => this.hover(null));
        // Onglet caché : plus de son ni de calcul audio
        this.listen(document, 'visibilitychange', () => this.ambiance?.setVisible(!document.hidden));
        // Échap fait aussi sortir du plein écran : le bouton suit l'état réel
        this.listen(document, 'fullscreenchange', () => {
            const on = document.fullscreenElement === this.element;
            this.fullscreenButtonTarget.textContent = on ? 'Quitter le plein écran' : 'Plein écran';
            this.fullscreenButtonTarget.setAttribute('aria-pressed', String(on));
        });

        // Rendu en deux temps (scène, puis conversion des couleurs) sur tous les écrans : rendus
        // directement sur le canvas, les points additifs saturent en blanc (couleurs mélangées en sRGB).
        this.composer = new EffectComposer(this.renderer);
        this.composer.addPass(new RenderPass(this.scene, this.camera));
        // Lueur (bloom) autour des points lumineux.
        // ponytail: coupée sur petit écran comme approximation des GPU faibles ; à affiner si besoin (mesure du FPS)
        if (!window.matchMedia('(max-width: 576px)').matches) {
            this.composer.addPass(new UnrealBloomPass(new THREE.Vector2(1, 1), 0.35, 0.3, 0.4) /* force, rayon, seuil */);
        }
        this.composer.addPass(new OutputPass());

        this.resizeObserver = new ResizeObserver(() => this.resize());
        this.resizeObserver.observe(this.canvasTarget);
        this.resize();

        this.timer = new THREE.Timer();
        this.timer.connect(document);
        const loop = () => {
            this.frame = requestAnimationFrame(loop);
            this.timer.update(); // sans l'horodatage de rAF : il peut précéder la création du timer (temps négatif)
            this.animate(this.reducedMotion ? 0 : this.timer.getElapsed());
            this.controls.update();
            this.composer.render();
        };
        loop();

        // Lien direct : vers un élément (/cerveau#PHP) ou vers une vue partagée (/cerveau#vue=…)
        const hash = decodeURIComponent(location.hash.slice(1));
        if (hash.startsWith(VIEW_PREFIX)) {
            this.restoreView(hash.slice(VIEW_PREFIX.length));
        } else {
            const linked = hash && this.clickables.find((t) => t.userData.data.nom === hash);
            if (linked) this.select(linked);
        }
    }

    // ------------------------------------------------
    // Partage
    // ------------------------------------------------

    /** Lien vers ce qu'on voit : l'élément sélectionné, sinon la position exacte de la caméra */
    share() {
        if (!this.selected) {
            const round = (v) => v.toArray().map((n) => +n.toFixed(2)).join(',');
            history.replaceState(null, '', `#${VIEW_PREFIX}${round(this.camera.position)};${round(this.controls.target)}`);
        }
        const url = location.href;
        // Écran tactile : menu de partage du système ; ailleurs : copie dans le presse-papiers
        // (Chrome sur Windows a aussi navigator.share, mais on y attend une simple copie)
        const shared = navigator.share && window.matchMedia('(pointer: coarse)').matches
            ? navigator.share({ title: document.title, url })
            : navigator.clipboard.writeText(url).then(() => this.flashShareButton('Lien copié !'));
        shared.catch(() => {}); // partage annulé ou presse-papiers refusé : rien à faire
    }

    flashShareButton(text) {
        this.shareButtonTarget.textContent = text;
        clearTimeout(this.shareTimeout);
        this.shareTimeout = setTimeout(() => { this.shareButtonTarget.textContent = 'Partager'; }, 2000);
    }

    /** « x,y,z;x,y,z » : position de la caméra ; point visé */
    restoreView(view) {
        const [camera, target] = view.split(';').map((part) => part.split(',').map(Number));
        if (camera?.length !== 3 || target?.length !== 3 || [...camera, ...target].some((n) => !Number.isFinite(n))) return;
        this.camera.position.fromArray(camera);
        this.controls.target.fromArray(target);
        this.controls.autoRotate = false; // sinon la vue partagée dérive aussitôt
    }

    disconnect() {
        if (!this.renderer) return;
        cancelAnimationFrame(this.frame);
        this.stopTour();
        clearTimeout(this.shareTimeout);
        this.ambiance?.close();
        this.timer.dispose();
        this.resizeObserver.disconnect();
        this.listeners.forEach(([el, type, fn]) => el.removeEventListener(type, fn));
        this.controls.dispose();
        this.scene.traverse((object) => {
            object.geometry?.dispose();
            object.material?.dispose();
        });
        this.texture.dispose();
        this.composer.passes.forEach((pass) => pass.dispose());
        this.composer.dispose();
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
        this.composer.setSize(w, h);
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
            halo.scale.setScalar((active ? 0.3 : 0.16) * pulse); // assez petits pour laisser voir les plis
            // Les neurones reliés au neurone sélectionné restent allumés
            halo.material.opacity = !selected || active || neighbors.has(halo) ? 1 : 0.25;
        }

        for (const core of this.nebulae.targets) {
            const active = core === selected || core === this.hovered;
            core.scale.setScalar((active ? 2.2 : 1.6) * (1 + 0.1 * Math.sin(time + core.userData.phase)));
        }

        for (const halo of this.souvenirs.targets) {
            const active = halo === selected || halo === this.hovered;
            halo.scale.setScalar((active ? 0.32 : 0.2) * (1 + 0.15 * Math.sin(time * 1.5 + halo.userData.phase)));
        }

        this.synapses.update(time);
        this.nebulae.update(time);
        this.souvenirs.update(time);

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
        if (target) {
            this.stopTour();
            this.select(target);
        }
    }

    /** Bouton « Son » : l'audio n'est créé qu'au premier clic (les navigateurs l'exigent) */
    toggleSound() {
        this.ambiance ??= new Ambiance();
        const on = this.ambiance.toggle();
        this.soundButtonTarget.textContent = on ? 'Couper le son' : 'Son';
        this.soundButtonTarget.setAttribute('aria-pressed', String(on));
    }

    toggleFullscreen() {
        if (document.fullscreenElement) document.exitFullscreen();
        else this.element.requestFullscreen();
    }

    // ------------------------------------------------
    // Visite guidée
    // ------------------------------------------------

    /** Bouton « Visite guidée » : lance ou arrête la visite */
    toggleTour() {
        if (this.tourTimeout) {
            this.stopTour();
            return;
        }
        const stops = [...this.neurons]
            .sort((a, b) => b.userData.data.projets.length - a.userData.data.projets.length)
            .slice(0, TOUR_STOPS);
        this.tourButtonTarget.textContent = 'Arrêter la visite';
        this.tourButtonTarget.setAttribute('aria-pressed', 'true');

        const next = (i) => {
            if (i === stops.length) {
                this.stopTour();
                this.close();
                return;
            }
            this.select(stops[i]);
            this.tourTimeout = setTimeout(() => next(i + 1), TOUR_PAUSE_MS);
        };
        next(0);
    }

    /** Toute action de l'utilisateur sur le cerveau arrête la visite */
    stopTour() {
        if (!this.tourTimeout) return;
        clearTimeout(this.tourTimeout);
        this.tourTimeout = null;
        this.tourButtonTarget.textContent = 'Visite guidée';
        this.tourButtonTarget.setAttribute('aria-pressed', 'false');
    }

    // ------------------------------------------------
    // Sélection + panneau
    // ------------------------------------------------

    /** Depuis la légende ou le panneau (clavier, lecteur d'écran) : data-brain-name-param */
    /** Champ de recherche : nom exact, sinon premier nom qui le contient (sans tenir compte des accents ni de la casse) */
    search(event) {
        event.preventDefault();
        const query = normalize(this.searchTarget.value.trim());
        if (!query || !this.clickables) return;

        const found = this.clickables.find((t) => normalize(t.userData.data.nom) === query)
            ?? this.clickables.find((t) => normalize(t.userData.data.nom).includes(query));
        this.searchTarget.setAttribute('aria-invalid', String(!found));
        if (!found) return;

        this.stopTour();
        this.select(found);
        this.searchTarget.value = found.userData.data.nom;
    }

    /** À chaque touche dans le champ (propagation arrêtée : les flèches ne déplacent pas la caméra) */
    clearSearchError() {
        this.searchTarget.removeAttribute('aria-invalid');
    }

    selectByName({ params: { name }, target }) {
        const found = this.clickables?.find((t) => t.userData.data.nom === name);
        if (!found) return;
        this.stopTour();
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
        this.ambiance?.ping(target.userData.kind);
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
        else if (target.userData.kind === 'souvenir') this.fillSouvenirPanel(target.userData.data);
        else this.fillNebulaPanel(target.userData.data);
        this.panelTarget.hidden = false;
        history.replaceState(null, '', `#${encodeURIComponent(target.userData.data.nom)}`);
    }

    close() {
        this.stopTour();
        if (!this.selected) return;
        this.selected = null;
        this.panelTarget.hidden = true;
        this.controls.autoRotate = !this.reducedMotion;
        this.synapses.highlight(null);
        history.replaceState(null, '', location.pathname);

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

    fillSouvenirPanel({ nom, dates, intitule, option, ecole, lieu, resultat }) {
        this.fillPanelHeader(`Parcours · ${dates}`, nom, '#ffe8a3');
        this.panelBodyTarget.append(
            element('p', { textContent: intitule }),
            ...(option ? [element('p', { className: 'brain-panel-empty', textContent: option })] : []),
            element('p', { textContent: `${ecole} — ${lieu}` }),
            element('p', { textContent: resultat }),
        );
    }

    fillPanelHeader(category, title, color) {
        this.panelTarget.style.setProperty('--neuron', color);
        this.panelCategoryTarget.textContent = category;
        this.panelTitleTarget.textContent = title;
        this.panelBodyTarget.replaceChildren();
    }
}

/** Texte comparable : sans accents ni majuscules (« Médecine » → « medecine »). */
function normalize(text) {
    return text.normalize('NFD').replace(/\p{Diacritic}/gu, '').toLowerCase();
}

/** Petit utilitaire DOM : element('a', { href, textContent }, ...enfants). */
function element(tag, properties = {}, ...children) {
    const el = Object.assign(document.createElement(tag), properties);
    el.append(...children);
    return el;
}
