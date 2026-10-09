/*
 * Service worker du portfolio (enregistré par assets/app.js) : rien n'est mis en cache, sauf la page hors ligne.
 * Une page demandée sans réseau affiche hors-ligne.html au lieu de l'erreur du navigateur.
 * Changer CACHE quand hors-ligne.html change, pour que les visiteurs reçoivent la nouvelle version.
 */
const CACHE = 'hors-ligne-v1';
const PAGE = '/hors-ligne.html';

self.addEventListener('install', (e) => {
    e.waitUntil(caches.open(CACHE).then((cache) => cache.add(PAGE)));
    self.skipWaiting();
});

self.addEventListener('activate', (e) => {
    e.waitUntil(caches.keys().then((noms) => Promise.all(noms.filter((nom) => nom !== CACHE).map((nom) => caches.delete(nom)))));
    self.clients.claim();
});

self.addEventListener('fetch', (e) => {
    if (e.request.mode !== 'navigate') return; // images, scripts, API : le navigateur s'en occupe comme d'habitude
    e.respondWith(fetch(e.request).catch(() => caches.match(PAGE)));
});
