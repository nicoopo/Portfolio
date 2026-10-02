#!/usr/bin/env bash
set -euo pipefail

cd "$(dirname "$0")/.."

COMPOSE="docker compose -f compose.yaml -f compose.preprod.yaml"

# Variables du serveur (.env.local, jamais versionné) : APP_SECRET, POSTGRES_PASSWORD…
set -a
# shellcheck disable=SC1091
. ./.env.local
set +a
: "${POSTGRES_PASSWORD:?POSTGRES_PASSWORD manquant dans .env.local}"

echo "==> Arrêt du stack dev (libère le port 8081)"
docker compose stop php 2>/dev/null || true

echo "==> Build de l'image preprod"
$COMPOSE build php

echo "==> Recréation des containers"
$COMPOSE up -d --remove-orphans

echo "==> Migrations de la base"
$COMPOSE exec -T php php bin/console doctrine:migrations:migrate --no-interaction

echo "==> Déploiement terminé → http://localhost:8081"
