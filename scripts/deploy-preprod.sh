#!/usr/bin/env bash
set -euo pipefail

cd "$(dirname "$0")/.."

COMPOSE="docker compose -f compose.yaml -f compose.preprod.yaml"

echo "==> Arrêt du stack dev (libère le port 8081)"
docker compose stop php 2>/dev/null || true

echo "==> Build de l'image preprod"
$COMPOSE build php

echo "==> Recréation du container"
$COMPOSE up -d --remove-orphans

echo "==> Déploiement terminé → http://localhost:8081"
