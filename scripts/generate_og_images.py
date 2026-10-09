#!/usr/bin/env python3
"""Generate 1200x630 Open Graph cards for portfolio projects (and articles).

Style matches the site's "Univers de Nico" cosmic-neural brand: dark navy
starfield, glowing neural nodes, cyan/purple/gold accents (see
assets/images/og-cerveau.jpg and assets/images/nico-symbol.svg).

Usage:
    python3 scripts/generate_og_images.py [--projects projects.json] [--out assets/images/og]

Each card is written to <out>/projets/<slug>.png or <out>/articles/<slug>.png.
If no projects.json is supplied, the script reads project rows embedded below
(kept in sync with the DB seeds in migrations/).
"""

from __future__ import annotations

import argparse
import json
import math
import os
import random
from pathlib import Path

from PIL import Image, ImageDraw, ImageFont

W, H = 1200, 630

# Brand palette (from the live site / nico-symbol.svg)
BG_TOP = (5, 10, 20)        # #050a14
BG_BOTTOM = (11, 16, 38)    # #0b1026
CYAN = (0, 255, 255)
BLUE = (29, 60, 253)        # #1D3CFD
PURPLE = (102, 16, 242)     # #6610f2
MAGENTA = (214, 51, 132)    # #d63384
GOLD = (255, 193, 7)        # #ffc107
ORANGE = (253, 126, 20)     # #fd7e14
GREEN = (64, 192, 87)       # #40c057
WHITE = (255, 255, 255)
GREY = (170, 185, 210)

FONT_DIR = Path("/usr/share/fonts/truetype/lato")
FONT_REG = FONT_DIR / "Lato-Regular.ttf"
FONT_BOLD = FONT_DIR / "Lato-Bold.ttf"
FONT_BLACK = FONT_DIR / "Lato-Black.ttf"

# Fallback font path override (scripts can be run on other machines)
FONT_DIR_ENV = os.environ.get("OG_FONT_DIR")


def font(path: Path, size: int) -> ImageFont.FreeTypeFont | ImageFont.ImageFont:
    if path.exists():
        return ImageFont.truetype(str(path), size)
    return ImageFont.load_default()


def draw_background(d: ImageDraw.ImageDraw, rng: random.Random) -> None:
    """Deep-navy vertical gradient + starfield + faint nebula washes."""
    for y in range(H):
        t = y / (H - 1)
        c = tuple(int(BG_TOP[i] + (BG_BOTTOM[i] - BG_TOP[i]) * t) for i in range(3))
        d.line([(0, y), (W, y)], fill=c)

    for _ in range(220):
        x = rng.randint(0, W)
        y = rng.randint(0, H)
        r = rng.choice([1, 1, 1, 2])
        alpha = rng.randint(60, 200)
        d.ellipse([x - r, y - r, x + r, y + r], fill=(255, 255, 255, alpha))

    # two soft nebula blobs (gold left, purple right) - drawn as translucent circles
    nebula = Image.new("RGBA", (W, H), (0, 0, 0, 0))
    nd = ImageDraw.Draw(nebula)
    for cx, cy, radius, color in [
        (180, 150, 300, (255, 193, 7, 24)),
        (1000, 160, 340, (102, 16, 242, 30)),
        (1050, 480, 260, (29, 60, 253, 22)),
    ]:
        nd.ellipse([cx - radius, cy - radius, cx + radius, cy + radius], fill=color)
    d._image.paste(Image.alpha_composite(d._image.convert("RGBA"), nebula).convert("RGB"), (0, 0))


def draw_neural_nodes(d: ImageDraw.ImageDraw, rng: random.Random) -> None:
    """Glowing constellation cells connected by faint lines (right-of-center)."""
    cx_center = 900
    cy_center = 300
    nodes = [
        (cx_center, cy_center),
    ]
    for i in range(16):
        ang = rng.uniform(0, math.tau)
        rad = rng.uniform(80, 260)
        nodes.append((cx_center + rad * math.cos(ang), cy_center + rad * math.sin(ang)))

    palette = [CYAN, BLUE, PURPLE, MAGENTA, GOLD, ORANGE, GREEN]
    for i, (x, y) in enumerate(nodes):
        for j in range(i + 1, len(nodes)):
            x2, y2 = nodes[j]
            dist = math.hypot(x2 - x, y2 - y)
            if dist < 190:
                d.line([(x, y), (x2, y2)], fill=(120, 160, 220, 40), width=1)

    for i, (x, y) in enumerate(nodes):
        color = palette[i % len(palette)]
        if i == 0:
            r = 16
        else:
            r = rng.choice([4, 5, 6])
        glow = Image.new("RGBA", (W, H), (0, 0, 0, 0))
        gd = ImageDraw.Draw(glow)
        gd.ellipse([x - r * 3, y - r * 3, x + r * 3, y + r * 3], fill=color + (36,))
        d._image.paste(Image.alpha_composite(d._image.convert("RGBA"), glow).convert("RGB"), (0, 0))
        d.ellipse([x - r, y - r, x + r, y + r], fill=color)


def draw_rings(d: ImageDraw.ImageDraw, cx: int, cy: int) -> None:
    """Thin brand-gradient rings echoing the nico-symbol mark."""
    ring_colors = [BLUE, PURPLE, MAGENTA, GOLD]
    for i, rc in enumerate(ring_colors):
        r = 96 + i * 22
        d.ellipse([cx - r, cy - r, cx + r, cy + r], outline=rc, width=2)


def wrap_title(text: str, max_width: int, f: ImageFont.FreeTypeFont, d: ImageDraw.ImageDraw) -> list[str]:
    words = text.split()
    lines: list[str] = []
    cur = ""
    for w in words:
        trial = (cur + " " + w).strip()
        if d.textlength(trial, font=f) <= max_width:
            cur = trial
        else:
            if cur:
                lines.append(cur)
            cur = w
    if cur:
        lines.append(cur)
    return lines


def draw_card(project: dict, out_path: Path, rng: random.Random) -> None:
    img = Image.new("RGB", (W, H), BG_TOP)
    d = ImageDraw.Draw(img)

    draw_background(d, rng)
    draw_neural_nodes(d, rng)
    draw_rings(d, 940, 150)

    # category chip (top-left)
    cat = project.get("categorie", "")
    chip_font = font(FONT_BOLD, 26)
    chip_text = cat.upper()
    chip_w = d.textlength(chip_text, font=chip_font) + 56
    d.rounded_rectangle([64, 64, 64 + chip_w, 116], radius=26, fill=(29, 60, 253, 60), outline=CYAN, width=2)
    d.text((64 + 28, 78), chip_text, font=chip_font, fill=CYAN)

    # site wordmark top-right
    wm_font = font(FONT_BOLD, 30)
    wm = "UNIVERS DE NICO"
    wm_w = d.textlength(wm, font=wm_font)
    d.text((W - 64 - wm_w, 74), wm, font=wm_font, fill=GREY)
    # small symbol dot
    d.ellipse([W - 64 - wm_w - 34, 78, W - 64 - wm_w - 14, 98], fill=GOLD)

    # title (left block)
    title = project.get("titre", "")
    t_font = font(FONT_BLACK, 74)
    lines = wrap_title(title, 700, t_font, d)
    y = 240
    for ln in lines[:3]:
        d.text((64, y), ln, font=t_font, fill=WHITE)
        y += 88

    # description (smaller, below title)
    desc = project.get("description", "")
    if desc and y < 520:
        de_font = font(FONT_REG, 28)
        de_lines = wrap_title(desc, 660, de_font, d)
        y2 = y + 8
        for ln in de_lines[:2]:
            d.text((64, y2), ln, font=de_font, fill=GREY)
            y2 += 44

    # tech chips at bottom-left
    techs = [t.strip() for t in project.get("tech", "").split(",") if t.strip()]
    chip_y = H - 96
    x = 64
    chip_small = font(FONT_BOLD, 22)
    tech_colors = [CYAN, PURPLE, MAGENTA, GOLD, ORANGE, GREEN]
    for i, tech in enumerate(techs[:5]):
        tw = d.textlength(tech, font=chip_small) + 40
        tw = max(tw, 54)
        if x + tw > W - 64:
            break
        d.rounded_rectangle([x, chip_y, x + tw, chip_y + 48], radius=24, fill=(20, 30, 70, 160),
                            outline=tech_colors[i % len(tech_colors)], width=2)
        d.text((x + 20, chip_y + 11), tech, font=chip_small, fill=tech_colors[i % len(tech_colors)])
        x += tw + 16

    out_path.parent.mkdir(parents=True, exist_ok=True)
    img.save(out_path, "PNG", optimize=True)
    print(f"wrote {out_path} ({os.path.getsize(out_path)} bytes)")


def main() -> None:
    ap = argparse.ArgumentParser()
    ap.add_argument("--projects", help="JSON array of project dicts (slug/titre/description/tech/categorie)")
    ap.add_argument("--articles", help="JSON array of article dicts (slug/titre/resume) - writes to articles/ subdir")
    ap.add_argument("--out", default="assets/images/og")
    args = ap.parse_args()

    if args.projects:
        with open(args.projects) as f:
            projects = json.load(f)
    else:
        projects = [  # kept in sync with migrations/ DB seeds
            {"slug": "todolist-javafx", "titre": "Todolist avec et sans interface", "description": "Application JavaFX permettant la gestion de tâches avec une base mysql.", "tech": "JavaFX, MYSQL, MVC", "categorie": "Java & JavaFX"},
            {"slug": "pendu", "titre": "Jeu du pendu", "description": "Version terminal.", "tech": "Java", "categorie": "Java & JavaFX"},
            {"slug": "poupee-russe", "titre": "Poupée russe", "description": "Version terminal.", "tech": "Java, POO", "categorie": "Java & JavaFX"},
            {"slug": "crud-java", "titre": "CRUD", "description": "CRUD Utilisateur.", "tech": "Java, POO, MYSQL", "categorie": "Java & JavaFX"},
            {"slug": "encaissements", "titre": "Encaissements", "description": "Progiciel pour la gestion de portefeuilles clients et d'investissements.", "tech": "Symfony, UX, MYSQL", "categorie": "PHP / Symfony"},
            {"slug": "plateforme-qcm", "titre": "Plateforme de QCM", "description": "Application web de QCM pour les formations CCA, avec authentification et suivi des scores.", "tech": "Symfony, Bootstrap, MySQL", "categorie": "PHP / Symfony"},
            {"slug": "portfolio", "titre": "Portfolio", "description": "Ce site : compétences, projets et parcours, à explorer dans un cerveau 3D interactif.", "tech": "Symfony, Twig, Stimulus, Three.js, Docker", "categorie": "PHP / Symfony"},
            {"slug": "topologie-cisco", "titre": "Topologie Cisco virtuelle", "description": "Mise en place d'un réseau complet sous Cisco Packet Tracer avec routage dynamique.", "tech": "Cisco, VLAN, OSPF", "categorie": "Réseau / Infra"},
            {"slug": "serveur-debian", "titre": "Serveur Web Debian", "description": "Déploiement complet d'un serveur Apache/PHP sécurisé sous Debian.", "tech": "Linux, Apache2, SSH", "categorie": "Réseau / Infra"},
        ]

    rng = random.Random(42)
    out = Path(args.out)
    if args.articles:
        with open(args.articles) as f:
            articles = json.load(f)
        for a in articles:
            card = {
                "slug": a["slug"],
                "titre": a.get("titre", ""),
                "description": a.get("resume", ""),
                "tech": a.get("cat", ""),
                "categorie": a.get("categorie", "Article"),
            }
            draw_card(card, out / "articles" / f"{a['slug']}.png", rng)
    for p in projects:
        draw_card(p, out / "projets" / f"{p['slug']}.png", rng)


if __name__ == "__main__":
    main()
