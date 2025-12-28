#!/bin/bash
set -e

echo "Starting Laravel application..."

# Simple wait for MySQL to be ready (Docker healthcheck should handle this, but add small buffer)
sleep 5

# Install/update composer dependencies (needed because volume mounts overwrite the built vendor dir)
echo "Installing dependencies..."
composer install --no-interaction --prefer-dist --optimize-autoloader 2>/dev/null || echo "Composer install skipped"

# Run migrations
echo "Running database migrations..."
php artisan migrate --force --no-interaction || echo "Migrations failed, but continuing..."

# Create storage link
php artisan storage:link || echo "Storage link already exists"

# Fix permissions
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache 2>/dev/null || true
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache 2>/dev/null || true

echo "✓ Laravel ready!"

# Start PHP-FPM
exec "$@"
