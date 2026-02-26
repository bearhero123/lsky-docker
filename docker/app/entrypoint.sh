#!/usr/bin/env sh
set -eu

cd /var/www/html

if [ ! -f .env ] && [ -f .env.example ]; then
    cp .env.example .env
fi

mkdir -p storage/app/public
mkdir -p storage/app/uploads
mkdir -p storage/framework/cache storage/framework/sessions storage/framework/testing storage/framework/views
mkdir -p storage/logs bootstrap/cache
mkdir -p public/thumbnails

# Installer writes installed.lock in project root.
chown www-data:www-data /var/www/html || true
chmod 775 /var/www/html || true

# Installer updates DB_* values into .env.
if [ -f .env ]; then
    chown www-data:www-data .env || true
    chmod 664 .env || true
fi

chown -R www-data:www-data storage bootstrap/cache public/thumbnails
chmod -R ug+rwx storage bootstrap/cache public/thumbnails

if [ ! -L public/i ]; then
    php artisan storage:link >/dev/null 2>&1 || true
fi

exec "$@"
