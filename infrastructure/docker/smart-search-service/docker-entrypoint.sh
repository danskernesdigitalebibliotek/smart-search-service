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

## Start the PHP process and run command if given. This trick is need to cron imports as k8s cron jobs.
if [ $# -eq 0 ]
 then
    /usr/local/bin/docker-php-entrypoint php-fpm
  else
    exec "$@"
fi
