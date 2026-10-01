# ================================================
# Makefile – Portfolio
# Usage : make <commande>
# ================================================

DC         = docker compose
DC_PREPROD = docker compose -f compose.yaml -f compose.preprod.yaml
CONSOLE    = $(DC) exec php php bin/console

.DEFAULT_GOAL := help

help: ## Affiche cette aide
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | \
	   awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-20s\033[0m %s\n", $$1, $$2}'

# --- DEV ---
up: ## Démarre le conteneur (dev) → http://localhost:8081
	$(DC) up -d
	$(DC) exec php composer install

down: ## Arrête le conteneur (dev)
	$(DC) down

logs: ## Logs en temps réel (dev)
	$(DC) logs -f

bash: ## Shell dans le conteneur (dev)
	$(DC) exec php bash

cc: ## Vide le cache (dev)
	$(CONSOLE) cache:clear

routes: ## Liste les routes
	$(CONSOLE) debug:router

test: ## Lance les tests PHPUnit
	$(DC) exec php vendor/bin/phpunit

# --- PREPROD ---
preprod-deploy: ## Build + relance (preprod)
	bash scripts/deploy-preprod.sh

preprod-down: ## Arrête le conteneur (preprod)
	$(DC_PREPROD) down

preprod-logs: ## Logs en temps réel (preprod)
	$(DC_PREPROD) logs -f

preprod-bash: ## Shell dans le conteneur (preprod)
	$(DC_PREPROD) exec php bash

.PHONY: help up down logs bash cc routes test preprod-deploy preprod-down preprod-logs preprod-bash
