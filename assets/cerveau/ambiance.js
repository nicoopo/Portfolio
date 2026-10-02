/**
 * Ambiance sonore du cerveau, synthétisée (Web Audio, aucun fichier) :
 * une nappe spatiale lente + un petit son cristallin à la sélection.
 * À créer seulement après un geste de l'utilisateur (règle des navigateurs).
 */
const VOLUME = 0.05;
const FADE_S = 1.5;
// La, Mi, La, Si : un accord ouvert, sans tension
const PAD_NOTES = [110, 164.81, 220, 246.94];
const PING_NOTES = { neuron: 880, nebula: 659.25, souvenir: 1046.5 };

export class Ambiance {
    constructor() {
        this.context = new AudioContext();
        this.master = this.context.createGain();
        this.master.gain.value = 0;
        this.master.connect(this.context.destination);

        // Nappe : oscillateurs doux, un peu désaccordés, dans un filtre passe-bas qui « respire »
        const filter = this.context.createBiquadFilter();
        filter.type = 'lowpass';
        filter.frequency.value = 600;
        filter.Q.value = 4;
        filter.connect(this.master);

        const breath = this.context.createOscillator();
        breath.frequency.value = 0.05; // un cycle toutes les 20 s
        const breathDepth = this.context.createGain();
        breathDepth.gain.value = 350;
        breath.connect(breathDepth).connect(filter.frequency);
        breath.start();

        for (const [i, frequency] of PAD_NOTES.entries()) {
            const oscillator = this.context.createOscillator();
            oscillator.type = i % 2 ? 'triangle' : 'sine';
            oscillator.frequency.value = frequency;
            oscillator.detune.value = (i - 1.5) * 6;
            const gain = this.context.createGain();
            gain.gain.value = 0.25;
            oscillator.connect(gain).connect(filter);
            oscillator.start();
        }

        this.on = false;
    }

    /** Fondu d'entrée ou de sortie ; renvoie le nouvel état */
    toggle() {
        this.on = !this.on;
        if (this.on) this.context.resume();
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

    /** Petit son à la sélection d'un élément (kind : neuron, nebula, souvenir) */
    ping(kind) {
        if (!this.on) return;
        const now = this.context.currentTime;
        const oscillator = this.context.createOscillator();
        oscillator.frequency.value = PING_NOTES[kind] ?? PING_NOTES.neuron;
        const gain = this.context.createGain();
        gain.gain.setValueAtTime(0, now);
        gain.gain.linearRampToValueAtTime(0.12, now + 0.01);
        gain.gain.exponentialRampToValueAtTime(0.0001, now + 1.2);
        oscillator.connect(gain).connect(this.context.destination);
        oscillator.start(now);
        oscillator.stop(now + 1.2);
    }

    close() {
        this.context.close();
    }
}
