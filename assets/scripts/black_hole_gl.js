/*
 * Trou noir en shader WebGL : pour chaque pixel, on suit le rayon lumineux à rebours depuis la caméra,
 * courbé par la gravité (géométrie de Schwarzschild). Un rayon qui passe trop près tombe dans le trou (ombre),
 * un rayon qui traverse le plan du disque d'accrétion en prend la lumière (plusieurs fois s'il fait le tour :
 * ce sont les images du disque repliées au-dessus et en dessous de l'ombre) ; une lueur suit le disque et un
 * mince anneau de photons borde l'ombre. Le côté droit du disque vient vers nous : plus vif et plus blanc (Doppler).
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

const float SHADOW = 2.598;  // rayon apparent de l'ombre : 3√3/2 rayons de Schwarzschild
const float INNER = 3.0;     // bord intérieur du disque (dernière orbite stable)
const float OUTER = 6.5;
const float FAR = 60.0;      // distance de la caméra
const float ZONE = 10.0;     // au-delà, la lumière va presque droit : pas besoin de suivre le rayon
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

// Décalage de fréquence d'un point du disque : > 1 côté droit (vient vers la caméra), < 1 côté gauche
float shift(vec3 p, vec3 dir) {
    float r = length(p.xz);
    float beta = sqrt(0.5 / r); // vitesse de Kepler, en fraction de c
    vec3 tangent = normalize(vec3(p.z, 0.0, -p.x));
    float doppler = 1.0 / (inversesqrt(1.0 - beta * beta) * (1.0 - beta * dot(tangent, -dir)));
    return doppler * sqrt(1.0 - 1.0 / r); // et le décalage gravitationnel
}

// Lumière du disque là où le rayon le traverse ; dir = sens du rayon (de la caméra vers le disque)
vec4 disk(vec3 hit, vec3 dir) {
    float r = length(hit.xz);
    if (r < INNER || r > OUTER) return vec4(0.0);
    float g = shift(hit, dir);
    float t = (r - INNER) / (OUTER - INNER);

    // Bord intérieur net et brillant, puis décroissance
    float profile = smoothstep(INNER, INNER + 0.1, r) * pow(INNER / r, 2.2) * (1.0 - smoothstep(0.55, 1.0, t));
    // Fins filaments étirés le long de l'orbite, l'intérieur tourne plus vite
    float angle = atan(hit.z, hit.x) / (2.0 * PI) + uTime * 0.35 * pow(r, -1.5);
    float fine = noise(vec2(angle * 64.0, r * 16.0), 64.0);
    float broad = noise(vec2(angle * 16.0, r * 5.0), 16.0);
    float filaments = 0.12 + 2.4 * pow(0.55 * fine + 0.45 * broad, 2.5);

    float light = profile * filaments * pow(g, 3.0) * 3.5;
    vec3 colour = palette(clamp(t * 1.4 + (1.0 - g) * 0.7, 0.0, 1.0));
    colour = mix(colour, vec3(1.0), clamp((g - 1.0) * 1.5 + pow(1.0 - t, 8.0) * 0.6, 0.0, 0.75));
    return vec4(colour * light, clamp(light * 2.5, 0.0, 1.0));
}

void main() {
    vec2 screen = (gl_FragCoord.xy - uCenter) / uRadius * SHADOW; // paramètre d'impact, en rayons de Schwarzschild
    float b = length(screen);

    vec3 eye = FAR * vec3(0.0, sin(uTilt), -cos(uTilt));
    vec3 forward = -normalize(eye), right = vec3(1.0, 0.0, 0.0), up = cross(forward, right);
    vec3 dir = normalize(forward * FAR + right * screen.x + up * screen.y);

    vec3 colour = vec3(0.0), glow = vec3(0.0);
    float cover = 0.0; // ce que le disque cache des images plus lointaines
    bool captured = false;

    // Avance en ligne droite jusqu'à la zone où la gravité compte ; au-delà, rien à dessiner
    float along = -dot(eye, dir);
    float gap = ZONE * ZONE - (dot(eye, eye) - along * along);
    if (gap < 0.0) discard;
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
            colour += d.rgb * (1.0 - cover);
            cover += d.a * (1.0 - cover);
        }
        // Lueur : le rayon qui frôle le disque en prend un peu de lumière (suit aussi ses images déviées)
        float rd = length(pos.xz);
        if (rd > INNER && rd < OUTER + 1.5) {
            float t = (rd - INNER) / (OUTER - INNER);
            glow += palette(clamp(t, 0.0, 1.0)) * pow(INNER / rd, 3.0) * pow(shift(pos, normalize(vel)), 3.0)
                  * exp(-abs(pos.y) * 3.5) * ds;
        }
        pos = next;
    }

    colour += glow * 0.22 * (1.0 - cover);
    // Anneau de photons : un mince trait de lumière qui borde l'ombre
    if (!captured) colour += vec3(1.0, 0.86, 0.66) * exp(-max(b - SHADOW, 0.0) * 14.0) * 0.9 * (1.0 - cover);

    colour = 1.0 - exp(-colour * 2.0); // les zones très vives saturent en douceur
    // Seule l'ombre est opaque (elle cache les projets de l'arrière) ; le reste n'est que lumière, ajoutée à la page
    // (canvas en alpha prémultiplié : opacité = couleur la plus vive)
    float alpha = captured ? 1.0 : max(colour.r, max(colour.g, colour.b));
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
    const u = Object.fromEntries(['uCenter', 'uRadius', 'uTime', 'uTilt'].map((n) => [n, gl.getUniformLocation(program, n)]));
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
