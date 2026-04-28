COMPOSE := docker compose

.PHONY: up down logs shell test migrate seed key setup composer-install npm-build queue-logs

up:
	$(COMPOSE) up -d --build

down:
	$(COMPOSE) down --remove-orphans

logs:
	$(COMPOSE) logs -f --tail=200

shell:
	$(COMPOSE) exec app sh

key:
	$(COMPOSE) exec app php artisan key:generate

migrate:
	$(COMPOSE) exec app php artisan migrate

seed:
	$(COMPOSE) exec app php artisan db:seed

test:
	$(COMPOSE) exec app php artisan test

composer-install:
	$(COMPOSE) exec app composer install

npm-build:
	$(COMPOSE) exec vite npm run build

queue-logs:
	$(COMPOSE) logs -f queue

setup: up composer-install key
	$(COMPOSE) exec app php artisan migrate --seed
