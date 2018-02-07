#!/usr/bin/env bash
git checkout master
git pull

php public/index.php Light/Migrate

./sentry.sh
