// Audit d'accessibilité (axe-core) de chaque page publique, en français et en anglais, en thème sombre et clair.
// Lancé par la CI (job « accessibilite ») sur le site démarré en local ; échoue s'il reste une violation.
//   npm install --no-save playwright @axe-core/playwright && npx playwright install chromium
//   BASE_URL=http://127.0.0.1:8000 node tests/accessibilite.mjs
import { chromium } from 'playwright';
import { AxeBuilder } from '@axe-core/playwright';

const base = process.env.BASE_URL ?? 'http://127.0.0.1:8000';
const pages = ['/', '/cerveau', '/projects', '/projects/portfolio', '/univers', '/competences', '/CV', '/contact',
    '/mentions-legales', '/confidentialite', '/nexiste-pas'];

const navigateur = await chromium.launch();
let total = 0;

// Les deux thèmes (le site suit celui du système) ; mouvements réduits : les contrastes sont mesurés
// sur le texte final, pas en plein fondu d'apparition
for (const theme of ['dark', 'light']) {
    const contexte = await navigateur.newContext({ locale: 'fr-FR', colorScheme: theme, reducedMotion: 'reduce' }); // fr-FR : sinon / redirige vers /en/
    const onglet = await contexte.newPage();
    console.log(`
Thème ${theme === 'dark' ? 'sombre' : 'clair'}`);
    for (const chemin of pages.flatMap((p) => [p, '/en' + (p === '/' ? '' : p)])) {
        await onglet.goto(base + chemin);
        // Fin des apparitions en fondu (le CV en a qui ignorent les mouvements réduits) ; les animations en boucle ne finissent jamais
        // (deux images d'abord : les transitions du premier rendu doivent avoir démarré)
        await onglet.evaluate(async () => {
            await new Promise((fin) => requestAnimationFrame(() => requestAnimationFrame(fin)));
            await Promise.all(document.getAnimations()
                .filter((a) => a.effect?.getTiming().iterations !== Infinity)
                .map((a) => a.finished.catch(() => {})));
        });
        const { violations } = await new AxeBuilder({ page: onglet })
            .withTags(['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa'])
            .analyze();
        total += violations.length;
        console.log(`${violations.length ? '✗' : '✓'} ${chemin}`);
        for (const v of violations) {
            console.log(`    [${v.impact}] ${v.id} : ${v.help} (${v.nodes.length} élément(s))`);
            for (const n of v.nodes.slice(0, 3)) console.log(`        ${n.target.join(' ')}`);
        }
    }
    await contexte.close();
}

await navigateur.close();
console.log(total ? `\n${total} violation(s) d'accessibilité` : '\nAucune violation');
process.exit(total ? 1 : 0);
