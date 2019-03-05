#!/usr/bin/env bash
git checkout master
git pull

composer install --no-dev -o

php public/index.php Light/Migrate

sudo service php7.2-fpm reload

./sentry.sh
