/* stimulusFetch: 'lazy' */
import { Controller } from '@hotwired/stimulus';

/** Frise des projets : n'affiche que les projets d'une techno (bouton « Toutes » : tout), et cache les années vides. */
export default class extends Controller {
    static targets = ['projet', 'annee'];

    filtrer(e) {
        const techno = e.currentTarget.dataset.techno;
        this.element.querySelectorAll('.frise-filtres button').forEach((bouton) => bouton.setAttribute('aria-pressed', String(bouton === e.currentTarget)));
        this.projetTargets.forEach((projet) => { projet.hidden = techno !== '' && !JSON.parse(projet.dataset.technos).includes(techno); });
        this.anneeTargets.forEach((annee) => { annee.hidden = !annee.querySelector('.frise-projet:not([hidden])'); });
    }
}
