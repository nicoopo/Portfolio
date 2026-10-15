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

    'Transition de page : la page arrive avec l\'effet, sans transform qui fasse défiler les éléments fixes': async (onglet) => {
        await onglet.emulateMedia({ reducedMotion: 'no-preference' });
        await ouvrir(onglet, '/projects');
        await onglet.evaluate(() => { const a = Object.assign(document.createElement('a'), { href: '/CV' }); document.body.append(a); a.click(); });
        await onglet.waitForURL(/\/CV$/);
        assert.ok(await onglet.getAttribute('html', 'data-arrivee'), 'effet de la page précédente');
        await onglet.waitForTimeout(800); // fin de l'animation d'arrivée
        await onglet.evaluate(() => scrollTo(0, 800));
        assert.equal(await onglet.locator('main').evaluate((m) => getComputedStyle(m).transform), 'none');
        assert.equal(await onglet.locator('.theme-toggle').evaluate((t) => Math.round(t.getBoundingClientRect().top)), 90);
    },

    'Code Konami au doigt : glisser ↑ ↑ ↓ ↓ ← → ← →, toucher deux fois, et les météores tombent': async (onglet) => {
        await onglet.emulateMedia({ reducedMotion: 'no-preference' });
        await ouvrir(onglet, '/projects');
        await onglet.evaluate(() => {
            // Un glissement sur deux est annulé (touchcancel) comme quand le navigateur fait défiler la page
            const geste = ([dx, dy], i) => {
                const toucher = (x, y) => [new Touch({ identifier: 1, target: document.body, clientX: x, clientY: y })];
                const fin = dx || dy ? (i % 2 ? 'touchcancel' : 'touchend') : 'touchend';
                document.body.dispatchEvent(new TouchEvent('touchstart', { bubbles: true, touches: toucher(200, 300), changedTouches: toucher(200, 300) }));
                if (dx || dy) document.body.dispatchEvent(new TouchEvent('touchmove', { bubbles: true, touches: toucher(200 + dx / 5, 300 + dy / 5), changedTouches: toucher(200 + dx / 5, 300 + dy / 5) }));
                document.body.dispatchEvent(new TouchEvent(fin, { bubbles: true, touches: [], changedTouches: toucher(200 + dx, 300 + dy) }));
            };
            [[0, -100], [0, -100], [0, 100], [0, 100], [-100, 0], [100, 0], [-100, 0], [100, 0], [0, 0], [0, 0]].forEach(geste);
        });
        await onglet.locator('canvas.pluie-meteores').waitFor({ state: 'attached' });
    },

    'Cerveau : piloter le vaisseau, puis Échap rend la caméra': async (onglet) => {
        await ouvrir(onglet, '/cerveau');
        const bouton = onglet.locator('[data-brain-target="exploreButton"]');
        await bouton.click();
        await onglet.locator('[data-brain-target="hint"]', { hasText: 'ZQSD' }).waitFor();
        await onglet.keyboard.down('ArrowUp');
        await onglet.waitForTimeout(300);
        await onglet.keyboard.up('ArrowUp');
        await onglet.keyboard.press('Escape');
        assert.equal(await bouton.getAttribute('aria-pressed'), 'false');
        assert.equal(await bouton.textContent(), 'Explorer en vaisseau');
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
    // Pas de carte graphique en CI : qualité basse, comme le site la choisit pour un tel visiteur (preferences.js) ;
    // sinon le shader du trou noir de /projects gèle la page au-delà des délais
    await contexte.addInitScript(() => localStorage.setItem('preferences', JSON.stringify({ qualite: 'basse' })));
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
