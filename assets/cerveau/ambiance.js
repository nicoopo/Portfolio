/**
 * Ambiance sonore du cerveau, synthétisée (Web Audio, aucun fichier) :
 * sinusoïdes pures dans une grande réverbération — une nappe qui ondule lentement,
 * des notes de cloche aléatoires (gamme pentatonique : jamais de fausse note)
 * et une note à chaque sélection.
 * À créer seulement après un geste de l'utilisateur (règle des navigateurs).
 */
const VOLUME = 1.5;         // volume général : crête ≈ 0,09 (≈ -21 dB), un fond discret
const FADE_S = 2;           // fondu à l'activation / la coupure
const REVERB_S = 6;         // longueur de la réverbération
const PAD_NOTES = [130.81, 196, 261.63]; // Do, Sol, Do : nappe douce
// Do majeur pentatonique, deux octaves aiguës : les « étoiles »
const STAR_NOTES = [523.25, 587.33, 659.25, 783.99, 880, 1046.5, 1174.66, 1318.51];
const STAR_EVERY_S = [3, 8]; // une note toutes les 3 à 8 s
const PING_NOTES = { neuron: 783.99, nebula: 523.25, souvenir: 1046.5 };

export class Ambiance {
    constructor() {
        this.context = new AudioContext();
        this.master = this.context.createGain();
        this.master.gain.value = 0;
        this.master.connect(this.context.destination);

        // Réverbération : réponse impulsionnelle = bruit qui s'éteint lentement (généré, pas de fichier)
        this.reverb = this.context.createConvolver();
        this.reverb.buffer = impulseResponse(this.context, REVERB_S);
        this.reverb.connect(this.master);

        // Nappe : chaque note monte et descend lentement, à son propre rythme
        for (const [i, frequency] of PAD_NOTES.entries()) {
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
        const [min, max] = STAR_EVERY_S;
        this.starTimeout = setTimeout(() => {
            this.bell(STAR_NOTES[Math.floor(Math.random() * STAR_NOTES.length)], 0.03);
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
