.DEFAULT_GOAL := help
SHELL := /bin/bash

COMPOSER ?= composer
PHP      ?= php
OPENAPI_SPEC := docs/openapi/openapi.json
OPENAPI_URL  := https://cloud.factro.com/api/core/docs/swagger-ui-init.js

.PHONY: help install update validate qa phpstan cs cs-fix rector rector-fix fix \
        test test-unit test-integration openapi-fetch openapi-diff clean

help: ## Show this help
	@grep -hE '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "  \033[36m%-18s\033[0m %s\n", $$1, $$2}'

install: ## Install dependencies
	$(COMPOSER) install --no-interaction --prefer-dist

update: ## Update dependencies
	$(COMPOSER) update --no-interaction --prefer-dist

validate: ## Validate composer.json
	$(COMPOSER) validate --strict

qa: ## Full quality gate: PHPStan, PHP-CS-Fixer (dry-run), Rector (dry-run), PHPUnit
	$(COMPOSER) qa

phpstan: ## Static analysis (level 9)
	$(COMPOSER) phpstan

cs: ## Code style check (dry-run)
	$(COMPOSER) cs

cs-fix: ## Apply code style fixes
	$(COMPOSER) cs:fix

rector: ## Rector check (dry-run)
	$(COMPOSER) rector

rector-fix: ## Apply Rector refactorings
	$(COMPOSER) rector:fix

fix: rector-fix cs-fix ## Apply Rector, then code style fixes

test: ## PHPUnit
	$(COMPOSER) test

test-unit: ## PHPUnit, Unit suite only
	$(COMPOSER) test:unit

test-integration: ## PHPUnit, Integration suite only
	$(COMPOSER) test:integration

openapi-fetch: ## Download the current Core API spec to docs/openapi/openapi.json and show what changed
	@set -o pipefail; curl -sSf $(OPENAPI_URL) | $(PHP) bin/openapi-extract > $(OPENAPI_SPEC).tmp \
		|| { rm -f $(OPENAPI_SPEC).tmp; echo "could not extract the spec from $(OPENAPI_URL)" >&2; exit 1; }
	@if [ -f $(OPENAPI_SPEC) ]; then bin/openapi-diff $(OPENAPI_SPEC) $(OPENAPI_SPEC).tmp; fi
	@mv $(OPENAPI_SPEC).tmp $(OPENAPI_SPEC)
	@echo "written $(OPENAPI_SPEC)"

openapi-diff: ## Compare docs/openapi/openapi.json with the live spec without replacing it
	@test -f $(OPENAPI_SPEC) || { echo "$(OPENAPI_SPEC) is missing, run make openapi-fetch" >&2; exit 1; }
	@set -o pipefail; curl -sSf $(OPENAPI_URL) | $(PHP) bin/openapi-extract > $(OPENAPI_SPEC).tmp \
		|| { rm -f $(OPENAPI_SPEC).tmp; echo "could not extract the spec from $(OPENAPI_URL)" >&2; exit 1; }
	@bin/openapi-diff $(OPENAPI_SPEC) $(OPENAPI_SPEC).tmp; rm -f $(OPENAPI_SPEC).tmp

clean: ## Remove tool caches
	rm -rf .phpunit.cache .php-cs-fixer.cache
