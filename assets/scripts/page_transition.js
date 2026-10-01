// Apparition en fondu du contenu de chaque page
document.addEventListener('DOMContentLoaded', () => {
    const main = document.querySelector('main');
    if (!main) return;

    main.style.opacity = 0;
    main.style.transform = 'translateY(30px)';
    main.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
    requestAnimationFrame(() => {
        main.style.opacity = 1;
        main.style.transform = 'translateY(0)';
    });
});
