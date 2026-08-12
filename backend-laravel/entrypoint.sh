#!/bin/bash
set -e

if [ ! -f .env ]; then
    cp .env.example .env
fi

# Ensure sqlite database exists
mkdir -p database
touch database/database.sqlite
chmod -R 777 database

# Fix storage permissions for volume mounts
mkdir -p storage/framework/{sessions,views,cache} bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

# Install dependencies if vendor is empty (useful if mounted as volume)
if [ ! -d vendor ]; then
    # Install dependensi Laravel (bypass security advisory via config di composer.json)
    composer install --no-interaction --optimize-autoloader --no-dev
fi

# Generate key if not set
if ! grep -q "APP_KEY=base64:" .env; then
    php artisan key:generate
fi

# Run migrations
php artisan migrate --force

# Seed database with default admin
php artisan db:seed --force

# Link storage
php artisan storage:link || true

# Execute Apache
exec "$@"
