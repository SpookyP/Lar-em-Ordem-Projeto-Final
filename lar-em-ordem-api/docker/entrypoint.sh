#!/bin/sh
set -e
cd /var/www/html

if [ ! -f .env ]; then
  echo "Falta o .env em lar-em-ordem-api (corre: make init)" >&2
  exit 1
fi

[ -f vendor/autoload.php ] || composer install --no-interaction --prefer-dist

php artisan package:discover --ansi

grep -q '^APP_KEY=.\+' .env || php artisan key:generate --force

if [ "$RUN_MIGRATIONS" = "true" ]; then
  tries=0
  until php -r 'exit(@fsockopen("mysql", 3306) ? 0 : 1);'; do
    tries=$((tries + 1))
    if [ "$tries" -ge 60 ]; then
      echo "A base de dados não respondeu a tempo" >&2
      exit 1
    fi
    echo "A aguardar pela base de dados..." >&2
    sleep 2
  done
  php artisan migrate:fresh --seed
fi

touch /tmp/ready
echo "API pronta" >&2

exec "$@"
