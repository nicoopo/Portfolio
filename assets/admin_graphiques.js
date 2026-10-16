/*
 * Graphiques du tableau de bord de l'admin (templates/admin/tableau_de_bord.html.twig), en barres :
 * données dans data-graphique = { labels, series: [{ label, data }] }, empilées s'il y a plusieurs séries.
 */
import { BarController, BarElement, CategoryScale, Chart, Legend, LinearScale, Tooltip } from 'chart.js';

Chart.register(BarController, BarElement, CategoryScale, Legend, LinearScale, Tooltip);

// ponytail: thème lu au chargement ; changer de thème dans l'admin demande de recharger la page
const sombre = document.body.classList.contains('ea-dark-scheme');
// Palette vérifiée (daltonisme, contraste) en clair et en sombre ; ordre fixe : la couleur suit la série
const COULEURS = sombre
    ? ['#3987e5', '#d95926', '#199e70', '#c98500', '#d55181']
    : ['#2a78d6', '#eb6834', '#1baf7a', '#eda100', '#e87ba4'];
Chart.defaults.color = sombre ? '#c3c2b7' : '#52514e';
Chart.defaults.font.family = getComputedStyle(document.body).fontFamily;

document.querySelectorAll('canvas[data-graphique]').forEach((canvas) => {
    const { labels, series } = JSON.parse(canvas.dataset.graphique);
    const empile = series.length > 1;
    new Chart(canvas, {
        type: 'bar',
        data: {
            labels,
            datasets: series.map((serie, i) => ({
                ...serie,
                backgroundColor: COULEURS[i],
                // Liseré transparent : le fond de la page sépare les segments empilés
                borderColor: 'transparent',
                borderWidth: empile ? { top: 2 } : 0,
                borderRadius: empile ? 0 : 4,
                maxBarThickness: 32,
            })),
        },
        options: {
            aspectRatio: 2,
            interaction: { mode: 'index', intersect: false },
            scales: {
                x: { stacked: empile, grid: { display: false } },
                y: {
                    stacked: empile,
                    beginAtZero: true,
                    ticks: { precision: 0 },
                    border: { display: false },
                    grid: { color: sombre ? '#2c2c2a' : '#e1e0d9' },
                },
            },
            plugins: {
                legend: { display: empile, position: 'bottom', labels: { boxWidth: 12 } },
                tooltip: { filter: (item) => item.raw > 0 }, // au survol : seulement les séries présentes ce jour-là
            },
        },
    });
});
