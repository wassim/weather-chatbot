include .env

.PHONY: help
.DEFAULT_GOAL = help

weather: ## Run the weather command
	@php artisan app:fetch-weather

help:
	@grep -h -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[32m%-30s\033[0m %s\n", $$1, $$2}'

ide: ## Regenerate docs
	@php artisan ide-helper:models

clear:
	@php artisan optimize:clear

fresh:
	@php artisan migrate:fresh --seed

pint: ## Run Pint
	./vendor/bin/pint

pint_test: ## Run Pint tests
	./vendor/bin/pint --test

stan: ## Run Stan
	./vendor/bin/phpstan analyse --memory-limit=2G

baseline: ## Re-generate Stan baseline file
	./vendor/bin/phpstan analyse --generate-baseline --allow-empty-baseline

security: ## Run Security Checker
	@rm -f ./local-php-security-checker
	@curl -L -sS -o local-php-security-checker https://github.com/fabpot/local-php-security-checker/releases/download/v$(PSC_VERSION)/local-php-security-checker_$(PLATFORM)_$(ARCHITECTURE)
	@chmod +x ./local-php-security-checker
	@./local-php-security-checker

test: ## Run PHPUnit tests
	@php artisan test

tests: stan test security pint_test

# Utils
PSC_VERSION := $(shell curl -s https://api.github.com/repos/fabpot/local-php-security-checker/releases/latest | grep '"tag_name":' | sed -E 's/.*"([^"]+)".*/\1/' | cut -c 2-)
PLATFORM := $(shell uname)
ifeq ($(PLATFORM), Darwin)
	PLATFORM = 'darwin'
else ifeq ($(PLATFORM), WindowsNT)
	PLATFORM = 'windows'
else
	PLATFORM = 'linux'
endif
ARCHITECTURE := $(shell uname -m)
ifeq ($(ARCHITECTURE), arm)
	ARCHITECTURE = 'arm64'
else ifeq ($(ARCHITECTURE), aarch64)
	ARCHITECTURE = 'arm64'
else ifeq ($(ARCHITECTURE), i386)
	ARCHITECTURE = '386'
else ifeq ($(ARCHITECTURE), i686)
	ARCHITECTURE = '386'
else
	ARCHITECTURE = 'amd64'
endif
