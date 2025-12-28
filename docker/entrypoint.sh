#!/bin/bash
set -e

echo "Starting Laravel application..."

# Simple wait for MySQL to be ready (Docker healthcheck should handle this, but add small buffer)
sleep 5

# Run migrations
echo "Running database migrations..."
php artisan migrate --force --no-interaction || echo "Migrations failed, but continuing..."

# Create storage link
php artisan storage:link || echo "Storage link already exists"

# Cache for production
if [ "${APP_ENV:-production}" = "production" ]; then
    echo "Caching configuration..."
    php artisan config:cache 2>/dev/null || echo "Config cache skipped"
    php artisan route:cache 2>/dev/null || echo "Route cache skipped"  
    php artisan view:cache 2>/dev/null || echo "View cache skipped"
fi

# Fix permissions
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache 2>/dev/null || true
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache 2>/dev/null || true

echo "✓ Laravel ready!"

# Start PHP-FPM
exec "$@"
