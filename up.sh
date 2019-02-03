#!/usr/bin/env bash
git checkout master
git pull

php public/index.php Light/Migrate

composer install --no-dev -o

sudo service php7.2-fpm reload

./sentry.sh
