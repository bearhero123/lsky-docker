#!/usr/bin/env sh
set -eu

cd /var/www/html

mkdir -p storage/app/public
mkdir -p storage/app/uploads
mkdir -p storage/framework/cache storage/framework/sessions storage/framework/testing storage/framework/views
mkdir -p storage/logs bootstrap/cache
mkdir -p public/thumbnails

chown -R www-data:www-data storage bootstrap/cache public/thumbnails
chmod -R ug+rwx storage bootstrap/cache public/thumbnails

if [ ! -L public/i ]; then
    php artisan storage:link >/dev/null 2>&1 || true
fi

exec "$@"
