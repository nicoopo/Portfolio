/*
 * Trou noir en shader WebGL : pour chaque pixel, on suit le rayon lumineux à rebours depuis la caméra,
 * courbé par la gravité (géométrie de Schwarzschild). Un rayon qui passe trop près tombe dans le trou (ombre),
 * un rayon qui traverse le plan du disque d'accrétion en prend la lumière (plusieurs fois s'il fait le tour :
 * ce sont les images du disque repliées au-dessus et en dessous de l'ombre) ; une lueur suit le disque et un
 * mince anneau de photons borde l'ombre. Le côté droit du disque vient vers nous : plus vif et plus blanc (Doppler).
 * Au clic, il aspire son disque, implose, explose en supernova et laisse naître un soleil ; un clic sur le soleil
 * le fait s'effondrer en trou noir.
 * Unités : rayon de Schwarzschild = 1. Utilisé par la page projets ; à défaut de WebGL, black_hole.js (2D).
 */

const VERTEX = `
attribute vec2 position;
void main() { gl_Position = vec4(position, 0.0, 1.0); }
`;

const FRAGMENT = `
precision highp float;

uniform vec2 uCenter;   // centre du trou noir, en pixels du canvas (origine en bas à gauche)
uniform vec2 uSize;     // taille du canvas, en pixels
uniform float uRadius;  // rayon apparent de l'ombre, en pixels
uniform float uTime;
uniform float uTilt;    // élévation de la caméra au-dessus du disque, en radians
uniform float uInflow;  // aspiration (clic) : de combien les grains sont descendus vers le trou
uniform float uGrains;  // opacité des grains : 0 juste après l'aspiration, le temps que le disque se reforme
uniform float uFlash;   // éclair de l'anneau quand le trou noir a tout avalé
uniform float uHole;    // taille du trou noir (0 → 1) : il implose, puis renaît de l'effondrement du soleil
uniform float uSpark;   // point brillant au centre, au moment de l'implosion et de l'effondrement
uniform float uNova;    // secondes depuis l'explosion de la supernova, négatif hors explosion
uniform float uSun;     // taille du soleil (0 → 1)

const float SHADOW = 2.598;  // rayon apparent de l'ombre : 3√3/2 rayons de Schwarzschild
const float INNER = 3.0;     // bord intérieur du disque (dernière orbite stable)
const float OUTER = 7.5;
const float GRAIN = 0.45;    // taille des cases où se placent les grains du disque
const float FAR = 60.0;      // distance de la caméra
const float ZONE = 10.0;     // au-delà, la lumière va presque droit : pas besoin de suivre le rayon
const float PI = 3.14159265;

float hash(vec2 p) { return fract(sin(dot(p, vec2(127.1, 311.7))) * 43758.5453); }

// Bruit lisse, périodique en x (période entière) : pas de couture à l'angle ±π
float noise(vec2 p, float period) {
    vec2 i = floor(p), f = fract(p);
    vec2 u = f * f * (3.0 - 2.0 * f);
    float x0 = mod(i.x, period), x1 = mod(i.x + 1.0, period);
    return mix(mix(hash(vec2(x0, i.y)), hash(vec2(x1, i.y)), u.x),
               mix(hash(vec2(x0, i.y + 1.0)), hash(vec2(x1, i.y + 1.0)), u.x), u.y);
}

// Couleurs du site, du bord intérieur (chaud) au bord extérieur (froid)
vec3 palette(float t) {
    vec3 a = vec3(1.0, 0.957, 0.839), b = vec3(1.0, 0.702, 0.278), c = vec3(1.0, 0.373, 0.635), d = vec3(0.498, 0.353, 0.941);
    return t < 0.33 ? mix(a, b, t / 0.33) : t < 0.66 ? mix(b, c, (t - 0.33) / 0.33) : mix(c, d, (t - 0.66) / 0.34);
}

// Décalage de fréquence d'un point du disque : > 1 côté droit (vient vers la caméra), < 1 côté gauche
float shift(vec3 p, vec3 dir) {
    float r = length(p.xz);
    float beta = sqrt(0.5 / r); // vitesse de Kepler, en fraction de c
    vec3 tangent = normalize(vec3(p.z, 0.0, -p.x));
    float doppler = 1.0 / (inversesqrt(1.0 - beta * beta) * (1.0 - beta * dot(tangent, -dir)));
    return doppler * sqrt(1.0 - 1.0 / r); // et le décalage gravitationnel
}

// Lumière du disque là où le rayon le traverse ; dir = sens du rayon (de la caméra vers le disque).
// Comme la version 2D : des grains de couleur qui tournent (Kepler : l'intérieur plus vite), sur une bande pâle ;
// grainy (0 → 1) : sous l'ombre, la bande seule, lisse (les grains y faisaient artificiel)
vec4 disk(vec3 hit, vec3 dir, float grainy) {
    float r = length(hit.xz);
    if (r < INNER || r > OUTER) return vec4(0.0);
    float t = (r - INNER) / (OUTER - INNER);
    float profile = smoothstep(INNER, INNER + 0.3, r) * pow(INNER / r, 2.0) * (1.0 - smoothstep(0.6, 1.0, t));
    vec3 colour = palette(t) * profile * mix(1.0, 0.5, grainy) * (0.4 + 0.6 * uGrains);
    float cover = profile * mix(0.8, 0.3, grainy);
    if (grainy < 0.01) return vec4(colour * pow(shift(hit, dir), 2.0), cover);

    // Trois grilles décalées d'anneaux découpés en cases ; au plus un grain par case
    for (int layer = 0; layer < 3; layer++) {
        float offset = float(layer) * GRAIN / 3.0;
        // Aspiration : la grille glisse vers l'intérieur ; chaque anneau garde son rayon d'origine (home),
        // donc sa vitesse et ses grains, et s'enroule en spirale en descendant
        float band = floor((r - INNER + offset + uInflow) / GRAIN);
        float home = INNER - offset + (band + 0.5) * GRAIN;
        if (home > OUTER + GRAIN) continue; // au-delà du disque d'origine : rien, le disque se vide
        float ring = home - uInflow;
        float cells = floor(2.0 * PI * home / GRAIN);
        // Tour parcouru (l'anneau tourne d'un bloc, l'intérieur plus vite), plus la spirale de l'aspiration
        float turn = uTime * 0.25 * pow(home / INNER, -1.5) + uInflow * 0.12 * INNER / max(ring, 1.0);
        float cell = floor((atan(hit.z, hit.x) / (2.0 * PI) + turn) * cells);
        vec2 id = vec2(mod(cell, cells), band + 37.0 * float(layer));
        if (hash(id) > mix(0.75, 0.25, t)) continue; // plus dense près du bord intérieur

        float angle = 2.0 * PI * ((cell + 0.3 + 0.4 * hash(id + 1.3)) / cells - turn);
        vec2 centre = (INNER - offset - uInflow + (band + 0.3 + 0.4 * hash(id + 2.7)) * GRAIN) * vec2(cos(angle), sin(angle));
        // Distance à peu près telle qu'on la voit (le disque est vu presque par la tranche) : des points presque ronds à l'écran
        vec2 delta = hit.xz - centre;
        float size = 0.03 + 0.035 * hash(id + 4.1);
        float seen = length(vec2(delta.x, delta.y * sin(uTilt) * 2.0)) / size;
        // Cœur net et petite lueur autour ; chaque grain scintille à son rythme
        float spot = 1.0 - smoothstep(0.3, 1.0, seen) + 0.3 * exp(-seen * 0.9);
        float twinkle = 0.6 + 0.4 * sin(uTime * (1.5 + 3.0 * hash(id + 8.3)) + 6.28 * hash(id + 9.7));
        colour += palette(clamp((length(centre) - INNER) / (OUTER - INNER) + (hash(id + 5.9) - 0.5) * 0.25, 0.0, 1.0)) * spot * twinkle * 1.4 * grainy * uGrains;
        cover += spot * 0.8 * grainy * uGrains;
    }
    return vec4(colour * pow(shift(hit, dir), 2.0), clamp(cover, 0.0, 1.0));
}

// Trou noir de rayon apparent radius (pixels) ; px = position par rapport au centre. Couleur prémultipliée.
vec4 blackHole(vec2 px, float radius) {
    vec2 screen = px / radius * SHADOW; // paramètre d'impact, en rayons de Schwarzschild
    float b = length(screen);

    vec3 eye = FAR * vec3(0.0, sin(uTilt), -cos(uTilt));
    vec3 forward = -normalize(eye), right = vec3(1.0, 0.0, 0.0), up = cross(forward, right);
    vec3 dir = normalize(forward * FAR + right * screen.x + up * screen.y);

    // Avance en ligne droite jusqu'à la zone où la gravité compte ; au-delà, rien à dessiner
    float along = -dot(eye, dir);
    float gap = ZONE * ZONE - (dot(eye, eye) - along * along);
    if (gap < 0.0) return vec4(0.0);

    // Sous l'ombre (avant du disque qui passe devant, image déviée du dessous) : pas de grains
    float grainy = 1.0 - (1.0 - smoothstep(-0.3, 0.4, screen.y)) * (1.0 - smoothstep(SHADOW * 0.9, SHADOW * 1.5, abs(screen.x)));

    vec3 colour = vec3(0.0), glow = vec3(0.0);
    float cover = 0.0; // ce que le disque cache des images plus lointaines
    bool captured = false;
    vec3 pos = eye + dir * (along - sqrt(gap));
    vec3 vel = dir;
    float h2 = dot(cross(pos, vel), cross(pos, vel)); // moment cinétique, constant
    for (int i = 0; i < 300; i++) {
        float r = length(pos);
        if (r < 1.0) { captured = true; break; }
        if (r > ZONE + 0.1 && dot(pos, vel) > 0.0) break;
        float ds = max(0.06 * r, 0.02);
        vel += -1.5 * h2 * pos / pow(r, 5.0) * ds;
        vec3 next = pos + normalize(vel) * ds;
        if (pos.y * next.y < 0.0) {
            vec3 hit = mix(pos, next, pos.y / (pos.y - next.y));
            vec4 d = disk(hit, normalize(vel), grainy);
            colour += d.rgb * (1.0 - cover);
            cover += d.a * (1.0 - cover);
        }
        // Lueur : le rayon qui frôle le disque en prend un peu de lumière (suit aussi ses images déviées)
        float rd = length(pos.xz);
        if (rd > INNER && rd < OUTER + 1.5) {
            float t = (rd - INNER) / (OUTER - INNER);
            glow += palette(clamp(t, 0.0, 1.0)) * pow(INNER / rd, 3.0) * pow(shift(pos, normalize(vel)), 2.0)
                  * exp(-abs(pos.y) * 3.5) * ds;
        }
        pos = next;
    }

    colour += glow * 0.15 * (0.4 + 0.6 * uGrains + 2.0 * uFlash) * (1.0 - cover);
    if (!captured) {
        // Anneau de photons qui borde l'ombre, plus vif à droite, et halo violet, comme la version 2D
        float edge = max(b - SHADOW, 0.0);
        vec3 ring = mix(vec3(1.0, 0.702, 0.278) * 0.5, vec3(1.0, 0.957, 0.839), clamp(screen.x / b * 0.5 + 0.5, 0.0, 1.0));
        colour += ring * (exp(-edge * 14.0) * 0.9 + exp(-edge * 4.0) * 0.25) * (1.0 + 3.0 * uFlash) * (1.0 - cover);
        colour += vec3(0.498, 0.353, 0.941) * 0.2 * (1.0 - smoothstep(SHADOW, ZONE, b));
    }

    colour = 1.0 - exp(-colour * 2.0); // les zones très vives saturent en douceur
    // Seule l'ombre est opaque (elle cache les projets de l'arrière) ; le reste n'est que lumière, ajoutée à la page
    return vec4(colour, captured ? 1.0 : max(colour.r, max(colour.g, colour.b)));
}

// Soleil de rayon radius (pixels) : surface qui bouillonne, assombrie au bord, et couronne qui ondule.
// Plus petit (naissance, effondrement), il est plus rose. Couleur prémultipliée ; le disque est opaque.
vec4 sun(vec2 px, float radius) {
    float d = length(px) / radius;
    float young = 1.0 - uSun;
    vec2 uv = px / radius;

    float mu = sqrt(max(1.0 - d * d, 0.0)); // 1 au centre du disque, 0 au bord
    vec2 flow = vec2(uTime * 0.08, uTime * 0.05);
    float cells = noise(uv * 5.0 + flow, 289.0) * 0.55 + noise(uv * 11.0 - flow * 1.7, 289.0) * 0.3 + noise(uv * 23.0 + flow * 2.3, 289.0) * 0.15;
    // Cellules chaudes (crème) sur fond orange, plus rose vers le bord
    vec3 base = palette(clamp(0.3 + (1.0 - mu) * 0.3 + young * 0.3, 0.0, 1.0));
    vec3 surface = mix(base, palette(0.0), cells * cells * mu) * (0.55 + 0.45 * mu) * 1.1;

    float angle = atan(px.y, px.x) / (2.0 * PI);
    float rays = noise(vec2(angle * 24.0, uTime * 0.35), 24.0) * noise(vec2(angle * 7.0, uTime * 0.2 + 3.0), 7.0);
    float beyond = max(d - 1.0, 0.0);
    vec3 corona = palette(clamp(0.38 + beyond * 0.35 + young * 0.3, 0.0, 1.0))
                * (exp(-beyond * 3.0) * 1.0 + exp(-beyond * 0.9) * 0.35 * (0.4 + 1.6 * rays));

    float inside = 1.0 - smoothstep(1.0 - 1.5 / radius, 1.0, d); // bord net, sans crénelage
    vec3 colour = 1.0 - exp(-(surface * inside + corona * (1.0 - inside)) * 1.6);
    return vec4(colour, max(inside, max(colour.r, max(colour.g, colour.b))));
}

// Supernova, s secondes après l'explosion : éclair, onde de choc, filaments de débris, puis nébuleuse qui s'éteint.
// Lumière seule (non saturée), ajoutée par-dessus
vec3 supernova(vec2 px, float s) {
    float d = length(px);
    float front = uRadius * (0.3 + s * 6.0);    // rayon de l'onde de choc
    float width = uRadius * (0.15 + s * 0.35);
    float angle = atan(px.y, px.x) / (2.0 * PI);
    float fade = exp(-s * 0.8);
    float tint = clamp(s * 0.3 + 0.25 * d / front, 0.0, 1.0); // blanc au début, puis orange, rose, violet

    float debris = noise(vec2(angle * 64.0, d / uRadius * 0.6 - s * 2.0), 64.0);
    float shell = exp(-pow((d - front) / width, 2.0)) * (0.5 + debris);
    float filaments = pow(noise(vec2(angle * 90.0, s * 0.5), 90.0), 4.0) * 3.0 * exp(-s * 0.9) * (1.0 - smoothstep(front * 0.2, front * 1.05, d));
    float core = exp(-d / (uRadius * (0.5 + s * 1.5))) * exp(-s * 1.8) * 3.0;
    float cloud = noise(px / uRadius * 0.8 + s * 0.1, 289.0) * (1.0 - smoothstep(0.0, front, d))
                * smoothstep(0.3, 1.5, s) * (1.0 - smoothstep(3.5, 5.5, s)) * 0.35;

    // S'éteint avant les bords du canvas, qu'on ne doit jamais voir
    vec2 pos = gl_FragCoord.xy;
    float margin = min(min(pos.x, uSize.x - pos.x), min(pos.y, uSize.y - pos.y));
    float edges = smoothstep(0.0, min(uSize.x, uSize.y) * 0.3, margin);
    return (palette(tint) * (shell * 1.6 + filaments) * fade + vec3(1.0, 0.957, 0.839) * core + palette(0.75) * cloud) * edges;
}

void main() {
    vec2 px = gl_FragCoord.xy - uCenter;
    vec4 result = vec4(0.0);
    if (uHole > 0.01) result = blackHole(px, uRadius * uHole);
    if (uSun > 0.01) {
        vec4 star = sun(px, uRadius * 1.05 * uSun);
        result = star + result * (1.0 - star.a);
    }

    // Lumières de passage : point brillant (implosion, effondrement) et supernova
    float d = length(px);
    vec3 light = vec3(1.0, 0.957, 0.839) * uSpark * (exp(-d / (uRadius * 0.12)) * 4.0 + exp(-d / (uRadius * 0.6)) * 0.6);
    if (uNova >= 0.0) light += supernova(px, uNova);
    light = 1.0 - exp(-light * 2.0);
    float glare = max(light.r, max(light.g, light.b));
    gl_FragColor = vec4(light + result.rgb * (1.0 - glare), max(result.a, glare));
}
`;

/**
 * Trou noir WebGL dans `canvas`, ou null si le navigateur n'a pas WebGL.
 * resize() suit la taille affichée ; draw(x, y, r, dt, tilt) : centre et rayon de l'ombre en pixels CSS, inclinaison
 * de la vue en radians (au-dessus du disque) ; il renvoie { pull, suck, events } : pull = rayon de l'orbite des projets
 * (1 au repos, 0 quand tout est avalé, plus de 1 quand l'explosion les souffle), suck = avancée de l'aspiration
 * avant l'explosion (0 → 1, pour avaler la page morceau par morceau ; 0 sinon), events = étapes franchies
 * ('boom', 'birth', 'reborn'), pour les sons. burst() : supernova (trou noir) ou effondrement (soleil). destroy() à la fin.
 */
export function createBlackHoleGL(canvas) {
    const gl = canvas.getContext('webgl', { premultipliedAlpha: true, antialias: false });
    if (!gl) return null;
    const program = link(gl);
    if (!program) return null;

    gl.useProgram(program);
    gl.bindBuffer(gl.ARRAY_BUFFER, gl.createBuffer());
    gl.bufferData(gl.ARRAY_BUFFER, new Float32Array([-1, -1, 3, -1, -1, 3]), gl.STATIC_DRAW); // un triangle couvre l'écran
    const position = gl.getAttribLocation(program, 'position');
    gl.enableVertexAttribArray(position);
    gl.vertexAttribPointer(position, 2, gl.FLOAT, false, 0, 0);
    const names = ['uCenter', 'uSize', 'uRadius', 'uTime', 'uTilt', 'uInflow', 'uGrains', 'uFlash', 'uHole', 'uSpark', 'uNova', 'uSun'];
    const u = Object.fromEntries(names.map((n) => [n, gl.getUniformLocation(program, n)]));

    // Le shader suit des centaines de pas par pixel : densité 1, net sans faire chauffer la carte
    let ratio = 1, time = 0;
    let state = 'hole';  // 'hole' ou 'sun', entre deux séquences
    let sequence = null; // { scenario, s } : séquence en cours, s secondes depuis le clic
    return {
        resize() {
            ratio = Math.min(window.devicePixelRatio || 1, 1.25);
            canvas.width = Math.round(canvas.clientWidth * ratio);
            canvas.height = Math.round(canvas.clientHeight * ratio);
            gl.viewport(0, 0, canvas.width, canvas.height);
        },
        /** Une séquence (supernova, effondrement) est en cours */
        get busy() {
            return sequence !== null;
        },
        burst() {
            if (!sequence) sequence = { scenario: state === 'hole' ? SUPERNOVA : COLLAPSE, s: 0 };
        },
        draw(x, y, r, dt = 0, tilt = 0.2) {
            let fx = REST[state], events = [];
            if (sequence) {
                const { scenario } = sequence, before = sequence.s;
                sequence.s += dt;
                fx = { ...REST.hole, ...scenario.at(sequence.s) };
                events = scenario.events.filter(([at]) => before < at && sequence.s >= at).map(([, name]) => name);
                if (sequence.s >= scenario.end) {
                    state = scenario.suivant;
                    sequence = null;
                }
            }
            time += dt * fx.spin;
            gl.uniform2f(u.uCenter, x * ratio, canvas.height - y * ratio);
            gl.uniform2f(u.uSize, canvas.width, canvas.height);
            gl.uniform1f(u.uRadius, r * ratio);
            gl.uniform1f(u.uTime, time);
            gl.uniform1f(u.uTilt, tilt);
            for (const name of ['inflow', 'grains', 'flash', 'hole', 'spark', 'nova', 'sun']) {
                gl.uniform1f(u[`u${name[0].toUpperCase()}${name.slice(1)}`], fx[name]);
            }
            gl.drawArrays(gl.TRIANGLES, 0, 3);
            return { pull: fx.pull, suck: fx.suck, events };
        },
        /** Rend le contexte WebGL au navigateur, qui n'en garde qu'une poignée à la fois */
        destroy() {
            gl.getExtension('WEBGL_lose_context')?.loseContext();
        },
    };
}

// Au repos : le trou noir et son disque, ou le soleil
const REST = {
    hole: { spin: 1, inflow: 0, grains: 1, flash: 0, hole: 1, spark: 0, nova: -1, sun: 0, pull: 1, suck: 0 },
    sun: { spin: 1, inflow: 0, grains: 0, flash: 0, hole: 0, spark: 0, nova: -1, sun: 1, pull: 1, suck: 0 },
};

const SUCK = 1.8;   // aspiration du disque, de plus en plus vite
const IMPLODE = 0.7; // le trou noir se contracte en un point et avale les projets
const BOOM = SUCK + IMPLODE;
const INFLOW = 5.1; // un peu plus que la largeur du disque (OUTER - INNER) : tout est avalé
const smooth = (a, b, x) => { const t = Math.min(Math.max((x - a) / (b - a), 0), 1); return t * t * (3 - 2 * t); };

const SUPERNOVA = {
    end: BOOM + 5.5,
    suivant: 'sun',
    events: [[BOOM, 'boom'], [BOOM + 1, 'birth']],
    at(s) {
        if (s < SUCK) {
            const e = (s / SUCK) ** 2;
            return { spin: 1 + 8 * e, inflow: e * INFLOW, flash: Math.max(0, (s - SUCK + 0.3) / 0.3), pull: 1 - 0.25 * e, suck: 0.3 * e };
        }
        if (s < BOOM) {
            const k = (s - SUCK) / IMPLODE;
            return { spin: 9, grains: 0, flash: 1, hole: 1 - k * k, spark: 1.5 * k * k, pull: 0.75 * (1 - k) ** 2, suck: 0.3 + 0.7 * k };
        }
        // Explosion : les projets, partis du centre, sont soufflés au-delà de leur orbite puis y reviennent (ressort amorti)
        const p = s - BOOM;
        return {
            grains: 0, hole: 0, nova: p, spark: Math.max(0, 1.5 * (1 - p * 4)), sun: smooth(0.8, 3.0, p),
            pull: 1 + Math.exp(-1.3 * p) * (-0.98 * Math.cos(3 * p) + 0.9 * Math.sin(3 * p)),
        };
    },
};

const SHRINK = 1.4; // le soleil s'effondre
const COLLAPSE = {
    end: SHRINK + 2.4,
    suivant: 'hole',
    events: [[SHRINK, 'reborn']],
    at(s) {
        if (s < SHRINK) {
            const k = s / SHRINK;
            return { grains: 0, hole: 0, sun: 1 - k * k, spark: 1.2 * k * k, pull: 1 - 0.3 * k };
        }
        // Le trou noir renaît : il grandit, son disque se reforme
        const q = s - SHRINK, refill = Math.min(q / 2.4, 1);
        return {
            spin: 1 + 4 * (1 - refill) ** 2, grains: refill * refill, hole: 1 - (1 - Math.min(q / 1.2, 1)) ** 3,
            flash: Math.max(0, 1 - q * 1.5), spark: Math.max(0, 1.2 * (1 - q * 4)), pull: 1 - 0.3 * Math.exp(-3 * q),
        };
    },
};

function link(gl) {
    const program = gl.createProgram();
    for (const [type, source] of [[gl.VERTEX_SHADER, VERTEX], [gl.FRAGMENT_SHADER, FRAGMENT]]) {
        const shader = gl.createShader(type);
        gl.shaderSource(shader, source);
        gl.compileShader(shader);
        if (!gl.getShaderParameter(shader, gl.COMPILE_STATUS)) {
            console.warn('Trou noir WebGL :', gl.getShaderInfoLog(shader));
            return null;
        }
        gl.attachShader(program, shader);
    }
    gl.linkProgram(program);
    return gl.getProgramParameter(program, gl.LINK_STATUS) ? program : null;
}
