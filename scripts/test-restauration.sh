#!/usr/bin/env bash
# ------------------------------------------------------------------
# Test de restauration : la dernière sauvegarde (scripts/sauvegarde-base.sh)
# est restaurée dans une base PostgreSQL jetable, puis on vérifie qu'elle
# contient bien le portfolio. La prod n'est jamais touchée.
#
# Échec : alerte ntfy (NTFY_SERVER / NTFY_TOPIC de .env.local, s'il est rempli).
# Lancé le 1er de chaque mois par cron (crontab de nicolas, voir README).
# ------------------------------------------------------------------
set -euo pipefail

cd "$(dirname "$0")/.."

DOSSIER="${DOSSIER:-$HOME/sauvegardes/portfolio}"
CONTAINER="portfolio_restauration_test"
IMAGE="postgres:16-alpine" # même version que la prod (compose.yaml)

# Sujet ntfy : seulement ces deux variables, lues sans exécuter .env.local
NTFY_SERVER=$(grep -s '^NTFY_SERVER=' .env.local | cut -d= -f2- || true)
NTFY_TOPIC=$(grep -s '^NTFY_TOPIC=' .env.local | cut -d= -f2- || true)

echec() {
    echo "==> $(date '+%F %T') ÉCHEC du test de restauration : $1" >&2
    if [ -n "$NTFY_TOPIC" ]; then
        curl -fsS -m 10 -H 'Title: Sauvegarde NON restaurable' -H 'Tags: rotating_light' -H 'Priority: high' \
            -d "$1" "${NTFY_SERVER:-https://ntfy.sh}/$NTFY_TOPIC" > /dev/null || true
    fi
    exit 1
}
trap 'echec "erreur inattendue ligne $LINENO"' ERR
trap 'docker rm -f "$CONTAINER" > /dev/null 2>&1 || true' EXIT

dump=$(ls -t "$DOSSIER"/portfolio-*.dump 2> /dev/null | head -1 || true)
images=$(ls -t "$DOSSIER"/portfolio-images-*.tar.gz 2> /dev/null | head -1 || true)
[ -n "$dump" ] || echec "aucune sauvegarde dans $DOSSIER"
# Plus de deux jours : la sauvegarde de nuit ne tourne plus
[ -n "$(find "$dump" -mtime -2)" ] || echec "dernière sauvegarde trop ancienne : $(basename "$dump")"

docker rm -f "$CONTAINER" > /dev/null 2>&1 || true
docker run -d --name "$CONTAINER" -e POSTGRES_USER=app -e POSTGRES_DB=app -e POSTGRES_PASSWORD=jetable "$IMAGE" > /dev/null
# Par TCP : pendant l'initialisation, le serveur temporaire de l'image n'écoute que sur la socket, puis redémarre
for _ in $(seq 60); do
    docker exec "$CONTAINER" pg_isready -h 127.0.0.1 -U app -d app -q 2> /dev/null && break
    sleep 1
done
docker exec "$CONTAINER" pg_isready -h 127.0.0.1 -U app -d app -q || echec "la base jetable ne démarre pas"

docker exec -i "$CONTAINER" pg_restore -U app -d app --no-owner --exit-on-error < "$dump" || echec "pg_restore a échoué sur $(basename "$dump")"

compter() { docker exec "$CONTAINER" psql -U app -d app -tAc "SELECT COUNT(*) FROM $1"; }
for table in projet competence categorie_competence cv_profil utilisateur doctrine_migration_versions; do
    [ "$(compter "$table")" -gt 0 ] || echec "table $table vide après restauration"
done

if [ -n "$images" ]; then
    tar -tzf "$images" > /dev/null || echec "archive d'images illisible : $(basename "$images")"
fi

echo "==> $(date '+%F %T') restauration OK : $(basename "$dump") — $(compter projet) projets, $(compter competence) compétences, $(compter candidature) candidatures"
