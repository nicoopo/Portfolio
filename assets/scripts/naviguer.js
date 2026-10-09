/**
 * Navigation déclenchée par un script, comme un vrai clic de lien : la transition trou noir
 * (page_transition.js) s'applique comme partout.
 * Même page, autre ancre (neurone du cerveau) : le navigateur ne recharge pas et la page ne relit pas l'ancre, on recharge.
 */
export function naviguer(url) {
    const lien = Object.assign(document.createElement('a'), { href: url, hidden: true });
    document.body.append(lien);
    lien.click();
    lien.remove();
    const cible = new URL(url, location.href);
    if (cible.pathname === location.pathname && cible.hash) location.reload();
}
