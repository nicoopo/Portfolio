import { Controller } from '@hotwired/stimulus';

/**
 * Effet machine à écrire qui fait défiler des mots.
 * <span data-controller="typing" data-typing-words-value='["A", "B"]'></span>
 */
export default class extends Controller {
    static values = { words: Array };

    connect() {
        this.wordIndex = 0;
        this.charIndex = 0;
        this.isDeleting = false;
        this.type();
    }

    disconnect() {
        clearTimeout(this.timeout);
    }

    type() {
        const typeSpeed = 100;
        const deleteSpeed = 50;
        const pauseTime = 2000;
        const currentWord = this.wordsValue[this.wordIndex];

        if (this.isDeleting) {
            this.element.textContent = currentWord.substring(0, this.charIndex - 1);
            this.charIndex--;
        } else {
            this.element.textContent = currentWord.substring(0, this.charIndex + 1);
            this.charIndex++;
        }

        let typeDelay = this.isDeleting ? deleteSpeed : typeSpeed;

        if (!this.isDeleting && this.charIndex === currentWord.length) {
            typeDelay = pauseTime;
            this.isDeleting = true;
        } else if (this.isDeleting && this.charIndex === 0) {
            this.isDeleting = false;
            this.wordIndex = (this.wordIndex + 1) % this.wordsValue.length;
            typeDelay = 500;
        }

        this.timeout = setTimeout(() => this.type(), typeDelay);
    }
}
