# Run static analysis
stan:
	docker compose run --rm web vendor/bin/phpstan analyse --configuration=phpstan.neon

# Run code sniffer
cs:
	docker compose run --rm web vendor/bin/phpcs --standard=phpcs.xml

# Auto-fix code style
cs-fix:
	docker compose run --rm web vendor/bin/phpcbf --standard=phpcs.xml

# Run all checks
check: stan cs