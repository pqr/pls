#!/usr/bin/env bash

VERSION=$(sentry-cli releases propose-version)
sentry-cli releases new --finalize $VERSION
sentry-cli releases set-commits $VERSION --auto
sentry-cli releases deploys $VERSION new -e production

echo $VERSION > version.txt

sentry-cli update
