#!/usr/bin/env bash
# ------------------------------------------------------------------
# Sauvegarde de la prod : base (pg_dump au format « custom », restaurable
# avec pg_restore, vérifiée) et images envoyées depuis l'admin (archive tar),
# puis rotation et copies.
#
#   1. ~/sauvegardes/portfolio            toujours
#   2. /mnt/sauvegardes/portfolio         si le second disque est monté
#   3. Proton Drive (rclone, remote « proton: »)  s'il est configuré
#
# Lancé chaque nuit par cron (crontab de nicolas, voir README).
# Restaurer : voir README, « Sauvegardes ».
# ------------------------------------------------------------------
set -euo pipefail

cd "$(dirname "$0")/.."
# cron ne fournit que /usr/bin:/bin ; rclone officiel (Proton Drive) est dans ~/.local/bin ou /usr/local/bin
export PATH="$HOME/.local/bin:/usr/local/bin:$PATH"

COMPOSE="${COMPOSE:-docker compose -f compose.yaml -f compose.prod.yaml}"
DOSSIER="${DOSSIER:-$HOME/sauvegardes/portfolio}"
DISQUE="/mnt/sauvegardes"
DISTANT="proton:sauvegardes/portfolio"
GARDER_JOURS=14

mkdir -p "$DOSSIER"
horodatage=$(date +%F_%H%M)
fichier="$DOSSIER/portfolio-$horodatage.dump"
images="$DOSSIER/portfolio-images-$horodatage.tar.gz"
trap 'rm -f "$fichier.part" "$images.part"' EXIT # pas de sauvegarde à moitié écrite en cas d'échec

# Socket locale du container : pas de mot de passe à fournir
$COMPOSE exec -T database pg_dump -U app --format=custom app > "$fichier.part"
# Une sauvegarde illisible ne vaut rien : on vérifie que pg_restore sait la lire
$COMPOSE exec -T database pg_restore --list < "$fichier.part" > /dev/null
mv "$fichier.part" "$fichier"

# Images envoyées depuis l'admin (volume monté sur public/uploads du container), vérifiées par tar -t
$COMPOSE exec -T php tar -czf - -C public uploads > "$images.part"
tar -tzf "$images.part" > /dev/null
mv "$images.part" "$images"

find "$DOSSIER" -name 'portfolio-*' -mtime +"$GARDER_JOURS" -delete

if mountpoint -q "$DISQUE"; then
    mkdir -p "$DISQUE/portfolio"
    cp "$fichier" "$images" "$DISQUE/portfolio/"
    find "$DISQUE/portfolio" -name 'portfolio-*' -mtime +"$GARDER_JOURS" -delete
    copies="second disque"
fi

if command -v rclone > /dev/null && rclone listremotes 2> /dev/null | grep -qx 'proton:'; then
    rclone copy "$fichier" "$DISTANT"
    rclone copy "$images" "$DISTANT"
    rclone delete --min-age "${GARDER_JOURS}d" "$DISTANT"
    copies="${copies:+$copies + }Proton Drive"
fi

echo "==> $(date '+%F %T') sauvegarde OK : $fichier ($(du -h "$fichier" | cut -f1)), $images ($(du -h "$images" | cut -f1)) — copies : ${copies:-aucune}"
