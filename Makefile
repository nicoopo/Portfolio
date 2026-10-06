# ================================================
# Makefile – Portfolio
# Usage : make <commande>
# ================================================

DC         = docker compose
DC_PROD = docker compose -f compose.yaml -f compose.prod.yaml
PHP        = $(DC) exec -u www-data php
CONSOLE    = $(PHP) php bin/console

.DEFAULT_GOAL := help

help: ## Affiche cette aide
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | \
	   awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-20s\033[0m %s\n", $$1, $$2}'

# --- DEV ---
up: ## Démarre les conteneurs (dev) → http://localhost:8082
	$(DC) up -d --build
	$(PHP) composer install
	$(PHP) php bin/console importmap:install
	$(CONSOLE) doctrine:migrations:migrate --no-interaction

migrate: ## Applique les migrations (dev)
	$(CONSOLE) doctrine:migrations:migrate --no-interaction

admin-create: ## Crée le compte « admin » de /admin, ou change son mot de passe (dev)
	$(CONSOLE) app:admin:create

journal-purge: ## Supprime les entrées du journal de plus de 12 mois (dev)
	$(CONSOLE) app:journal:purge

down: ## Arrête le conteneur (dev)
	$(DC) down

logs: ## Logs en temps réel (dev)
	$(DC) logs -f

bash: ## Shell dans le conteneur (dev)
	$(PHP) bash

cc: ## Vide le cache (dev)
	$(CONSOLE) cache:clear

routes: ## Liste les routes
	$(CONSOLE) debug:router

test: ## Lance les tests PHPUnit (base app_test créée et migrée au besoin)
	$(DC) exec -u www-data -e APP_ENV=test php php bin/console doctrine:database:create --if-not-exists
	$(DC) exec -u www-data -e APP_ENV=test php php bin/console doctrine:migrations:migrate --no-interaction
	$(DC) exec -u www-data -e APP_ENV=test php vendor/bin/phpunit

# --- PROD ---
prod-deploy: ## Build + relance (prod)
	bash scripts/deploy-prod.sh

prod-down: ## Arrête le conteneur (prod)
	$(DC_PROD) down

prod-logs: ## Logs en temps réel (prod)
	$(DC_PROD) logs -f

prod-admin-create: ## Crée le compte « admin » de /admin, ou change son mot de passe (prod)
	$(DC_PROD) exec php php bin/console app:admin:create

prod-purge: ## Supprime le journal et les demandes de contact de plus de 12 mois (prod ; cron quotidien, voir README)
	$(DC_PROD) exec -T php php bin/console app:journal:purge
	$(DC_PROD) exec -T php php bin/console app:contact:purge

prod-bash: ## Shell dans le conteneur (prod)
	$(DC_PROD) exec php bash

.PHONY: help up down logs bash cc routes test prod-deploy prod-down prod-logs prod-bash
