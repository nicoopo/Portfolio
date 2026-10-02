// Ouverture/fermeture du menu galactique (base.html.twig)
const menuToggle = document.getElementById('menuToggle');
const menuDropdown = document.getElementById('menuDropdown');

menuToggle.addEventListener('click', () => {
    menuDropdown.classList.toggle('show');
});

// Ferme le menu quand on clique ailleurs
document.addEventListener('click', (e) => {
    if (!menuToggle.contains(e.target) && !menuDropdown.contains(e.target)) {
        menuDropdown.classList.remove('show');
    }
});
