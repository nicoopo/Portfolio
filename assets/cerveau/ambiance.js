/**
 * Ambiance sonore, synthétisée (Web Audio, aucun fichier) : celle du cerveau, et une par page du site
 * (assets/scripts/ambiance_page.js). Sinusoïdes pures dans une grande réverbération — une nappe qui ondule
 * lentement, des notes de cloche aléatoires (gamme pentatonique : jamais de fausse note) et, sur le cerveau,
 * une note à chaque sélection. À créer seulement après un geste de l'utilisateur (règle des navigateurs).
 */
const VOLUME = 1.5;         // volume général : crête ≈ 0,09 (≈ -21 dB), un fond discret
const FADE_S = 2;           // fondu à l'activation / la coupure
const REVERB_S = 6;         // longueur de la réverbération
/**
 * Une couleur par page : nappe (accord grave), « étoiles » (pentatonique aiguë, vide = nappe seule)
 * et intervalle entre deux étoiles, en secondes.
 */
export const PROFILS = {
    // Do majeur : le cerveau, tel qu'il a toujours sonné
    cerveau: { nappe: [130.81, 196, 261.63], etoiles: [523.25, 587.33, 659.25, 783.99, 880, 1046.5, 1174.66, 1318.51], rythme: [3, 8] },
    // Ré majeur, lumineux : l'accueil
    accueil: { nappe: [146.83, 220, 293.66], etoiles: [587.33, 659.25, 739.99, 880, 987.77, 1174.66, 1318.51, 1479.98], rythme: [4, 9] },
    // La mineur, plus vif : les projets
    projets: { nappe: [110, 164.81, 220], etoiles: [440, 523.25, 587.33, 659.25, 783.99, 880, 1046.5, 1174.66], rythme: [2, 6] },
    // Fa majeur, lent et grave : le parcours
    univers: { nappe: [87.31, 130.81, 174.61], etoiles: [698.46, 783.99, 880, 1046.5, 1174.66, 1396.91], rythme: [5, 11] },
    // Sol majeur : les compétences
    competences: { nappe: [98, 146.83, 196], etoiles: [783.99, 880, 987.77, 1174.66, 1318.51, 1567.98], rythme: [3, 7] },
    // Nappe seule, sans étoiles : pages où l'on lit ou écrit (contact, CV, articles…)
    calme: { nappe: [130.81, 196, 261.63], etoiles: [], rythme: [0, 0] },
};
const PING_NOTES = { neuron: 783.99, nebula: 523.25, souvenir: 1046.5 };

export class Ambiance {
    constructor(profil = PROFILS.cerveau) {
        this.profil = profil;
        this.context = new AudioContext();
        this.master = this.context.createGain();
        this.master.gain.value = 0;
        this.master.connect(this.context.destination);

        // Réverbération : réponse impulsionnelle = bruit qui s'éteint lentement (généré, pas de fichier)
        this.reverb = this.context.createConvolver();
        this.reverb.buffer = impulseResponse(this.context, REVERB_S);
        this.reverb.connect(this.master);

        // Nappe : chaque note monte et descend lentement, à son propre rythme
        for (const [i, frequency] of profil.nappe.entries()) {
            const oscillator = this.context.createOscillator();
            oscillator.frequency.value = frequency;
            const gain = this.context.createGain();
            gain.gain.value = 0.02;
            const swell = this.context.createOscillator();
            swell.frequency.value = 1 / (11 + i * 4); // une vague toutes les 11, 15, 19 s
            const swellDepth = this.context.createGain();
            swellDepth.gain.value = 0.018;
            swell.connect(swellDepth).connect(gain.gain);
            oscillator.connect(gain).connect(this.reverb);
            oscillator.start();
            swell.start();
        }

        this.on = false;
    }

    /** Fondu d'entrée ou de sortie ; renvoie le nouvel état */
    toggle() {
        this.on = !this.on;
        if (this.on) {
            this.context.resume();
            this.scheduleStar();
        } else {
            clearTimeout(this.starTimeout);
        }
        const now = this.context.currentTime;
        this.master.gain.cancelScheduledValues(now);
        this.master.gain.setValueAtTime(this.master.gain.value, now);
        this.master.gain.linearRampToValueAtTime(this.on ? VOLUME : 0, now + FADE_S);
        return this.on;
    }

    /** Onglet caché : on suspend (plus de calcul audio) ; visible : on reprend si le son est actif */
    setVisible(visible) {
        if (!visible) this.context.suspend();
        else if (this.on) this.context.resume();
    }

    /** Note à la sélection d'un élément (kind : neuron, nebula, souvenir) */
    ping(kind) {
        if (this.on) this.bell(PING_NOTES[kind] ?? PING_NOTES.neuron, 0.07);
    }

    /** Étoile : une note de cloche au hasard, puis la suivante quelques secondes plus tard */
    scheduleStar() {
        const { etoiles, rythme: [min, max] } = this.profil;
        if (!etoiles.length) return;
        this.starTimeout = setTimeout(() => {
            this.bell(etoiles[Math.floor(Math.random() * etoiles.length)], 0.03);
            this.scheduleStar();
        }, (min + Math.random() * (max - min)) * 1000);
    }

    /** Cloche douce : sinusoïde + son octave plus faible, attaque rapide, longue extinction, dans la réverbération */
    bell(frequency, volume) {
        const now = this.context.currentTime;
        const gain = this.context.createGain();
        gain.gain.setValueAtTime(0, now);
        gain.gain.linearRampToValueAtTime(volume, now + 0.02);
        gain.gain.exponentialRampToValueAtTime(0.0001, now + 3);
        gain.connect(this.reverb);
        for (const [multiple, level] of [[1, 1], [2, 0.3]]) {
            const oscillator = this.context.createOscillator();
            oscillator.frequency.value = frequency * multiple;
            const partial = this.context.createGain();
            partial.gain.value = level;
            oscillator.connect(partial).connect(gain);
            oscillator.start(now);
            oscillator.stop(now + 3);
        }
    }

    close() {
        clearTimeout(this.starTimeout);
        this.context.close();
    }
}

/** Réponse impulsionnelle stéréo : bruit blanc à décroissance exponentielle (salle immense). */
function impulseResponse(context, seconds) {
    const length = Math.floor(context.sampleRate * seconds);
    const buffer = context.createBuffer(2, length, context.sampleRate);
    for (let channel = 0; channel < 2; channel++) {
        const data = buffer.getChannelData(channel);
        for (let i = 0; i < length; i++) {
            data[i] = (Math.random() * 2 - 1) * (1 - i / length) ** 3;
        }
    }
    return buffer;
}
