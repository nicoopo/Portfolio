// Menu et réglages (base.html.twig) s'ouvrent seuls (attribut popover) ; ici, seulement les préférences
import { basseQualite, preferences, setPreference } from './preferences.js';

const transitions = document.getElementById('prefTransitions');
const qualite = document.getElementById('prefQualite');
const son = document.getElementById('prefSon');
transitions.checked = preferences.transitions;
qualite.checked = !basseQualite();
son.checked = preferences.son;
transitions.addEventListener('change', () => setPreference('transitions', transitions.checked));
son.addEventListener('change', () => setPreference('son', son.checked));
// Son coupé ou remis depuis le bouton de la page cerveau
document.addEventListener('preference', (e) => { if (e.detail.name === 'son') son.checked = e.detail.value; });
qualite.addEventListener('change', () => {
    setPreference('qualite', qualite.checked ? 'haute' : 'basse');
    setPreference('qualiteAuto', false); // choisi à la main : plus de proposition automatique
    location.reload(); // cerveau et trous noirs sont construits au chargement
});

// Curseur : appliqué tout de suite (curseur.js relit la préférence à chaque image)
document.querySelectorAll('input[name="curseur"]').forEach((radio) => {
    radio.checked = radio.value === preferences.curseur;
    radio.addEventListener('change', () => setPreference('curseur', radio.value));
});
