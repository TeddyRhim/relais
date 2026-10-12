#!/bin/sh
set -e

cd /var/www/html

php artisan config:cache --no-ansi
php artisan route:cache --no-ansi
php artisan view:cache --no-ansi

# Les migrations ne s'exécutent au démarrage que sur demande explicite (service web uniquement).
if [ "$RUN_MIGRATIONS" = "true" ]; then
    php artisan migrate --force --no-ansi
fi

exec "$@"
