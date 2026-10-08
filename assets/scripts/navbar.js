// Menu et réglages (base.html.twig) s'ouvrent seuls (attribut popover) ; ici, seulement les préférences
import { preferences, setPreference } from './preferences.js';

const transitions = document.getElementById('prefTransitions');
const qualite = document.getElementById('prefQualite');
transitions.checked = preferences.transitions;
qualite.checked = preferences.qualite === 'haute';
transitions.addEventListener('change', () => setPreference('transitions', transitions.checked));
qualite.addEventListener('change', () => {
    setPreference('qualite', qualite.checked ? 'haute' : 'basse');
    location.reload(); // cerveau et trous noirs sont construits au chargement
});

// Curseur : appliqué tout de suite (curseur.js relit la préférence à chaque image)
document.querySelectorAll('input[name="curseur"]').forEach((radio) => {
    radio.checked = radio.value === preferences.curseur;
    radio.addEventListener('change', () => setPreference('curseur', radio.value));
});
