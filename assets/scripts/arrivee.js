/*
 * Globe des visites (/stats) : à l'arrivée sur le site, le fuseau horaire du navigateur (« Europe/Paris ») part au
 * serveur (ArriveeController). Une fois par arrivée : pas en passant d'une page à l'autre, ni en rechargeant,
 * ni en revenant en arrière. Rien n'est gardé dans le navigateur.
 */
const url = document.querySelector('meta[name="arrivee"]')?.content;
const navigation = performance.getEntriesByType('navigation')[0];
const depuisLeSite = (() => { try { return new URL(document.referrer).host === location.host; } catch { return false; } })();
const fuseau = (() => { try { return Intl.DateTimeFormat().resolvedOptions().timeZone; } catch { return null; } })();

if (url && fuseau && !depuisLeSite && navigation?.type === 'navigate') {
    // Sans réseau, tant pis : ce n'est qu'une statistique
    fetch(url, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ fuseau }), keepalive: true }).catch(() => {});
}
