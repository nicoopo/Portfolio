/**
 * Envoi refusé par le serveur (contact, livre d'or) : la page se recharge en haut, et sur téléphone les erreurs
 * sont sous le bas de l'écran. On amène le visiteur au premier champ en erreur, son message juste au-dessus.
 */
document.addEventListener('DOMContentLoaded', () => {
    const champ = document.querySelector('main form [aria-invalid="true"]');
    if (!champ) return;
    champ.closest('div')?.scrollIntoView({ block: 'center' });
    champ.focus({ preventScroll: true });
});
