# Laravel Keen Admin Starter — one-word Docker workflow targets.
# Every target wraps the underlying `docker compose ...` block so contributors
# never type long chains. Commands assume they run on the host with Docker only.

DC       := docker compose
APP      := $(DC) exec -T app
APP_TTY  := $(DC) exec app
# Node has no long-running container in the default stack (dev profile only),
# so Node tasks run as one-off containers.
NODE_RUN := $(DC) run --rm node

# Pass host UID/GID into the build so mounted files stay writable.
export UID := $(shell id -u)
export GID := $(shell id -g)

.DEFAULT_GOAL := help
.PHONY: help build up down restart install setup shell migrate fresh seed \
        queue dev assets test pint stan lint logs ps tinker ide-helper \
        wait-db key storage-link

help: ## Show this help
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | \
		awk 'BEGIN {FS = ":.*?## "}; {printf "  \033[36m%-12s\033[0m %s\n", $$1, $$2}'

build: ## Build all images
	$(DC) build

up: ## Start the stack (waits for a healthy database)
	$(DC) up -d
	@$(MAKE) wait-db

down: ## Stop and remove containers
	$(DC) down

restart: down up ## Restart the stack

wait-db: ## Block until MariaDB reports healthy
	@echo "Waiting for MariaDB to become healthy..."
	@until [ "$$(docker inspect -f '{{.State.Health.Status}}' $$($(DC) ps -q mariadb) 2>/dev/null)" = "healthy" ]; do \
		printf '.'; sleep 2; \
	done; echo " ready."

install: ## First-run bootstrap: composer + npm install
	$(APP) composer install
	$(NODE_RUN) npm install

setup: up ## One-shot fresh start (install, key, migrate --seed, storage:link)
	$(APP) composer install
	$(NODE_RUN) npm install
	@$(MAKE) key
	$(APP) php artisan migrate --seed --force
	@$(MAKE) storage-link
	$(NODE_RUN) npm run build
	@echo "Setup complete -> http://localhost:$${APP_PORT:-8080}"

key: ## Generate the app key if missing
	$(APP) php artisan key:generate

storage-link: ## Symlink public storage
	$(APP) php artisan storage:link || true

shell: ## Bash shell in the app container
	$(APP_TTY) bash

migrate: ## Run database migrations
	$(APP) php artisan migrate

fresh: ## Drop everything and re-migrate + seed
	$(APP) php artisan migrate:fresh --seed

seed: ## Run database seeders
	$(APP) php artisan db:seed

queue: ## Run a queue worker in the foreground
	$(APP) php artisan queue:work --tries=3

dev: ## Run the Vite dev server (dev profile)
	$(DC) up node

assets: ## Build production assets
	$(NODE_RUN) npm run build

test: ## Run the Pest test suite
	$(APP) php artisan test

pint: ## Format PHP with Pint
	$(APP) ./vendor/bin/pint

stan: ## Run Larastan static analysis
	$(APP) ./vendor/bin/phpstan analyse

lint: ## Lint + format-check the frontend
	$(NODE_RUN) npm run lint
	$(NODE_RUN) npm run format:check

logs: ## Follow container logs
	$(DC) logs -f

ps: ## Show container status
	$(DC) ps

tinker: ## Open Tinker
	$(APP_TTY) php artisan tinker

ide-helper: ## Generate IDE helper files
	$(APP) php artisan ide-helper:generate
	$(APP) php artisan ide-helper:models -N
	$(APP) php artisan ide-helper:meta
