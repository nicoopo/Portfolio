import { Controller } from '@hotwired/stimulus';

/**
 * CV en ligne : étoiles, bascule thème clair/sombre, téléchargement du PDF
 * généré par Symfony (CVController::download) dans le thème affiché.
 */
export default class extends Controller {
    static targets = ['stars', 'download', 'iconSun', 'iconMoon', 'themeText'];
    static values = { downloadUrl: String };

    connect() {
        this.createStars(100);
        // Le thème est déjà posé sur <html> par le script en tête de page
        this.updateThemeButton(this.theme);

        this.onScroll = () => this.parallax();
        window.addEventListener('scroll', this.onScroll);
    }

    disconnect() {
        window.removeEventListener('scroll', this.onScroll);
    }

    get theme() {
        return document.documentElement.dataset.theme;
    }

    createStars(count) {
        for (let i = 0; i < count; i++) {
            const star = document.createElement('div');
            star.className = 'star';
            star.style.left = Math.random() * 100 + '%';
            star.style.top = Math.random() * 100 + '%';
            star.style.animationDelay = Math.random() * 3 + 's';
            star.style.opacity = Math.random() * 0.7 + 0.3;
            this.starsTarget.appendChild(star);
        }
    }

    parallax() {
        const scrolled = window.scrollY;
        this.starsTarget.querySelectorAll('.star').forEach((star, index) => {
            const speed = (index % 3 + 1) * 0.1;
            star.style.transform = `translateY(${scrolled * speed}px)`;
        });
    }

    toggleTheme() {
        const newTheme = this.theme === 'dark' ? 'light' : 'dark';
        document.documentElement.dataset.theme = newTheme;
        localStorage.setItem('theme', newTheme);
        this.updateThemeButton(newTheme);
    }

    updateThemeButton(theme) {
        const dark = theme === 'dark';
        this.iconSunTarget.style.display = dark ? 'none' : 'inline';
        this.iconMoonTarget.style.display = dark ? 'inline' : 'none';
        this.themeTextTarget.textContent = dark ? 'Mode Clair' : 'Mode Sombre';
    }

    download() {
        const button = this.downloadTarget;
        const label = button.querySelector('span:last-child');
        const originalText = label.textContent;

        // Bouton désactivé pendant la génération
        button.disabled = true;
        button.style.opacity = '0.6';
        button.style.cursor = 'wait';
        label.textContent = 'Génération...';

        const link = document.createElement('a');
        link.href = `${this.downloadUrlValue}?theme=${this.theme}`;
        link.download = '';
        document.body.appendChild(link);
        link.click();
        link.remove();

        setTimeout(() => {
            button.disabled = false;
            button.style.opacity = '1';
            button.style.cursor = 'pointer';
            label.textContent = originalText;
        }, 1000);
    }
}
