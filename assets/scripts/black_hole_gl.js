/*
 * Trou noir en shader WebGL : pour chaque pixel, on suit le rayon lumineux à rebours depuis la caméra,
 * courbé par la gravité (géométrie de Schwarzschild). Un rayon qui passe trop près tombe dans le trou (ombre),
 * un rayon qui traverse le plan du disque d'accrétion en prend la lumière (plusieurs fois s'il fait le tour :
 * ce sont les images du disque repliées au-dessus et en dessous de l'ombre), les autres repartent vers les
 * étoiles du fond, déviées. Le côté droit du disque vient vers nous : plus vif et plus blanc (Doppler).
 * Unités : rayon de Schwarzschild = 1. Utilisé par la page projets ; à défaut de WebGL, black_hole.js (2D).
 */

const VERTEX = `
attribute vec2 position;
void main() { gl_Position = vec4(position, 0.0, 1.0); }
`;

const FRAGMENT = `
precision highp float;

uniform vec2 uCenter;   // centre du trou noir, en pixels du canvas (origine en bas à gauche)
uniform float uRadius;  // rayon apparent de l'ombre, en pixels
uniform float uTime;
uniform float uTilt;    // élévation de la caméra au-dessus du disque, en radians
uniform float uLight;   // 1 sur le thème clair : étoiles foncées

const float SHADOW = 2.598;  // rayon apparent de l'ombre : 3√3/2 rayons de Schwarzschild
const float INNER = 3.0;     // bord intérieur du disque (dernière orbite stable)
const float OUTER = 9.0;
const float FAR = 60.0;      // distance de la caméra
const float ZONE = 14.0;     // au-delà, la lumière va presque droit : pas besoin de suivre le rayon
const float PI = 3.14159265;

float hash(vec2 p) { return fract(sin(dot(p, vec2(127.1, 311.7))) * 43758.5453); }

// Bruit lisse, périodique en x (période entière) : le disque n'a pas de couture à l'angle ±π
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

// Étoiles du fond, selon la direction finale du rayon
float stars(vec3 dir) {
    vec2 uv = vec2(atan(dir.z, dir.x), asin(clamp(dir.y, -1.0, 1.0))) * 60.0;
    vec2 cell = floor(uv);
    float h = hash(cell);
    if (h < 0.96) return 0.0;
    vec2 pos = vec2(hash(cell + 7.1), hash(cell + 3.7));
    return (1.0 - smoothstep(0.0, 0.25, length(fract(uv) - pos))) * (h - 0.96) * 20.0;
}

// Lumière du disque là où le rayon le traverse ; dir = sens du rayon (de la caméra vers le disque)
vec4 disk(vec3 hit, vec3 dir) {
    float r = length(hit.xz);
    if (r < INNER || r > OUTER) return vec4(0.0);
    // Vitesse de Kepler (en fraction de c), sens : le côté droit (x > 0) vient vers la caméra
    float beta = sqrt(0.5 / r);
    vec3 tangent = normalize(vec3(hit.z, 0.0, -hit.x));
    float doppler = 1.0 / (inversesqrt(1.0 - beta * beta) * (1.0 - beta * dot(tangent, -dir)));
    float g = doppler * sqrt(1.0 - 1.0 / r); // et le décalage gravitationnel

    // Profil : nul au bord intérieur, maximum un peu après, puis décroît
    float t = (r - INNER) / (OUTER - INNER);
    float profile = pow(INNER / r, 2.0) * pow(1.0 - sqrt(INNER / r), 0.4) * (1.0 - smoothstep(0.8, 1.0, t));
    // Filaments qui tournent, l'intérieur plus vite
    float angle = atan(hit.z, hit.x) / (2.0 * PI) + uTime * 0.35 * pow(r, -1.5);
    float streaks = 0.45 + 0.55 * noise(vec2(angle * 24.0, r * 3.0), 24.0) * noise(vec2(angle * 6.0, r * 0.7), 6.0);

    float light = profile * streaks * pow(g, 3.0) * 6.0;
    vec3 colour = palette(clamp(t * 1.3 - (g - 1.0) * 0.8, 0.0, 1.0));
    return vec4(colour * light, clamp(light * 1.5, 0.0, 0.9));
}

void main() {
    vec2 screen = (gl_FragCoord.xy - uCenter) / uRadius * SHADOW; // paramètre d'impact, en rayons de Schwarzschild
    float b = length(screen);

    vec3 eye = FAR * vec3(0.0, sin(uTilt), -cos(uTilt));
    vec3 forward = -normalize(eye), right = vec3(1.0, 0.0, 0.0), up = cross(forward, right);
    vec3 dir = normalize(forward * FAR + right * screen.x + up * screen.y);

    vec3 colour = vec3(0.0);
    float alpha = 0.0;
    bool captured = false;

    // Avance en ligne droite jusqu'à la zone où la gravité compte
    float along = -dot(eye, dir);
    float gap = ZONE * ZONE - (dot(eye, eye) - along * along);
    if (gap < 0.0) {
        // Loin du trou : petite déviation vers lui, 2/b radians
        dir = normalize(dir - normalize(eye + dir * along) * 2.0 / b);
    } else {
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
                vec4 d = disk(hit, normalize(vel));
                colour += d.rgb * (1.0 - alpha);
                alpha += d.a * (1.0 - alpha);
            }
            pos = next;
        }
        dir = normalize(vel);
    }

    if (captured) {
        alpha = 1.0; // l'ombre cache ce qui est derrière (les projets de l'arrière)
    } else {
        // Étoiles déviées et halo violet, qui s'effacent loin du trou pour se fondre dans la page
        float fade = 1.0 - smoothstep(SHADOW * 1.5, ZONE + 2.0, b);
        float star = stars(dir) * fade;
        float halo = 0.25 * exp(-(b - SHADOW) * 0.45) * fade;
        vec3 starColour = mix(vec3(1.0), vec3(0.25, 0.18, 0.45), uLight);
        colour += (starColour * star + vec3(0.498, 0.353, 0.941) * halo) * (1.0 - alpha);
        alpha += max(star, halo * 0.6) * (1.0 - alpha);
    }

    colour = 1.0 - exp(-colour * 1.6); // les zones très vives saturent en douceur
    // Canvas en alpha prémultiplié : la couleur ne dépasse pas l'opacité
    alpha = max(alpha, max(colour.r, max(colour.g, colour.b)));
    gl_FragColor = vec4(colour, alpha);
}
`;

/**
 * Trou noir WebGL dans `canvas`, ou null si le navigateur n'a pas WebGL.
 * resize() suit la taille affichée ; draw(x, y, r, dt) : centre et rayon de l'ombre en pixels CSS ; destroy() à la fin.
 */
export function createBlackHoleGL(canvas, { tilt = 0.2 } = {}) {
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
    const u = Object.fromEntries(['uCenter', 'uRadius', 'uTime', 'uTilt', 'uLight'].map((n) => [n, gl.getUniformLocation(program, n)]));
    gl.uniform1f(u.uTilt, tilt);

    // Le shader suit des centaines de pas par pixel : densité 1, net sans faire chauffer la carte
    let ratio = 1, time = 0;
    return {
        resize() {
            ratio = Math.min(window.devicePixelRatio || 1, 1.25);
            canvas.width = Math.round(canvas.clientWidth * ratio);
            canvas.height = Math.round(canvas.clientHeight * ratio);
            gl.viewport(0, 0, canvas.width, canvas.height);
        },
        draw(x, y, r, dt = 0) {
            time += dt;
            gl.uniform2f(u.uCenter, x * ratio, canvas.height - y * ratio);
            gl.uniform1f(u.uRadius, r * ratio);
            gl.uniform1f(u.uTime, time);
            gl.uniform1f(u.uLight, document.documentElement.dataset.theme === 'light' ? 1 : 0);
            gl.drawArrays(gl.TRIANGLES, 0, 3);
        },
        /** Rend le contexte WebGL au navigateur, qui n'en garde qu'une poignée à la fois */
        destroy() {
            gl.getExtension('WEBGL_lose_context')?.loseContext();
        },
    };
}

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
