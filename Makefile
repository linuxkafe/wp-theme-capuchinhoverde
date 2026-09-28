# Capuchinho Verde — AES project tooling
#
# The theme has no composer dependencies, so gates degrade honestly:
# a check that cannot run reports SKIP and never reports PASS.

SHELL := /bin/bash
.DEFAULT_GOAL := help

WP_URL        ?= http://localhost:8083
PHP_BIN       ?= php
# Use find, not `git ls-files`: the latter only sees tracked files, so a newly added PHP
# file is unlinted until it is committed. That hole hid two real syntax errors in
# partials/posthead.php on the day they were written.
THEME_PHP     := $(shell find . -name '*.php' -not -path './node_modules/*' -not -path './wp-data/*' -not -path './vendor/*' 2>/dev/null | sort)

.PHONY: help setup test test-e2e lint format check doctor metrics clean ux-check wake \
        wp-up wp-install wp-seed wp-shell wp-logs wp-down wp-nuke key-coverage

help: ## Show available targets
	@grep -hE '^[a-zA-Z_-]+:.*?## ' $(MAKEFILE_LIST) \
		| awk 'BEGIN {FS = ":.*?## "}; {printf "  \033[36m%-14s\033[0m %s\n", $$1, $$2}'

setup: ## Install node dev dependencies
	npm ci

lint: ## Parse-check every tracked PHP file
	@fail=0; \
	for f in $(THEME_PHP); do \
		$(PHP_BIN) -l "$$f" >/dev/null 2>&1 || { echo "SYNTAX ERROR: $$f"; $(PHP_BIN) -l "$$f"; fail=1; }; \
	done; \
	if [ $$fail -eq 0 ]; then echo "lint: OK ($(words $(THEME_PHP)) files)"; else exit 1; fi

format: ## Check formatting (php-cs-fixer / prettier when installed, else SKIP)
	@if command -v php-cs-fixer >/dev/null 2>&1; then \
		php-cs-fixer fix --dry-run --diff .; \
	elif [ -x vendor/bin/php-cs-fixer ]; then \
		vendor/bin/php-cs-fixer fix --dry-run --diff .; \
	else \
		echo "format: SKIP (php-cs-fixer not installed — see aes/tickets/T004)"; \
	fi
	@if [ -x node_modules/.bin/prettier ]; then \
		node_modules/.bin/prettier --check "**/*.{ts,js,json,md}" || exit 1; \
	else \
		echo "format: SKIP (prettier not installed)"; \
	fi

test: test-e2e ## Run the test suite (E2E; SKIPs without a live WordPress)

# `make check` used to print "check: OK" after a SKIP, so an absent WordPress silently
# removed 100% of the coverage while the gate reported success (peer review B1,
# aes/tickets/T015). test-e2e now records what actually happened, and check refuses to
# call that OK. Two distinct cases, deliberately not conflated:
#   - WordPress unreachable  -> SKIP, reported, never "OK"
#   - WordPress reachable but 0 tests ran -> always a FAILURE, never a skip
TEST_STATUS := $(CURDIR)/.test-status
TEST_LOG    := $(CURDIR)/.test-log
# Escape hatch for a run that genuinely must not need WordPress (e.g. a PHP-only lint job).
REQUIRE_TESTS ?= 0

test-e2e: ## Run Playwright E2E against $(WP_URL)
	@# One logical line: in make, each physical line is a separate shell, so a
	@# bare `exit 0` inside an if-block would NOT stop the recipe from continuing.
	@rm -f $(TEST_STATUS) $(TEST_LOG); \
	if [ ! -d node_modules ]; then \
		echo "test: SKIP (run 'make setup' first)"; \
		echo "SKIP" > $(TEST_STATUS); \
	elif ! curl -sf -o /dev/null --max-time 5 "$(WP_URL)"; then \
		echo "test: SKIP (no WordPress answering at $(WP_URL))"; \
		echo "       start a site and re-run, or export WP_URL=..."; \
		echo "SKIP" > $(TEST_STATUS); \
	else \
		WP_URL=$(WP_URL) npx playwright test 2>&1 | tee $(TEST_LOG); \
		pc=$${PIPESTATUS[0]}; \
		echo "RAN" > $(TEST_STATUS); \
		if grep -q "No tests found" $(TEST_LOG); then \
			echo "test: FAIL — WordPress answered at $(WP_URL) but 0 tests executed"; \
			echo "       the suite is empty or every spec was filtered out"; \
			exit 1; \
		fi; \
		exit $$pc; \
	fi

check: lint format doc-consistency key-coverage ## Run every locally-runnable gate
	@rm -f $(TEST_STATUS) $(TEST_LOG); \
	$(MAKE) --no-print-directory test; rc=$$?; \
	status=$$(cat $(TEST_STATUS) 2>/dev/null || echo RAN); \
	rm -f $(TEST_STATUS) $(TEST_LOG); \
	echo ""; \
	if [ "$$rc" -ne 0 ]; then \
		echo "check: FAIL — a gate exited non-zero"; \
		exit 1; \
	elif [ "$$status" = "SKIP" ] && [ "$(REQUIRE_TESTS)" = "1" ]; then \
		echo "check: FAIL — REQUIRE_TESTS=1 but no WordPress answered at $(WP_URL)"; \
		exit 1; \
	elif [ "$$status" = "SKIP" ]; then \
		echo "check: DEGRADED — lint, format, doc-consistency, key-coverage PASSED; test SKIPPED"; \
		echo "         0 of the E2E suite ran. This is NOT a green run and must not be"; \
		echo "         reported as one. Fix with 'make wp-seed', or export WP_URL=<url>."; \
	else \
		echo "check: OK — lint, format, doc-consistency, key-coverage, test"; \
	fi

# --- WordPress environment -------------------------------------------------------
# `make test` SKIPs without a live WordPress. These targets make that SKIP go away.
# The theme is bind-mounted, so edits are live; only the database and uploads persist.

wp-up: ## Start WordPress + MySQL in Docker
	docker compose up -d
	@echo "waiting for WordPress to answer on $(WP_URL)..."
	@for i in $$(seq 1 60); do \
		if curl -sf -o /dev/null --max-time 3 "$(WP_URL)"; then echo "WordPress is up"; exit 0; fi; \
		sleep 2; \
	done; \
	echo "ERROR: WordPress did not become ready in 120s"; \
	echo "  expected: HTTP response at $(WP_URL)"; \
	echo "  found:    $( curl -s -o /dev/null -w '%{http_code}' --max-time 3 $(WP_URL) || echo 'no response' )"; \
	echo "  logs:     docker compose logs wordpress"; \
	exit 1

wp-install: wp-up ## Install WordPress (idempotent) and activate the theme
	@if docker compose run --rm -T cli core is-installed >/dev/null 2>&1; then \
		echo "WordPress already installed"; \
	else \
		docker compose run --rm -T cli core install --url=$(WP_URL) --title="Capuchinho Verde" \
			--admin_user=admin --admin_password=admin --admin_email=dev@example.test --skip-email; \
	fi
	docker compose run --rm -T cli theme activate capuchinhoverde

wp-seed: wp-install ## Seed pages, CPT content, slider, taxonomies and menus
	@bash scripts/seed.sh

wp-shell: ## Open an interactive wp-cli shell
	docker compose run --rm cli shell

wp-logs: ## Tail WordPress and PHP error logs
	docker compose logs -f wordpress

wp-down: ## Stop the containers (data volumes are kept)
	docker compose down

wp-nuke: ## Stop and DELETE the database and uploads
	docker compose down -v
	@echo "volumes removed — run 'make wp-seed' to rebuild"

doc-consistency: ## Fail if docs/ disagrees with the style.css theme header
	@bash scripts/check-doc-consistency.sh

key-coverage: ## Fail if a template reads a theme key that nothing can write
	@bash scripts/check-key-coverage.sh

ux-check: ## UX manifest gate (no-ops when docs/UX/pages/ is absent)
	@if [ -d docs/UX/pages ]; then echo "ux-check: manifests present"; \
	else echo "ux-check: SKIP (no docs/UX/pages/ — classic PHP theme, not an SPA)"; fi

wake: ## Print the current AES state
	@echo "tier:     $$(grep '^tier:' aes/kanban.md | cut -d' ' -f2)"
	@echo "sprint:   $$(grep '^current_sprint:' aes/kanban.md | cut -d' ' -f2)"
	@echo "ticket:   $$(sed -n 's/^current_ticket:[[:space:]]*//p' aes/kanban.md | sed 's/^$$$$/(none set)/')"
	@echo "tickets:"; ls aes/tickets/*.md 2>/dev/null | sed 's|aes/tickets/|  |'

doctor: ## Report the environment and which gates are real vs skipped
	@echo "== environment =="
	@printf "php        %s\n" "$$($(PHP_BIN) -r 'echo PHP_VERSION;' 2>/dev/null || echo MISSING)"
	@printf "node       %s\n" "$$(node --version 2>/dev/null || echo MISSING)"
	@printf "composer   %s\n" "$$(command -v composer || echo MISSING — T004 blocked)"
	@printf "phpstan    %s\n" "$$(command -v phpstan || echo MISSING — T004 blocked)"
	@printf "php-cs-fix %s\n" "$$(command -v php-cs-fixer || echo MISSING — format will SKIP)"
	@printf "playwright %s\n" "$$([ -d node_modules ] && echo installed || echo MISSING — run 'make setup')"
	@echo ""
	@echo "== gates (diagnostic — doctor never fails the build) =="
	@$(MAKE) --no-print-directory lint
	@bash scripts/check-doc-consistency.sh || echo "  ^ BLOCKING for 'make check'; tracked in T002"
	@bash scripts/check-key-coverage.sh | tail -1
	@curl -sf -o /dev/null --max-time 5 "$(WP_URL)" \
		&& echo "e2e        REACHABLE at $(WP_URL)" \
		|| echo "e2e        SKIP (no WordPress at $(WP_URL))"
	@echo ""
	@echo "== aes =="
	@ls aes/kanban.md >/dev/null 2>&1 && echo "kanban     present" || echo "kanban     MISSING"
	@[ -x .git/hooks/pre-commit ] && echo "git hook   installed" || echo "git hook   MISSING"
	@for h in .aes/hooks/*.sh; do bash -n "$$h" 2>/dev/null || echo "hook broken: $$h"; done
	@echo "hooks      $$(ls .aes/hooks/*.sh 2>/dev/null | wc -l) present"

metrics: ## Size and surface-area metrics
	@echo "tracked files: $$(git ls-files | wc -l)"
	@echo "php files:     $$(find . -name '*.php' -not -path './node_modules/*' -not -path './wp-data/*' -not -path './vendor/*' | wc -l)"
	@echo "php LOC:       $$(find . -name '*.php' -not -path './node_modules/*' -not -path './wp-data/*' -not -path './vendor/*' | xargs cat 2>/dev/null | wc -l)"
	@echo "css LOC:       $$(git ls-files '*.css' | xargs cat 2>/dev/null | wc -l)"
	@echo "js LOC:        $$(git ls-files '*.js' | xargs cat 2>/dev/null | wc -l)"
	@echo "tickets open:  $$(grep -l 'status: pending\|status: blocked' aes/tickets/*.md 2>/dev/null | wc -l)"

clean: ## Remove generated test artefacts
	rm -rf test-results playwright-report .playwright-cache $(TEST_STATUS) $(TEST_LOG)
