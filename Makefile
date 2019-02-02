composer-update:
	composer update --ignore-platform-reqs

migrate:
	php public/index.php Light/Migrate

test:
	vendor/bin/codecept run functional

test-no-pdf:
	vendor/bin/codecept run functional --skip-group pdf

docker-composer-update:
	docker-compose exec php-fpm composer update

docker-migrate:
	docker-compose exec php-fpm php public/index.php Light/Migrate

docker-test:
	docker-compose exec php-fpm vendor/bin/codecept run functional

docker-test-no-pdf:
	docker-compose exec php-fpm vendor/bin/codecept run functional --skip-group pdf

docker-mysql:
	docker-compose exec mysql mysql