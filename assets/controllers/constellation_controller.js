/* stimulusFetch: 'lazy' */
import { Controller } from '@hotwired/stimulus';

/**
 * Page Compétences : survoler (ou parcourir au clavier) une compétence de la liste éclaire son étoile
 * dans la constellation, et inversement.
 */
export default class extends Controller {
    connect() {
        const eclairer = (e) => {
            const id = e.type.endsWith('out') ? null : e.target.closest('[data-competence]')?.dataset.competence;
            this.element.querySelectorAll('[data-competence]').forEach((el) => el.classList.toggle('eclairee', el.dataset.competence === id));
        };
        ['pointerover', 'pointerout', 'focusin', 'focusout'].forEach((type) => this.element.addEventListener(type, eclairer));
    }
}
