import { Controller } from '@hotwired/stimulus';

/** Visionneuse PDF de la page Univers : affiche un document dans l'iframe, sous les boutons. */
export default class extends Controller {
    static targets = ['viewer', 'frame', 'title'];

    show({ params: { url, title } }) {
        this.titleTarget.textContent = title;
        this.frameTarget.src = url;
        this.viewerTarget.hidden = false;
        this.viewerTarget.scrollIntoView({ behavior: 'smooth' });
    }

    close() {
        this.viewerTarget.hidden = true;
        this.frameTarget.src = '';
    }
}
