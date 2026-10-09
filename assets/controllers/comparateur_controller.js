/* stimulusFetch: 'lazy' */
import { Controller } from '@hotwired/stimulus';

/**
 * Comparateur offre / compétences (competences/comparer.html.twig) : cherche dans le texte collé les mots-clés
 * de chaque compétence (nom et synonymes, CompetencesController), les surligne et liste les projets qui les prouvent.
 * Recherche sans casse ni accents, sur des mots entiers ; les mots-clés de deux lettres (« Go », « JS ») respectent
 * la casse (telle qu'écrite ou tout en majuscules), sinon « go » ou « ts » se trouveraient partout. Tout reste dans le navigateur.
 */
export default class extends Controller {
    static targets = ['offre', 'resume', 'surligne', 'resultats'];
    static values = { competences: Array, textes: Object };

    connect() {
        // Même longueur que le texte d'origine (un caractère pour un), pour surligner aux bons endroits
        // (split('') et non [...texte] : un émoji compte deux unités, comme dans les positions de matchAll)
        this.normaliser = (texte) => texte.split('').map((c) => c.normalize('NFD')[0].toLowerCase()).join('');
        this.motifs = this.competencesValue.map((competence) => competence.mots.map((mot) => {
            const court = mot.length <= 2;
            const echapper = (m) => m.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
            // Court : tel qu'écrit ou tout en majuscules (« Go » / « GO », « js » / « JS »)
            const echappe = court ? `(?:${echapper(mot)}|${echapper(mot.toUpperCase())})` : echapper(this.normaliser(mot));
            // Pas derrière un point : le « js » de « Vue.js » n'est pas du JavaScript
            return { court, regex: new RegExp(`(?<![\\p{L}\\p{N}.])${echappe}(?![\\p{L}\\p{N}])`, 'gu') };
        }));
    }

    comparer() {
        const texte = this.offreTarget.value;
        const normalise = this.normaliser(texte);
        const zones = [];
        const trouvees = this.competencesValue.filter((competence, i) => {
            let trouve = false;
            for (const { court, regex } of this.motifs[i]) {
                for (const m of (court ? texte : normalise).matchAll(regex)) {
                    zones.push([m.index, m.index + m[0].length]);
                    trouve = true;
                }
            }
            return trouve;
        });

        this.surligneTarget.hidden = !texte.trim();
        this.surligneTarget.replaceChildren(...this.surligner(texte, zones));
        this.resumeTarget.textContent = !texte.trim() ? ''
            : trouvees.length ? this.textesValue.resume.replace('%trouvees%', trouvees.length) : this.textesValue.aucune;
        this.resultatsTarget.replaceChildren(...trouvees.map((competence) => this.resultat(competence)));
    }

    /** Le texte de l'offre, mots trouvés dans des <mark> (zones qui se chevauchent fusionnées) */
    surligner(texte, zones) {
        zones.sort((a, b) => a[0] - b[0]);
        const morceaux = [];
        let pos = 0;
        for (const [debut, fin] of zones) {
            if (fin <= pos) continue;
            const depart = Math.max(debut, pos);
            morceaux.push(document.createTextNode(texte.slice(pos, depart)));
            morceaux.push(Object.assign(document.createElement('mark'), { textContent: texte.slice(depart, fin) }));
            pos = fin;
        }
        morceaux.push(document.createTextNode(texte.slice(pos)));
        return morceaux;
    }

    resultat(competence) {
        const li = document.createElement('li');
        li.append(
            Object.assign(document.createElement('strong'), { textContent: competence.nom }),
            Object.assign(document.createElement('span'), { className: 'comparateur-categorie', textContent: competence.categorie }),
        );
        if (competence.projets.length) {
            const preuve = Object.assign(document.createElement('p'), { textContent: `${this.textesValue.projets} : ` });
            competence.projets.forEach((projet, i) => {
                if (i) preuve.append(', ');
                preuve.append(Object.assign(document.createElement('a'), { href: projet.url, textContent: projet.titre }));
            });
            li.append(preuve);
        }
        return li;
    }
}
