.DEFAULT_GOAL := test

.PHONY: test
test:
	@vendor/bin/phpunit