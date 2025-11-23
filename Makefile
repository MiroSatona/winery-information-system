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

pack:
	zip -r xlogin00.zip . \
		-x "phpcs.xml" \
		-x "phpstan.neon" \
		-x "composer.lock" \
		-x ".gitignore" \
		-x ".gitattributes" \
		-x "vendor/*" \
		-x "temp/*" \
		-x "log/*" \
		-x "resources/*" \
		-x ".git/*" \
		-x ".devcontainer/*" \
		-x "xlogin00.zip"


.PHONY: stan cs cs-fix check pack