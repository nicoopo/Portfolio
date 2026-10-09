/* stimulusFetch: 'lazy' */
import { Controller } from '@hotwired/stimulus';
import { decouvrir } from '../scripts/decouvertes.js';
import { naviguer } from '../scripts/naviguer.js';

/**
 * /terminal : faux terminal. Les données (profil, projets, compétences, contact) viennent de TerminalController ;
 * tout est affiché en texte (textContent), jamais en HTML. Historique aux flèches, complétion à Tab.
 */
export default class extends Controller {
    static targets = ['sortie', 'champ'];
    static values = { donnees: Object, textes: Object };

    connect() {
        this.historique = [];
        this.position = 0;
        this.ecrire(this.textesValue.bienvenue);
    }

    focus() { this.champTarget.focus(); }

    executer(e) {
        e.preventDefault();
        const ligne = this.champTarget.value.trim();
        this.champTarget.value = '';
        this.ecrire(`visiteur@univers:~$ ${ligne}`, 'terminal-commande');
        if (!ligne) return;
        this.historique.push(ligne);
        this.position = this.historique.length;

        const [commande, ...args] = ligne.split(/\s+/);
        const argument = args.join(' ').replace(/\/$/, '');
        const actions = {
            help: () => this.textesValue.aide.forEach(([c, d]) => this.ecrire(`  ${c.padEnd(20)} ${d}`)),
            whoami: () => this.ecrire(`Nicolas Cataluna — ${this.donneesValue.titre}\n${this.donneesValue.qualites}`),
            ls: () => this.ls(argument),
            cat: () => this.cat(argument),
            open: () => this.ouvrir(argument),
            clear: () => this.sortieTarget.replaceChildren(),
            exit: () => naviguer(this.donneesValue.pages.accueil),
            sudo: () => { this.ecrire(this.textesValue.sudo); decouvrir('terminal'); },
        };
        (actions[commande.toLowerCase()] ?? (() => this.ecrire(this.textesValue.inconnue.replace('%commande%', commande))))();
    }

    ls(dossier) {
        const { projets, competences } = this.donneesValue;
        if (!dossier) return this.ecrire('projets/  competences/  cv  contact');
        if (dossier === 'projets') return this.ecrire(projets.map((p) => p.slug).join('  '));
        if (dossier === 'competences') {
            return Object.entries(competences).forEach(([categorie, noms]) => this.ecrire(`${categorie}/\n  ${noms.join(', ')}`));
        }
        this.ecrire(this.textesValue.introuvable.replace('%nom%', dossier));
    }

    cat(fichier) {
        const { projets, resume, contact } = this.donneesValue;
        if (fichier === 'cv') return this.ecrire(resume);
        if (fichier === 'contact') return this.ecrire(`e-mail    ${contact.email}\nGitHub    ${contact.github}\nLinkedIn  ${contact.linkedin}`);
        const projet = projets.find((p) => `projets/${p.slug}` === fichier || p.slug === fichier);
        if (projet) return this.ecrire(`# ${projet.titre}\n${projet.description}\n[${projet.tech}]\n→ open ${projet.slug}`);
        this.ecrire(this.textesValue.introuvable.replace('%nom%', fichier));
    }

    ouvrir(nom) {
        const projet = this.donneesValue.projets.find((p) => p.slug === nom.replace(/^projets\//, ''));
        const url = projet?.url ?? this.donneesValue.pages[nom];
        if (!url) return this.ecrire(this.textesValue.introuvable.replace('%nom%', nom));
        this.ecrire(this.textesValue.ouverture.replace('%nom%', nom));
        naviguer(url);
    }

    /** ↑ ↓ : historique ; Tab : complète la commande ou le nom (projets/…, pages) */
    touche(e) {
        if (e.key === 'ArrowUp' || e.key === 'ArrowDown') {
            e.preventDefault();
            this.position = Math.max(0, Math.min(this.historique.length, this.position + (e.key === 'ArrowUp' ? -1 : 1)));
            this.champTarget.value = this.historique[this.position] ?? '';
        } else if (e.key === 'Tab') {
            const valeur = this.champTarget.value;
            const mots = valeur.split(' ');
            const dernier = mots.pop();
            const possibles = mots.length
                ? ['projets/', 'competences/', 'cv', 'contact', ...Object.keys(this.donneesValue.pages), ...this.donneesValue.projets.flatMap((p) => [p.slug, `projets/${p.slug}`])]
                : ['help', 'whoami', 'ls', 'cat', 'open', 'clear', 'exit'];
            const trouves = [...new Set(possibles.filter((p) => p.startsWith(dernier)))];
            if (!valeur.trim() && !mots.length) return; // Tab sur un champ vide : laisser sortir du champ
            e.preventDefault();
            if (trouves.length === 1) this.champTarget.value = [...mots, trouves[0]].join(' ') + (trouves[0].endsWith('/') ? '' : ' ');
            else if (trouves.length > 1) this.ecrire(trouves.join('  '));
        }
    }

    ecrire(texte, classe = '') {
        const ligne = Object.assign(document.createElement('pre'), { textContent: texte, className: classe });
        this.sortieTarget.append(ligne);
        this.sortieTarget.scrollTop = this.sortieTarget.scrollHeight;
    }
}
