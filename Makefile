# ================================================
# Makefile – Portfolio
# Usage : make <commande>
# ================================================

DC         = docker compose
DC_PREPROD = docker compose -f compose.yaml -f compose.preprod.yaml
PHP        = $(DC) exec -u www-data php
CONSOLE    = $(PHP) php bin/console

.DEFAULT_GOAL := help

help: ## Affiche cette aide
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | \
	   awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-20s\033[0m %s\n", $$1, $$2}'

# --- DEV ---
up: ## Démarre les conteneurs (dev) → http://localhost:8081
	$(DC) up -d
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

# --- PREPROD ---
preprod-deploy: ## Build + relance (preprod)
	bash scripts/deploy-preprod.sh

preprod-down: ## Arrête le conteneur (preprod)
	$(DC_PREPROD) down

preprod-logs: ## Logs en temps réel (preprod)
	$(DC_PREPROD) logs -f

preprod-admin-create: ## Crée le compte « admin » de /admin, ou change son mot de passe (preprod)
	$(DC_PREPROD) exec php php bin/console app:admin:create

preprod-journal-purge: ## Supprime les entrées du journal de plus de 12 mois (preprod ; à planifier, ex. cron mensuel)
	$(DC_PREPROD) exec -T php php bin/console app:journal:purge

preprod-bash: ## Shell dans le conteneur (preprod)
	$(DC_PREPROD) exec php bash

.PHONY: help up down logs bash cc routes test preprod-deploy preprod-down preprod-logs preprod-bash
