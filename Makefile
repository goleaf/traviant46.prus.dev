SHELL := /bin/bash

ARTISAN := php artisan
SAIL := ./vendor/bin/sail
PINT := ./vendor/bin/pint
PHPSTAN := ./vendor/bin/phpstan
PEST := ./vendor/bin/pest
NPM := npm

# Default connection used for artisan tasks (override with `make test DB_CONNECTION=mysql`)
DB_CONNECTION ?= sqlite

.PHONY: help up down restart logs shell test cs stan lint seed migrate fresh dev build hooks

help:
	@echo "Available targets:"
	@echo "  make up         - Start Laravel Sail containers in detached mode"
	@echo "  make down       - Stop Laravel Sail containers"
	@echo "  make restart    - Restart Sail containers"
	@echo "  make logs       - Tail application logs from Sail"
	@echo "  make shell      - Open a bash shell within the Sail app container"
	@echo "  make test       - Run the Pest test suite (override with ARGS=\"--filter=Foo\")"
	@echo "  make cs         - Run Laravel Pint on dirty files"
	@echo "  make stan       - Run Larastan (PHPStan) analysis"
	@echo "  make lint       - Run Pint and Larastan together"
	@echo "  make migrate    - Run outstanding migrations"
	@echo "  make seed       - Run migrations with seeders"
	@echo "  make fresh      - Fresh database reset with seeders"
	@echo "  make dev        - Start the Vite development server"
	@echo "  make build      - Build the production Vite assets"
	@echo "  make hooks      - Configure Git to use the repository-provided hooks"

up:
	@$(SAIL) up -d

down:
	@$(SAIL) down

restart:
	@$(SAIL) restart

logs:
	@$(SAIL) logs -f

shell:
	@$(SAIL) shell

test:
	@DB_CONNECTION=$(DB_CONNECTION) $(PEST) $(ARGS)

cs:
	@$(PINT) --dirty

stan:
	@DB_CONNECTION=$(DB_CONNECTION) $(PHPSTAN) analyse $(ARGS)

lint: cs stan

migrate:
	@DB_CONNECTION=$(DB_CONNECTION) $(ARTISAN) migrate $(ARGS)

seed:
	@DB_CONNECTION=$(DB_CONNECTION) $(ARTISAN) migrate --seed $(ARGS)

fresh:
	@DB_CONNECTION=$(DB_CONNECTION) $(ARTISAN) migrate:fresh --seed $(ARGS)

dev:
	@$(NPM) run dev

build:
	@$(NPM) run build

hooks:
	@git config core.hooksPath githooks
	@chmod +x githooks/*
	@echo "Git hooks installed."
