#!/bin/bash
set -e

echo "Starting Laravel application initialization..."

# Wait for database to be ready
echo "Waiting for database connection..."
MAX_TRIES=30
COUNTER=0
until mysqladmin ping -h"${DB_HOST:-mysql}" -u"${DB_USERNAME:-root}" -p"${DB_ROOT_PASSWORD:-salimbay_boosttio}" --silent 2>/dev/null; do
    echo "Database is unavailable - waiting... (attempt $COUNTER/$MAX_TRIES)"
    sleep 2
    COUNTER=$((COUNTER+1))
    if [ $COUNTER -ge $MAX_TRIES ]; then
        echo "ERROR: Database connection failed after $MAX_TRIES attempts"
        echo "Please check your database credentials and that MySQL is running"
        exit 1
    fi
done
echo "Database is ready!"

# Check if we're running migrations
if [ "${RUN_MIGRATIONS:-true}" = "true" ]; then
    echo "Running database migrations..."
    php artisan migrate --force --no-interaction
    echo "Migrations completed!"
fi

# Link storage directory if not already linked
if [ ! -L /var/www/html/public/storage ]; then
    echo "Creating storage symlink..."
    php artisan storage:link
fi

# Clear and cache configuration for production
if [ "${APP_ENV:-production}" = "production" ]; then
    echo "Optimizing application for production..."
    
    php artisan config:cache
    echo "✓ Configuration cached"
    
    php artisan route:cache
    echo "✓ Routes cached"
    
    php artisan view:cache
    echo "✓ Views cached"
    
    php artisan event:cache
    echo "✓ Events cached"
else
    echo "Development mode - skipping caching..."
    php artisan config:clear
    php artisan route:clear
    php artisan view:clear
fi

# Set permissions
echo "Setting file permissions..."
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

echo "✓ Laravel application is ready!"
echo "Starting PHP-FPM..."

# Execute the main command
exec "$@"
