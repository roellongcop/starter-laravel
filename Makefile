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
.PHONY: help build up down down-v refresh restart install setup shell migrate \
        fresh seed queue dev assets test pint stan lint is-mergeable logs ps tinker \
        ide-helper wait-db key storage-link clean hooks mail fix \
        backup backup-prune backup-monitor schedule-list

help: ## Show this help
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | \
		awk 'BEGIN {FS = ":.*?## "}; {printf "  \033[36m%-12s\033[0m %s\n", $$1, $$2}'

build: ## Build all images
	$(DC) build

up: ## Start the stack (waits for a healthy database)
	$(DC) up -d
	@$(MAKE) wait-db

# `--profile dev` ensures the dev-only `node` container is torn down too;
# `down` ignores inactive-profile containers, which otherwise get left behind
# pinned to a deleted network and break the next `make dev`.
down: ## Stop and remove containers
	$(DC) --profile dev down

down-v: ## Stop and remove containers + named volumes (db, seaweedfs)
	$(DC) --profile dev down -v --remove-orphans

# Build assets are written by the node container (root), so host `rm` hits
# permission errors. Delete inside a one-off root container — the project is
# bind-mounted, so root can remove files of any owner. Needs the app image built.
clean: ## Delete generated/uploaded files + runtime caches (assets, storage, compiled config)
	$(DC) run --rm --no-deps -T --user root app sh -lc 'rm -rf \
		public/build public/hot \
		storage/app/private/uploads storage/app/private/exports \
		storage/app/private/imports storage/app/private/backups \
		storage/app/private/image-cache \
		storage/app/backup-temp; \
		find storage/app/public -mindepth 1 -not -name .gitignore -delete 2>/dev/null || true; \
		for d in storage/framework/cache storage/framework/sessions \
			storage/framework/views storage/framework/testing \
			storage/logs bootstrap/cache; do \
			find "$$d" -mindepth 1 -not -name .gitignore -delete 2>/dev/null || true; \
		done'

refresh: ## Wipe EVERYTHING (containers, volumes, local files) and rebuild fresh
	$(DC) --profile dev down -v --remove-orphans
	$(DC) build
	@$(MAKE) clean
	@$(MAKE) setup

restart: down up ## Restart the stack

wait-db: ## Block until Postgres reports healthy
	@echo "Waiting for Postgres to become healthy..."
	@until [ "$$(docker inspect -f '{{.State.Health.Status}}' $$($(DC) ps -q postgres) 2>/dev/null)" = "healthy" ]; do \
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

fresh: ## DB only: drop all tables and re-migrate + seed (use `refresh` for a full wipe)
	$(APP) php artisan migrate:fresh --seed

seed: ## Run database seeders
	$(APP) php artisan db:seed

queue: ## Run a queue worker in the foreground
	$(APP) php artisan queue:work --tries=3

backup: ## Queue a database backup now (same flow as the nightly scheduled job)
	$(APP) php artisan schedule:test --name='backups:run'

backup-prune: ## Delete backups older than the retention window (keeps the latest generated)
	$(APP) php artisan backups:prune

backup-monitor: ## Alert developers in-app if no successful backup is within the threshold
	$(APP) php artisan backups:monitor

schedule-list: ## List every scheduled task and when it next runs
	$(APP) php artisan schedule:list

dev: ## Run the Vite dev server (dev profile)
	$(DC) up node

mail: ## Print the Mailpit inbox URL (dev email)
	@port=$$(grep -E '^MAILPIT_PORT=' .env 2>/dev/null | cut -d= -f2); \
	echo "Mailpit inbox: http://localhost:$${port:-8025}"

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

fix: ## Auto-format + lint-fix PHP and the frontend (writes files)
	$(APP) ./vendor/bin/pint
	$(NODE_RUN) npm run format
	$(NODE_RUN) npm run lint

hooks: ## Install the git pre-commit hook (Pint + Prettier + ESLint, check-only)
	git config core.hooksPath .githooks
	@echo "Git hooks enabled (.githooks/pre-commit). Bypass once with SKIP_HOOKS=1 git commit."

is-mergeable: ## Run the full CI gate locally (check-only, no writes)
	$(APP) ./vendor/bin/pint --test
	$(APP) ./vendor/bin/phpstan analyse --no-progress
	$(APP) php artisan test
	$(NODE_RUN) npm run format:check
	$(NODE_RUN) npm run lint:check
	$(NODE_RUN) npm run build

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
