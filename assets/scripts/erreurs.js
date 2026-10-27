// Erreurs JavaScript → GlitchTip, via /monitoring (MonitoringController) : GlitchTip n'est pas joignable d'Internet.
// Le SDK Sentry (~260 Ko gzip) n'est chargé qu'à la première erreur : tant que tout va bien, aucune page ne le paie.
// data-sentry-dsn n'est posé qu'en prod (twig.yaml).
const dsn = document.body.dataset.sentryDsn;

if (dsn) {
    const enAttente = [];
    const surErreur = (event) => noter(event.error ?? event.message);
    const surRejet = (event) => noter(event.reason);

    function noter(erreur) {
        enAttente.push(erreur);
        if (enAttente.length > 1) {
            return; // SDK déjà en cours de chargement
        }
        import('@sentry/browser').then((Sentry) => {
            // Le SDK capte lui-même les erreurs suivantes : on retire nos écouteurs pour ne rien envoyer deux fois
            removeEventListener('error', surErreur);
            removeEventListener('unhandledrejection', surRejet);
            Sentry.init({ dsn, tunnel: '/monitoring' });
            enAttente.forEach((e) => Sentry.captureException(e));
        }).catch(() => {}); // SDK injoignable : l'erreur est perdue, le site marche pareil
    }

    addEventListener('error', surErreur);
    addEventListener('unhandledrejection', surRejet);
}
