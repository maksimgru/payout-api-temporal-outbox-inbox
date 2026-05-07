#!/usr/bin/env bash
set -euo pipefail

cd /var/www/html

mkdir -p _volume/mysql _volume/redis _volume/composer

if [ ! -f .env ]; then
  cp .env.example .env
fi

if [ ! -f vendor/autoload.php ]; then
  echo "vendor/autoload.php not found. Waiting for composer install lock..."

  mkdir -p /tmp/composer-lock

  (
    flock -x 200

    if [ ! -f vendor/autoload.php ]; then
      echo "Running composer install..."
      composer install --no-interaction --prefer-dist --optimize-autoloader
    else
      echo "vendor/autoload.php appeared while waiting for lock."
    fi
  ) 200>/tmp/composer-lock/install.lock
fi

if ! grep -q '^APP_KEY=' .env 2>/dev/null; then
  sed -i '1i APP_KEY=' .env
fi

if ! grep -q '^APP_KEY=base64:' .env 2>/dev/null; then
  php artisan key:generate --force --no-interaction || true
fi

if [ "${APP_WAIT_FOR_DB:-true}" = "true" ]; then
  echo "Waiting for MySQL at ${DB_HOST:-mysql}:${DB_PORT:-3306}..."
  until php -r "
    try {
      new PDO(
        'mysql:host=${DB_HOST:-mysql};port=${DB_PORT:-3306};dbname=${DB_DATABASE:-payouts}',
        '${DB_USERNAME:-payouts}',
        '${DB_PASSWORD:-secret}'
      );
      echo 'MySQL is ready.'.PHP_EOL;
      exit(0);
    } catch (Throwable \$e) {
      echo 'MySQL wait error: '.\$e->getMessage().PHP_EOL;
      exit(1);
    }
  "; do
    sleep 2
  done
fi

php artisan config:clear --no-interaction || true
php artisan --version
php -v

if [ "${RUN_MIGRATIONS:-false}" = "true" ]; then
  php artisan migrate --force --no-interaction
fi

exec "$@"
