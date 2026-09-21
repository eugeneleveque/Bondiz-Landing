# =========================
# Project configuration
# =========================
PROJECT_NAME=mdsu_blog
COMPOSE_FILE=docker/compose.yaml
PHP_SERVICE=php
.DEFAULT_GOAL := help

DOCKER_COMPOSE=docker compose -p $(PROJECT_NAME) --env-file .env -f $(COMPOSE_FILE)

# =========================
# Docker commands
# =========================

## Lancer les containers
dc-up:
	$(DOCKER_COMPOSE) up -d --build

## Arrêter les containers
dc-down:
	$(DOCKER_COMPOSE) down

## Redémarrer les containers
dc-restart: dc-down dc-up

## Alias plus courts
up: dc-up
down: dc-down
restart: dc-restart
sh: dc-bash

## Voir les logs
dc-logs:
	$(DOCKER_COMPOSE) logs -f

## Status des containers
dc-ps:
	$(DOCKER_COMPOSE) ps

# =========================
# PHP / Symfony
# =========================

## Entrer dans le container PHP en bash
dc-bash:
	$(DOCKER_COMPOSE) exec $(PHP_SERVICE) bash

## Installer toutes les dépendances composer
composer-install:
	$(DOCKER_COMPOSE) exec $(PHP_SERVICE) composer install

## Installer un package composer (ex: make composer-require pkg/name)
composer-require:
	$(DOCKER_COMPOSE) exec $(PHP_SERVICE) composer require $(pkg)

## Installer un package composer en dev (ex: make composer-require-dev pkg/name)
composer-require-dev:
	$(DOCKER_COMPOSE) exec $(PHP_SERVICE) composer require $(pkg)

## Supprimer un package composer (ex: make composer-remove pkg/name)
composer-remove:
	$(DOCKER_COMPOSE) exec $(PHP_SERVICE) composer remove $(pkg)

# =========================
# Symfony shortcuts
# =========================

## Cache clear
cache-clear:
	$(DOCKER_COMPOSE) exec $(PHP_SERVICE) php bin/console cache:clear

## Console Symfony (ex: make console c=cache:clear)
console:
	$(DOCKER_COMPOSE) exec $(PHP_SERVICE) php bin/console $(c)

# =========================
# Database
# =========================

## Créer une migration
db-migration:
	$(DOCKER_COMPOSE) exec $(PHP_SERVICE) php bin/console make:migration

## Jouer les migrations
db-migrate:
	$(DOCKER_COMPOSE) exec $(PHP_SERVICE) php bin/console doctrine:migrations:migrate --no-interaction

## Reset la base de données (Drop + Create + Migrate)
db-reset:
	$(DOCKER_COMPOSE) exec $(PHP_SERVICE) php bin/console doctrine:database:drop --force --if-exists
	$(DOCKER_COMPOSE) exec $(PHP_SERVICE) php bin/console doctrine:database:create
	$(DOCKER_COMPOSE) exec $(PHP_SERVICE) php bin/console doctrine:migrations:migrate --no-interaction

# =========================
# Utils
# =========================

## Nettoyage complet
reset:
	$(DOCKER_COMPOSE) down -v --remove-orphans

help:
	@echo ""
	@echo "Commandes disponibles :"
	@echo "  make dc-up                   Lancer les containers"
	@echo "  make dc-down                 Arrêter les containers"
	@echo "  make dc-restart              Redémarrer les containers"
	@echo "  make dc-logs                 Logs Docker"
	@echo "  make dc-ps                   Status Docker"
	@echo "  make dc-bash                 Entrer dans le container PHP"
	@echo "  make composer-install        Installer les dépendances"
	@echo "  make composer-require pkg=vendor/package    Installer un package composer"
	@echo "  make composer-require-dev pkg=vendor/package    Installer un package composer en dev"
	@echo "  make composer-remove pkg=vendor/package    Supprimer un package composer"
	@echo "  make console c=command       Commande Symfony"
	@echo "  make reset                   Reset complet (volumes inclus)"
	@echo ""
