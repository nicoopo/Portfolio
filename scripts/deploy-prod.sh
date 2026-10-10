#!/usr/bin/env bash
set -euo pipefail

cd "$(dirname "$0")/.."

COMPOSE="docker compose -f compose.yaml -f compose.prod.yaml"

# Variables du serveur (.env.local, jamais versionné) : APP_SECRET, POSTGRES_PASSWORD…
set -a
# shellcheck disable=SC1091
. ./.env.local
set +a
: "${POSTGRES_PASSWORD:?POSTGRES_PASSWORD manquant dans .env.local}"

echo "==> Build de l'image prod"
$COMPOSE build php

# Avant la recréation : le nouveau code ne tourne jamais sur l'ancienne base (migrations additives :
# l'ancien container, encore en service, s'accommode d'une table ou d'une colonne en plus)
echo "==> Migrations de la base"
$COMPOSE run --rm -T php php bin/console doctrine:migrations:migrate --no-interaction

echo "==> Recréation des containers"
$COMPOSE up -d --remove-orphans

echo "==> Déploiement terminé → https://nicolascataluna.fr"
