/* stimulusFetch: 'lazy' */
import { Controller } from '@hotwired/stimulus';
import * as THREE from 'three';
import { OrbitControls } from 'three/addons/controls/OrbitControls.js';
import { EffectComposer } from 'three/addons/postprocessing/EffectComposer.js';
import { RenderPass } from 'three/addons/postprocessing/RenderPass.js';
import { UnrealBloomPass } from 'three/addons/postprocessing/UnrealBloomPass.js';
import { OutputPass } from 'three/addons/postprocessing/OutputPass.js';
import { dotTexture, exploreFade } from '../cerveau/textures.js';
import { BODY_OPACITY, createBrain } from '../cerveau/brain.js';
import { createNeurons } from '../cerveau/neurons.js';
import { Synapses } from '../cerveau/synapses.js';
import { createNebulae } from '../cerveau/nebulae.js';
import { createSouvenirs } from '../cerveau/souvenirs.js';
import { Ambiance } from '../cerveau/ambiance.js';
import { createShip, createStarfield } from '../cerveau/vaisseau.js';
import { basseQualite, preferences, setPreference } from '../scripts/preferences.js';

const HOME_TARGET = new THREE.Vector3(0, 0, 0);
const CLICK_TOLERANCE_PX = 5;
const FLIGHT_SPEED = 4; // vitesse des vols de caméra (plus grand = plus rapide)
const FOCUS_DISTANCE = { neuron: 1.8, nebula: 5, souvenir: 1.8 }; // distance caméra ↔ élément sélectionné
// Plongée : en dessous de outside la caméra commence à entrer, en dessous de inside elle est dedans
const DIVE = { outside: 1.3, inside: 0.75, cameraDistance: 0.45, souvenirDistance: 0.7 };
const TOUR_STOPS = 6;       // neurones visités : ceux qui ont le plus de projets
const TOUR_PAUSE_MS = 9000; // temps passé sur chaque neurone (vol compris)
const TOUR_FLIGHT_S = 3.5;  // durée du vol d'un neurone à l'autre
const TOUR_LIFT = 0.6;      // la caméra prend du recul à mi-vol (part de la distance parcourue)
const VIEW_PREFIX = 'vue='; // lien vers une vue : /cerveau#vue=x,y,z;x,y,z
// Mode exploration : vitesse max (unités/s) lente près du cerveau, rapide au large (entre near et far du centre),
// virage et tangage (rad/s), nez levé au plus (rad), zone de vol, distance pour « ramasser » un élément,
// zone morte de la souris (part du demi-écran), multiplicateur du boost (clic maintenu),
// glissé du doigt pour braquer à fond (px), distance à la caméra sous laquelle les halos s'effacent
const SHIP = {
    slow: 0.6, fast: 3, near: 1.5, far: 8,
    turn: 1.3, pitch: 1, maxPitch: 1.2, maxDistance: 45, boost: 3,
    pickup: { neuron: 0.15, souvenir: 0.15, nebula: 0.6 },
    deadZone: 0.15,
    touchRange: 60,
    fadeNear: 0.9,
};
// Touches physiques (e.code) : ZQSD sur AZERTY = WASD sur QWERTY
const SHIP_KEYS = new Set(['ArrowUp', 'ArrowDown', 'ArrowLeft', 'ArrowRight', 'KeyW', 'KeyA', 'KeyS', 'KeyD', 'Space', 'ShiftLeft', 'ShiftRight']);

/**
 * Scène du cerveau : interaction (caméra, survol, clic, panneau).
 * La 3D elle-même est dans assets/cerveau/ (cerveau, neurones, synapses, nébuleuses, souvenirs).
 * Chargé uniquement sur les pages qui l'utilisent (lazy).
 */
export default class BrainController extends Controller {
    static targets = ['canvas', 'fallback', 'tooltip', 'panel', 'panelCategory', 'panelTitle', 'panelBody', 'tourButton', 'fullscreenButton', 'search', 'legendMenu', 'shareButton', 'soundButton', 'diveButton', 'exploreButton', 'hint'];
    // texts : libellés traduits par le template (home/cerveau.html.twig)
    static values = { neurons: Array, passions: Array, souvenirs: Array, texts: Object };

    connect() {
        // Mobile : légende repliée, le cerveau reste visible
        if (petitEcran()) this.legendMenuTarget.open = false;

        try {
            // Pas d'anticrénelage : la scène passe par le composer (cibles sans MSAA), il ne lisserait que le quad final
            this.renderer = new THREE.WebGLRenderer({ antialias: false, alpha: true });
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
        this.diveButtonTarget.hidden = false;
        this.exploreButtonTarget.hidden = false;

        this.reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

        this.renderer.setPixelRatio(basseQualite() ? 1 : Math.min(window.devicePixelRatio, 2));
        this.canvasTarget.appendChild(this.renderer.domElement);

        this.scene = new THREE.Scene();
        this.camera = new THREE.PerspectiveCamera(50, 1, 0.01, 100);
        this.camera.position.set(3.2, 1.2, 3.2);
        this.homeDistance = this.camera.position.length();

        this.controls = new OrbitControls(this.camera, this.renderer.domElement);
        this.controls.enableDamping = true;
        this.controls.listenToKeyEvents(window); // flèches du clavier : se déplacer
        this.controls.minDistance = 0.25; // on peut entrer dans le cerveau
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
        // Neurones, synapses et souvenirs toujours dessinés après le corps du cerveau, sans test
        // de profondeur : ils sont dedans, l'écran qui cache ce qui passe derrière ne doit pas les masquer
        for (const object of [this.synapses.object, this.souvenirs.object, ...this.neurons.map((h) => h.userData.anchor)]) {
            object.traverse((child) => {
                child.renderOrder = 1;
                if (child.material) child.material.depthTest = false;
            });
        }

        this.raycaster = new THREE.Raycaster();
        this.pointer = new THREE.Vector2();
        this.listen(this.renderer.domElement, 'pointermove', (e) => this.onPointerMove(e));
        this.listen(this.renderer.domElement, 'pointerdown', (e) => {
            this.downAt = [e.clientX, e.clientY];
            if (!this.ship) return;
            if (e.pointerType !== 'mouse') this.touchDown(e);
            else if (e.button === 0) this.boosting = true; // clic maintenu : le vaisseau fonce
        });
        const pointerUp = (e) => {
            if (e.pointerType !== 'mouse') this.touchUp(e);
            else this.boosting = false;
        };
        this.listen(window, 'pointerup', pointerUp);
        this.listen(window, 'pointercancel', pointerUp);
        this.listen(this.renderer.domElement, 'pointerup', (e) => this.onPointerUp(e));
        this.listen(this.renderer.domElement, 'pointerleave', (e) => {
            this.hover(null);
            if (e.pointerType !== 'mouse') return;
            this.steer = null; // la souris quitte la scène (ex. vers un bouton) : le vaisseau ne tourne plus
            this.boosting = false;
        });
        // Pilotage du vaisseau (hors champ de saisie)
        this.listen(document, 'keydown', (e) => {
            if (!this.ship || !SHIP_KEYS.has(e.code) || e.target.closest?.('input, textarea')) return;
            e.preventDefault(); // pas de défilement de la page
            this.keys.add(e.code);
        });
        this.listen(document, 'keyup', (e) => this.keys?.delete(e.code));
        this.listen(window, 'blur', () => this.keys?.clear()); // touche relâchée hors de la fenêtre
        // Onglet caché : plus de son ni de calcul audio
        this.listen(document, 'visibilitychange', () => this.ambiance?.setVisible(!document.hidden));
        // Son : préférence du site (panneau ⚙ ou bouton « Son »)
        this.listen(document, 'preference', (e) => { if (e.detail.name === 'son') this.setSound(e.detail.value); });
        // Déjà activé : le navigateur n'autorise le son qu'après un geste, on démarre au premier clic ou à la première touche
        // (sauf sur le bouton « Son » et le panneau ⚙, qui changent eux-mêmes la préférence)
        const startSound = (e) => { if (!e.target.closest?.('#reglages, [data-brain-target="soundButton"]')) this.setSound(preferences.son); };
        this.listen(document, 'pointerdown', startSound);
        this.listen(document, 'keydown', startSound);
        // Échap fait aussi sortir du plein écran : le bouton suit l'état réel
        this.listen(document, 'fullscreenchange', () => {
            const on = document.fullscreenElement === this.element;
            this.fullscreenButtonTarget.textContent = on ? this.textsValue.quitFullscreen : this.textsValue.fullscreen;
            this.fullscreenButtonTarget.setAttribute('aria-pressed', String(on));
        });

        // Rendu en deux temps (scène, puis conversion des couleurs) sur tous les écrans : rendus
        // directement sur le canvas, les points additifs saturent en blanc (couleurs mélangées en sRGB).
        this.composer = new EffectComposer(this.renderer);
        this.composer.addPass(new RenderPass(this.scene, this.camera));
        // Lueur (bloom) autour des points lumineux.
        // ponytail: coupée sur petit écran comme approximation des GPU faibles ; à affiner si besoin (mesure du FPS)
        if (!basseQualite() && !window.matchMedia('(max-width: 576px)').matches) {
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
            this.animate(this.reducedMotion ? 0 : this.timer.getElapsed(), this.timer.getDelta());
            if (!this.ship) this.controls.update(); // en exploration, la caméra suit le vaisseau
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
            : navigator.clipboard.writeText(url).then(() => this.flashShareButton(this.textsValue.linkCopied));
        shared.catch(() => {}); // partage annulé ou presse-papiers refusé : rien à faire
    }

    flashShareButton(text) {
        this.shareButtonTarget.textContent = text;
        clearTimeout(this.shareTimeout);
        this.shareTimeout = setTimeout(() => { this.shareButtonTarget.textContent = this.textsValue.share; }, 2000);
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
        exploreFade.value = 0; // état du module : la prochaine visite ne doit pas démarrer en exploration
        this.stopTour();
        clearTimeout(this.shareTimeout);
        this.ambiance?.close();
        this.timer.dispose();
        this.resizeObserver.disconnect();
        this.listeners.forEach(([el, type, fn]) => el.removeEventListener(type, fn));
        this.controls.dispose();
        this.scene.traverse((object) => {
            object.geometry?.dispose();
            object.material?.map?.dispose();
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

    animate(time, delta) {
        const selected = this.selected;
        const neighbors = selected?.userData.kind === 'neuron' ? this.synapses.neighborsOf(selected) : new Set();

        // 0 = dehors, 1 = dans le cerveau (selon la distance de la caméra au centre)
        const inside = 1 - THREE.MathUtils.smoothstep(this.camera.position.length(), DIVE.inside, DIVE.outside);
        this.setInside(inside > 0.5);

        // Respiration lente de l'hologramme, atténué quand quelque chose est sélectionné
        // et quand on est dedans (la paroi devient un voile autour de nous)
        this.brain.material.opacity = ((selected ? 0.35 : 0.75) + 0.25 * Math.sin(time * 1.5)) * (1 - 0.65 * inside);
        // Le corps sombre masquerait tout l'intérieur
        this.brain.userData.bodyMaterial.opacity = BODY_OPACITY * (1 - inside);

        for (const halo of this.neurons) {
            const active = halo === selected || halo === this.hovered;
            const pulse = 1 + 0.2 * Math.sin(time * 2.5 + halo.userData.phase);
            halo.scale.setScalar((active ? 0.3 : 0.16) * pulse); // assez petits pour laisser voir les plis
            // Les neurones reliés au neurone sélectionné restent allumés ;
            // de l'intérieur, on les estompe : vus de si près ils masqueraient les souvenirs
            const lit = !selected || active || neighbors.has(halo) ? 1 : 0.25;
            halo.material.opacity = active ? lit : lit * (1 - 0.8 * inside);
            halo.userData.anchor.material.opacity = halo.material.opacity;
            // Noyau plein : même transparent, il cacherait les souvenirs derrière lui (profondeur)
            halo.userData.anchor.material.visible = active || inside < 0.5;
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
        if (this.ship) {
            this.updateShip(delta);
        } else if (this.flight?.duration && !this.reducedMotion) {
            // Vol de la visite : durée fixe, départ et arrivée en douceur, en arc par l'extérieur du cerveau
            const f = this.flight;
            f.t = Math.min(f.t + delta / f.duration, 1);
            const e = f.t < 0.5 ? 4 * f.t ** 3 : 1 - (-2 * f.t + 2) ** 3 / 2; // easeInOutCubic
            this.controls.target.lerpVectors(f.from.target, f.target, e);
            this.camera.position.lerpVectors(f.from.camera, f.camera, e);
            this.camera.position.setLength(this.camera.position.length() + f.lift * Math.sin(Math.PI * e));
            if (f.t === 1) this.flight = null;
        } else if (this.flight) {
            // Rapprochement selon le temps écoulé, pas par image : même durée à 30 ou 144 images/s
            const k = this.reducedMotion ? 1 : 1 - Math.exp(-FLIGHT_SPEED * delta);
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
        if (this.ship && event.pointerType !== 'mouse') {
            this.touchMove(event); // le doigt pilote : pas de bulle de survol
            return;
        }
        if (this.ship) this.steerTowards(event);
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
        // Un glisser (rotation) n'est pas un clic ; en exploration, le clic sert au boost (on ouvre en touchant)
        if (!this.downAt || this.ship) return;
        const moved = Math.hypot(event.clientX - this.downAt[0], event.clientY - this.downAt[1]);
        this.downAt = null;
        if (moved > CLICK_TOLERANCE_PX) return;

        const target = this.pick(event);
        if (target) {
            this.stopTour();
            this.select(target);
        }
    }

    /** Bouton « Son » : change la préférence, qui revient par l'événement « preference » */
    toggleSound() {
        setPreference('son', !this.ambiance?.on);
    }

    /** L'audio n'est créé qu'au premier geste (les navigateurs l'exigent) */
    setSound(on) {
        if (!window.AudioContext || on === !!this.ambiance?.on) return;
        this.ambiance ??= new Ambiance();
        this.ambiance.toggle();
        this.soundButtonTarget.textContent = on ? this.textsValue.mute : this.textsValue.sound;
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
        if (this.ship) this.stopExplore();
        if (this.tourTimeout) {
            this.stopTour();
            return;
        }
        const stops = [...this.neurons]
            .sort((a, b) => b.userData.data.projets.length - a.userData.data.projets.length)
            .slice(0, TOUR_STOPS);
        this.tourButtonTarget.textContent = this.textsValue.stopTour;
        this.tourButtonTarget.setAttribute('aria-pressed', 'true');

        const next = (i) => {
            if (i === stops.length) {
                this.stopTour();
                this.close();
                return;
            }
            this.select(stops[i], TOUR_FLIGHT_S);
            // Pendant la pause, la caméra tourne lentement autour du neurone
            this.controls.autoRotate = !this.reducedMotion;
            this.tourTimeout = setTimeout(() => next(i + 1), TOUR_PAUSE_MS);
        };
        next(0);
    }

    /** Toute action de l'utilisateur sur le cerveau arrête la visite */
    stopTour() {
        if (!this.tourTimeout) return;
        clearTimeout(this.tourTimeout);
        this.tourTimeout = null;
        this.controls.autoRotate = !this.selected && !this.reducedMotion;
        this.tourButtonTarget.textContent = this.textsValue.tour;
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
        if (petitEcran()) {
            this.searchTarget.blur(); // ferme le clavier
            this.legendMenuTarget.open = false;
        }
    }

    /** Recherche en direct : la légende ne montre que les noms qui contiennent la saisie, groupes concernés dépliés */
    filter() {
        const query = normalize(this.searchTarget.value.trim());
        for (const groupe of this.legendMenuTarget.querySelectorAll('.brain-legend-group')) {
            let trouves = 0;
            for (const li of groupe.querySelectorAll('li')) {
                li.hidden = query !== '' && !normalize(li.textContent).includes(query);
                if (!li.hidden) trouves++;
            }
            groupe.hidden = query !== '' && trouves === 0;
            groupe.open = query !== '' && trouves > 0;
        }
    }

    /** À chaque touche dans le champ (propagation arrêtée : les flèches ne déplacent pas la caméra) */
    clearSearchError() {
        this.searchTarget.removeAttribute('aria-invalid');
    }

    selectByName({ params: { name } }) {
        const found = this.clickables?.find((t) => t.userData.data.nom === name);
        if (!found) return;
        this.stopTour();
        this.select(found);
        this.panelTarget.focus();
        // Mobile : la légende ouverte masquerait le cerveau
        if (petitEcran()) this.legendMenuTarget.open = false;
    }

    /** duration (secondes) : vol lent et en arc (visite guidée) ; sans : vol direct */
    select(target, duration = 0) {
        this.selected = target;
        this.ambiance?.ping(target.userData.kind);
        this.controls.autoRotate = false;
        this.synapses.highlight(target.userData.kind === 'neuron' ? target : null);

        // La caméra vient se placer devant l'élément, dans l'axe de la vue actuelle ;
        // un souvenir visé de l'intérieur se regarde depuis le centre (on reste dedans)
        const position = target.userData.anchor.getWorldPosition(new THREE.Vector3());
        const fromInside = this.inside && target.userData.kind === 'souvenir';
        // Visite : on regarde chaque neurone depuis l'extérieur du cerveau (centre → neurone)
        let direction;
        if (fromInside) direction = position.clone().negate().normalize();
        else if (duration) direction = position.clone().normalize();
        else direction = this.camera.position.clone().sub(this.controls.target).normalize();
        this.flight = {
            target: position,
            camera: position.clone().addScaledVector(direction, fromInside ? DIVE.souvenirDistance : FOCUS_DISTANCE[target.userData.kind]),
        };
        if (duration) {
            Object.assign(this.flight, {
                duration,
                t: 0,
                from: { target: this.controls.target.clone(), camera: this.camera.position.clone() },
                lift: TOUR_LIFT * this.camera.position.distanceTo(this.flight.camera),
            });
        }

        if (target.userData.kind === 'neuron') this.fillNeuronPanel(target);
        else if (target.userData.kind === 'souvenir') this.fillSouvenirPanel(target.userData.data);
        else this.fillNebulaPanel(target.userData.data);
        this.panelTarget.hidden = false;
        // Le nouveau contenu apparaît en fondu plutôt que de remplacer l'ancien d'un coup
        if (!this.reducedMotion) {
            this.panelTarget.animate(
                [{ opacity: 0, transform: 'translateY(8px)' }, { opacity: 1, transform: 'none' }],
                { duration: duration ? 900 : 350, easing: 'ease-out' },
            );
        }
        history.replaceState(null, '', `#${encodeURIComponent(target.userData.data.nom)}`);
    }

    close() {
        if (this.ship) this.stopExplore();
        this.stopTour();
        if (!this.selected) return;
        this.deselect();
        // De l'intérieur, on y reste
        this.flyHome(this.inside ? DIVE.cameraDistance : this.homeDistance);
    }

    deselect() {
        this.selected = null;
        this.panelTarget.hidden = true;
        this.controls.autoRotate = !this.reducedMotion;
        this.synapses.highlight(null);
        history.replaceState(null, '', location.pathname);
    }

    /** Retour vers le centre, la caméra à `distance` dans l'axe de la vue actuelle */
    flyHome(distance) {
        const direction = this.camera.position.clone().sub(this.controls.target).normalize();
        this.flight = { target: HOME_TARGET.clone(), camera: direction.multiplyScalar(distance) };
    }

    // ------------------------------------------------
    // Plongée dans le cerveau
    // ------------------------------------------------

    /** Bouton « Plonger » / « Ressortir » */
    dive() {
        if (this.ship) this.stopExplore();
        const goingIn = !this.inside;
        this.stopTour();
        this.deselect();
        this.flyHome(goingIn ? DIVE.cameraDistance : this.homeDistance);
    }

    /** Met l'interface à jour seulement quand on passe la paroi */
    setInside(inside) {
        if (inside === this.inside) return;
        this.inside = inside;
        this.diveButtonTarget.textContent = inside ? this.textsValue.surface : this.textsValue.dive;
        this.diveButtonTarget.setAttribute('aria-pressed', String(inside));
        if (!this.ship) this.hintTarget.textContent = inside ? this.textsValue.hintInside : this.textsValue.hintOutside;
    }

    // ------------------------------------------------
    // Mode exploration : piloter un vaisseau
    // ------------------------------------------------

    /** Bouton « Explorer en vaisseau » / « Quitter l'exploration » */
    toggleExplore() {
        if (this.ship) this.stopExplore();
        else this.startExplore();
    }

    startExplore() {
        this.stopTour();
        this.deselect();
        this.flight = null;
        this.controls.enabled = false;
        this.controls.autoRotate = false;
        // Sinon Espace « clique » sur le bouton et quitte aussitôt
        document.activeElement?.blur();

        // Posé devant la caméra, dans son orientation
        this.ship = createShip(this.texture);
        // Cap et tangage gardés à part (pas de roulis qui s'accumule)
        const { x: pitch, y: yaw } = new THREE.Euler().setFromQuaternion(this.camera.quaternion, 'YXZ');
        this.shipFlight = { speed: 0, turn: 0, climb: 0, yaw, pitch, look: null };
        this.steer = null;
        this.ship.rotation.set(pitch, yaw, 0, 'YXZ');
        this.ship.position.copy(this.camera.position);
        this.ship.translateZ(-0.6);
        this.baseFov = this.camera.fov;
        this.stars = createStarfield(this.texture);
        this.scene.add(this.ship, this.stars);
        exploreFade.value = SHIP.fadeNear;
        this.keys = new Set();

        this.exploreButtonTarget.textContent = this.textsValue.stopExplore;
        this.exploreButtonTarget.setAttribute('aria-pressed', 'true');
        this.hintTarget.textContent = window.matchMedia('(pointer: coarse)').matches ? this.textsValue.hintExploreTouch : this.textsValue.hintExplore;
    }

    stopExplore() {
        const position = this.ship.position.clone();
        this.scene.remove(this.ship, this.stars);
        this.stars.geometry.dispose();
        this.stars.material.dispose();
        this.boosting = false;
        this.touches?.clear();
        exploreFade.value = 0;
        this.ship.traverse((child) => {
            child.geometry?.dispose();
            child.material?.dispose(); // pas sa texture : c'est celle de toute la scène
        });
        this.ship = null;
        this.keys.clear();
        this.resize(); // champ de vision normal

        // On reprend l'orbite autour du centre en partant de là où était le vaisseau
        this.controls.enabled = true;
        this.controls.target.copy(position);
        this.controls.autoRotate = !this.selected && !this.reducedMotion;
        this.flyHome(this.inside ? DIVE.cameraDistance : this.homeDistance);

        this.exploreButtonTarget.textContent = this.textsValue.explore;
        this.exploreButtonTarget.setAttribute('aria-pressed', 'false');
        this.hintTarget.textContent = this.inside ? this.textsValue.hintInside : this.textsValue.hintOutside;
    }

    /** Souris : écart du pointeur au centre de la scène, de -1 à 1, sans la zone morte */
    steerTowards(event) {
        const rect = this.renderer.domElement.getBoundingClientRect();
        this.steer = {
            x: deadZone(((event.clientX - rect.left) / rect.width) * 2 - 1),
            y: deadZone(-(((event.clientY - rect.top) / rect.height) * 2 - 1)),
        };
    }

    // Tactile : le premier doigt est une manette (posé = avance, glissé depuis son point de départ = dirige) ;
    // un deuxième doigt posé = boost

    touchDown(event) {
        (this.touches ??= new Map()).set(event.pointerId, [event.clientX, event.clientY]);
        this.boosting = this.touches.size > 1;
    }

    touchMove(event) {
        const [first] = this.touches?.keys() ?? [];
        if (event.pointerId !== first) return;
        const [x0, y0] = this.touches.get(first);
        const range = (v) => deadZone(THREE.MathUtils.clamp(v / SHIP.touchRange, -1, 1));
        this.steer = { x: range(event.clientX - x0), y: range(y0 - event.clientY) };
    }

    touchUp(event) {
        if (!this.touches?.delete(event.pointerId)) return;
        this.boosting = this.touches.size > 1;
        if (this.touches.size === 0) this.steer = null;
    }

    updateShip(delta) {
        const axis = (plus, minus) => (plus.some((c) => this.keys.has(c)) ? 1 : 0) - (minus.some((c) => this.keys.has(c)) ? 1 : 0);
        const clamp = (v) => THREE.MathUtils.clamp(v, -1, 1);
        const thrust = this.boosting || this.touches?.size ? 1 : axis(['ArrowUp', 'KeyW'], ['ArrowDown', 'KeyS']);
        // Clavier et souris s'additionnent
        const turn = clamp(axis(['ArrowLeft', 'KeyA'], ['ArrowRight', 'KeyD']) - (this.steer?.x ?? 0));
        const climb = clamp(axis(['Space'], ['ShiftLeft', 'ShiftRight']) + (this.steer?.y ?? 0));
        const smooth = (rate) => (this.reducedMotion ? 1 : 1 - Math.exp(-rate * delta));
        const f = this.shipFlight;

        // Lent près du cerveau (pour viser), rapide au large (pour rejoindre les nébuleuses)
        const cruise = THREE.MathUtils.lerp(SHIP.slow, SHIP.fast, THREE.MathUtils.smoothstep(this.ship.position.length(), SHIP.near, SHIP.far));
        const max = this.boosting ? cruise * SHIP.boost : cruise;
        // Inertie courte : on part et on s'arrête vite ; freiner (↓) est encore plus franc, la marche arrière lente
        let wanted = 0;
        let reactivite = 3;
        if (thrust > 0) {
            wanted = max;
            reactivite = 2;
        } else if (thrust < 0) {
            wanted = -0.4 * max;
            reactivite = 6;
        }
        f.speed += (wanted - f.speed) * smooth(reactivite);
        f.turn += (turn - f.turn) * smooth(4);
        f.climb += (climb - f.climb) * smooth(4);
        f.yaw += SHIP.turn * f.turn * delta;
        f.pitch = THREE.MathUtils.clamp(f.pitch + SHIP.pitch * f.climb * delta, -SHIP.maxPitch, SHIP.maxPitch);
        this.ship.rotation.set(f.pitch, f.yaw, 0, 'YXZ');
        const from = this.ship.position.clone();
        this.ship.translateZ(-f.speed * delta);
        // shortcut: seule collision = le bord de la zone de vol ; on traverse les neurones (#286)
        this.ship.position.clampLength(0, SHIP.maxDistance);
        // Puissance du réacteur : 1 en croisière, jusqu'à 3 en boost
        this.ship.userData.update(Math.max(f.speed, 0) / cruise, f.turn, f.climb, this.reducedMotion);

        // Caméra derrière et au-dessus ; son regard rejoint en douceur un point devant le vaisseau
        this.camera.position.lerp(this.ship.localToWorld(new THREE.Vector3(0, 0.14, 0.5)), smooth(5));
        const look = this.ship.localToWorld(new THREE.Vector3(0, 0, -0.4));
        f.look = f.look ? f.look.lerp(look, smooth(8)) : look;
        this.camera.lookAt(f.look);
        // Champ un peu élargi à grande vitesse
        if (!this.reducedMotion) {
            this.camera.fov = this.baseFov + Math.min(6 * Math.abs(f.speed) / SHIP.fast, 18);
            this.camera.updateProjectionMatrix();
        }

        // Toucher un neurone, un souvenir ou une nébuleuse ouvre son panneau (projets liés, parcours…)
        // Sur tout le trajet de l'image : en boost, on avance de plus que la distance de ramassage
        const path = new THREE.Line3(from, this.ship.position);
        const at = new THREE.Vector3();
        const touched = this.clickables.find((t) => {
            t.userData.anchor.getWorldPosition(at);
            return path.closestPointToPoint(at, true, new THREE.Vector3()).distanceTo(at) < SHIP.pickup[t.userData.kind];
        });
        if (touched && touched !== this.selected) {
            this.select(touched);
            this.flight = null; // la caméra reste derrière le vaisseau
        }
    }

    fillNeuronPanel(halo) {
        const { nom, categorie, couleur, projets } = halo.userData.data;
        this.fillPanelHeader(categorie, nom, couleur);
        const body = this.panelBodyTarget;

        if (projets.length === 0) {
            body.append(element('p', { className: 'brain-panel-empty', textContent: this.textsValue.noProject }));
        } else {
            const list = element('ul');
            for (const { titre, url } of projets) {
                list.append(element('li', {}, element('a', { href: url, textContent: titre })));
            }
            body.append(element('h3', { textContent: projets.length > 1 ? this.textsValue.linkedProjects : this.textsValue.linkedProject }), list);
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
            body.append(element('h3', { textContent: this.textsValue.connectedTo }), links);
        }
    }

    fillNebulaPanel({ nom, couleur, description }) {
        this.fillPanelHeader(this.textsValue.passion, nom, couleur);
        this.panelBodyTarget.append(element('p', { textContent: description }));
    }

    fillSouvenirPanel({ nom, dates, intitule, option, ecole, lieu, resultat, url }) {
        this.fillPanelHeader(`${this.textsValue.parcours} · ${dates}`, nom, '#ffe8a3');
        this.panelBodyTarget.append(
            element('p', { textContent: intitule }),
            ...(option ? [element('p', { className: 'brain-panel-empty', textContent: option })] : []),
            element('p', { textContent: `${ecole} — ${lieu}` }),
            element('p', { textContent: resultat }),
            element('p', {}, element('a', { href: url, textContent: this.textsValue.seeInTimeline })),
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
/** Même seuil que le CSS mobile (_cerveau.css) */
function petitEcran() {
    return window.matchMedia('(max-width: 576px)').matches;
}

function normalize(text) {
    return text.normalize('NFD').replace(/\p{Diacritic}/gu, '').toLowerCase();
}

/** Pilotage (souris, doigt) : valeur de -1 à 1 sans la zone morte du centre, puis remise de 0 à 1. */
function deadZone(v) {
    return Math.sign(v) * Math.max(Math.abs(v) - SHIP.deadZone, 0) / (1 - SHIP.deadZone);
}

/** Petit utilitaire DOM : element('a', { href, textContent }, ...enfants). */
function element(tag, properties = {}, ...children) {
    const el = Object.assign(document.createElement(tag), properties);
    el.append(...children);
    return el;
}
