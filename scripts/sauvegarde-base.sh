#!/usr/bin/env bash
# ------------------------------------------------------------------
# Sauvegarde de la base de prod : pg_dump (format compressé « custom »,
# restaurable avec pg_restore), vérifiée, puis rotation et copies.
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

COMPOSE="${COMPOSE:-docker compose -f compose.yaml -f compose.prod.yaml}"
DOSSIER="${DOSSIER:-$HOME/sauvegardes/portfolio}"
DISQUE="/mnt/sauvegardes"
DISTANT="proton:sauvegardes/portfolio"
GARDER_JOURS=14

mkdir -p "$DOSSIER"
fichier="$DOSSIER/portfolio-$(date +%F_%H%M).dump"
trap 'rm -f "$fichier.part"' EXIT # pas de sauvegarde à moitié écrite en cas d'échec

# Socket locale du container : pas de mot de passe à fournir
$COMPOSE exec -T database pg_dump -U app --format=custom app > "$fichier.part"
# Une sauvegarde illisible ne vaut rien : on vérifie que pg_restore sait la lire
$COMPOSE exec -T database pg_restore --list < "$fichier.part" > /dev/null
mv "$fichier.part" "$fichier"
find "$DOSSIER" -name 'portfolio-*.dump' -mtime +"$GARDER_JOURS" -delete

if mountpoint -q "$DISQUE"; then
    mkdir -p "$DISQUE/portfolio"
    cp "$fichier" "$DISQUE/portfolio/"
    find "$DISQUE/portfolio" -name 'portfolio-*.dump' -mtime +"$GARDER_JOURS" -delete
    copies="second disque"
fi

if command -v rclone > /dev/null && rclone listremotes 2> /dev/null | grep -qx 'proton:'; then
    rclone copy "$fichier" "$DISTANT"
    rclone delete --min-age "${GARDER_JOURS}d" "$DISTANT"
    copies="${copies:+$copies + }Proton Drive"
fi

echo "==> $(date '+%F %T') sauvegarde OK : $fichier ($(du -h "$fichier" | cut -f1)) — copies : ${copies:-aucune}"
