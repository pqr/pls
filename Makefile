composer-update:
	composer update --ignore-platform-reqs

migrate:
	php public/index.php Light/Migrate

light:
	cd src/Light && npm run watch

phpstan:
	XDEBUG_MODE=off php vendor/bin/phpstan analyse

phpstan-baseline:
	XDEBUG_MODE=off vendor/bin/phpstan analyse --generate-baseline