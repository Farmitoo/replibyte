DOCKER_COMPOSE  = DOCKER_BUILDKIT=1 docker-compose
EXEC_PHP        = $(DOCKER_COMPOSE) exec -T php-fpm
COMPOSER        = $(EXEC_PHP) composer
YARN            = $(EXEC_PHP) yarn
ENCORE          = $(EXEC_PHP) ./node_modules/.bin/encore
SYMFONY         = $(EXEC_PHP) bin/console
.PHONY: help

help: ## show help messages
	@awk 'BEGIN {FS = ":.*##"; printf "\nUsage:\n  make \033[36m\033[0m\n"} /^[$$()% 0-9a-zA-Z_-]+:.*?##/ { printf "  \033[36m%-15s\033[0m %s\n", $$1, $$2 } /^##@/ { printf "\n\033[1m%s\033[0m\n", substr($$0, 5) } ' $(MAKEFILE_LIST)

start: ## start containers (build and pull updates if needed)
	$(DOCKER_COMPOSE) pull --ignore-pull-failures
	$(DOCKER_COMPOSE) build --pull
	$(DOCKER_COMPOSE) up -d --remove-orphans --no-recreate

stop: ## stop containers
	$(DOCKER_COMPOSE) stop

kill: ## stop containers then remove containers, networks, volumes, images and orphans containers (defined in docker-compose.yaml file)
	$(DOCKER_COMPOSE) kill
	$(DOCKER_COMPOSE) down --volumes --remove-orphans

clean: ## remove all unused containers, networks, images and volumes
	docker system prune -a -f
	docker volume prune -f

php: ## enter the webserver container
	$(DOCKER_COMPOSE) exec php-fpm /bin/bash

install: ## execute `make start` then `make project-install`
	$(MAKE) start
	$(MAKE) project-install

project-install: ## execute : composer install - yarn install - encore dev - `make db-install` - symfony cache:clear
	$(COMPOSER) install
	$(SYMFONY) ca:cl

reset: ## execute `make kill` then execute `make install`
	$(MAKE) kill
	$(MAKE) install

test-unit: ## launch tests from tests/Unit folder only
	$(EXEC_PHP) ./vendor/bin/phpunit -c etc/phpunit/phpunit.xml tests/Unit
