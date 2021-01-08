#!/bin/sh

set -eux

## Run templates with configuration.
/usr/local/bin/confd --onetime --backend env --confdir /etc/confd

## Start prometheus export
/usr/local/bin/php-fpm_exporter server &

## Warm-up symfony cache (with the current configuration).
/var/www/html/bin/console --env=prod cache:warmup --quiet

## Create database (SQLite)
/var/www/html/bin/console doctrine:database:create --no-interaction --quiet
/var/www/html/bin/console doctrine:migrations:migrate --no-interaction --quiet

## Start the PHP process.
/usr/local/bin/docker-php-entrypoint php-fpm
