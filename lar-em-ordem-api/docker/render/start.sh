#!/bin/sh
set -e
cd /var/www/html

export PORT="${PORT:-10000}"
envsubst '${PORT}' < /etc/nginx/templates/default.conf.template > /etc/nginx/conf.d/default.conf

php artisan package:discover --ansi
php artisan migrate --force

php artisan config:cache
php artisan route:cache || echo "route:cache failed, continuing without it" >&2
php artisan view:cache || echo "view:cache failed, continuing without it" >&2

chown -R www-data:www-data storage bootstrap/cache

exec supervisord -c /etc/supervisord.conf