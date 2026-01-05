/**
 * PDF Viewer Controller
 * Gère l'affichage des PDF dans un visualiseur intégré
 */

// Initialisation automatique si le visualiseur existe
document.addEventListener('DOMContentLoaded', function() {
    const viewer = document.getElementById('pdf-viewer');
    if (viewer) {
        initPDFViewer();
    }
});

export function initPDFViewer() {
    // Vérifier si le visualiseur existe
    const viewer = document.getElementById('pdf-viewer');
    if (!viewer) return;

    // Fonction pour afficher un PDF
    window.showPDF = function(pdfUrl, title) {
        const frame = document.getElementById('pdf-frame');
        const titleElement = document.getElementById('pdf-title');
        
        if (!frame || !titleElement) return;
        
        // Configurer le visualiseur
        titleElement.textContent = title;
        frame.src = pdfUrl;
        
        // Afficher avec animation
        viewer.style.display = 'block';
        setTimeout(() => {
            viewer.style.opacity = '1';
            viewer.style.transform = 'translateY(0)';
        }, 10);
        
        // Scroll vers le visualiseur
        viewer.scrollIntoView({ behavior: 'smooth' });
    };

    // Fonction pour fermer le visualiseur
    window.closePDF = function() {
        const frame = document.getElementById('pdf-frame');
        
        // Effet de fermeture
        viewer.style.opacity = '0';
        viewer.style.transform = 'translateY(20px)';
        
        setTimeout(() => {
            viewer.style.display = 'none';
            if (frame) frame.src = '';
        }, 300);
    };

    // Gestion des boutons de téléchargement
    document.querySelectorAll('[data-pdf-download]').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const url = this.dataset.pdfDownload;
            const filename = this.dataset.pdfFilename || 'document.pdf';
            
            downloadPDF(url, filename);
        });
    });

    function downloadPDF(url, filename) {
        const link = document.createElement('a');
        link.href = url;
        link.download = filename;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }
}