// Parcours dans un vrai navigateur (Playwright) : ce que PHPUnit ne voit pas, le JavaScript du site.
// Lancé par la CI (job « accessibilite », même site démarré) ; échoue si un parcours casse ou si une page lève une erreur JS.
//   npm install --no-save playwright && npx playwright install chromium
//   BASE_URL=http://127.0.0.1:8000 node tests/parcours.mjs
import { chromium } from 'playwright';
import assert from 'node:assert/strict';

const base = process.env.BASE_URL ?? 'http://127.0.0.1:8000';
const navigateur = await chromium.launch();
// Réseau au repos : les contrôleurs Stimulus chargés à la demande (terminal, comparateur…) sont branchés
const ouvrir = (onglet, chemin) => onglet.goto(base + chemin, { waitUntil: 'networkidle' });
const erreursJs = [];

const parcours = {
    'Palette Ctrl+K : chercher un projet et y aller': async (onglet) => {
        await ouvrir(onglet, '/');
        await onglet.keyboard.press('Control+K');
        await onglet.locator('#palette input').fill('pendu');
        await onglet.locator('#palette [role="option"]', { hasText: /pendu/i }).first().waitFor();
        await onglet.keyboard.press('Enter');
        await onglet.waitForURL(/\/projects\/pendu$/);
    },

    'Terminal : « ls projets » liste les projets': async (onglet) => {
        await ouvrir(onglet, '/terminal');
        await onglet.locator('[data-terminal-target="sortie"]', { hasText: 'help' }).waitFor(); // message de bienvenue : contrôleur branché
        await onglet.locator('[data-terminal-target="champ"]').fill('ls projets');
        await onglet.keyboard.press('Enter');
        await onglet.locator('[data-terminal-target="sortie"]', { hasText: /pendu/i }).waitFor();
    },

    'Comparateur : une offre fait ressortir les compétences': async (onglet) => {
        await ouvrir(onglet, '/competences/comparer');
        await onglet.locator('#offre').fill('Nous cherchons un développeur Symfony qui maîtrise Docker et Git.');
        const resultats = onglet.locator('[data-comparateur-target="resultats"] li');
        await resultats.first().waitFor();
        assert.match(await resultats.allInnerTexts().then((t) => t.join(' ')), /Docker/);
    },

    'Contact : le message part (jeton CSRF posé par le JavaScript)': async (onglet) => {
        await ouvrir(onglet, '/contact');
        await onglet.locator('#contact_nom').fill('Test navigateur');
        await onglet.locator('#contact_email').fill('navigateur@example.com');
        await onglet.locator('#contact_message').fill('Message envoyé par les tests de parcours.');
        await onglet.getByRole('button', { name: 'Envoyer' }).click();
        await onglet.locator('.contact-flash--success').waitFor();
    },

    'Langue : passer la page en anglais': async (onglet) => {
        await ouvrir(onglet, '/contact');
        await onglet.locator('.lang-switch').click();
        await onglet.locator('#langues a[hreflang="en"]').click();
        await onglet.waitForURL(/\/en\/contact$/);
        assert.equal(await onglet.getAttribute('html', 'lang'), 'en');
    },

    'Aucune erreur JavaScript sur les pages principales': async (onglet) => {
        for (const chemin of ['/', '/cerveau', '/projects', '/projects/frise', '/univers', '/competences', '/CV', '/livre-d-or', '/now', '/terminal', '/nexiste-pas']) {
            await onglet.goto(base + chemin);
            await onglet.waitForTimeout(300); // contrôleurs Stimulus chargés à la demande
        }
    },
};

let echecs = 0;
for (const [nom, test] of Object.entries(parcours)) {
    // fr-FR : sinon / redirige vers /en/ ; mouvements réduits : pas d'attente d'animation
    const contexte = await navigateur.newContext({ locale: 'fr-FR', reducedMotion: 'reduce' });
    const onglet = await contexte.newPage();
    onglet.setDefaultTimeout(10_000);
    onglet.on('pageerror', (e) => erreursJs.push(`${onglet.url()} : ${e.message}`));
    const avant = erreursJs.length;
    try {
        await test(onglet);
        assert.deepEqual(erreursJs.slice(avant), [], 'erreur JavaScript');
        console.log(`✓ ${nom}`);
    } catch (e) {
        echecs++;
        console.log(`✗ ${nom}\n    ${e.message.split('\n').slice(0, 6).join('\n    ')}`);
    }
    await contexte.close();
}

await navigateur.close();
console.log(echecs ? `\n${echecs} parcours en échec` : '\nTous les parcours passent');
process.exit(echecs ? 1 : 0);
