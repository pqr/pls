#!/usr/bin/env bash
git checkout master
git pull

composer install --no-dev -o

php public/index.php Light/Migrate

sudo service php7.4-fpm reload

./sentry.sh
