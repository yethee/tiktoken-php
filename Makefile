DOCKER_IMAGE = tiktoken-php/php-dev
RUN_CMD = docker run --rm -it -v "$(CURDIR):/workspace" $(DOCKER_IMAGE)

.DEFAULT_GOAL := help

export MSYS_NO_PATHCONV=1

help: ## Show available targets
	@awk 'BEGIN {FS = ":.*##"; printf "\nUsage:\n  make \033[36m<target>\033[0m \n\nTargets:\n"} /^[a-zA-Z_-]+:.*?##/ { printf "  \033[36m%-12s\033[0m %s\n", $$1, $$2 }' $(MAKEFILE_LIST)
.PHONY: help

cargo-build: ## Build lib
	cargo build --release

cargo-clean: ## Clean build artifacts
	cargo clean

cargo-test: ## Run lib tests
	cargo test

cargo-lint: ## Run lint checks
	cargo check --all-targets
	cargo clippy --all-targets -- -D warnings
	cargo fmt --all -- --check

cargo-fix: ## Format lib files
	cargo fix --allow-dirty --allow-staged
	cargo clippy --all-targets --fix --allow-dirty --allow-staged -- -D warnings
	cargo fmt --all

build:  ## Build docker image for dev
	docker build -t $(DOCKER_IMAGE) --target php-dev --file .docker/Dockerfile .

test: build  ## Run tests
	$(RUN_CMD) composer test

bench: build ## Run benchmarks
	$(RUN_CMD) composer bench
