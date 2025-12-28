# Docker Deployment Guide

This guide explains how to deploy the Boost.io Laravel application using Docker.

## Prerequisites

- Docker Engine 20.10 or higher
- Docker Compose V2
- At least 4GB RAM available for Docker
- Git (for cloning the repository)

## Quick Start

### 1. Environment Setup

Create a `.env` file from the example (if not already present):

```bash
cp .env.example .env
```

**Important environment variables for Docker:**

```env
# Application
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:YOUR_GENERATED_KEY

# Database (Docker service names)
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=boost_io
DB_USERNAME=boost_user
DB_PASSWORD=your_secure_password

# Redis (Docker service name)
REDIS_HOST=redis
REDIS_PORT=6379
CACHE_DRIVER=redis
SESSION_DRIVER=redis

# Queue
QUEUE_CONNECTION=database  # or 'redis' for better performance
```

### 2. Build and Start Services

```bash
# Build Docker images
docker-compose build

# Start all services in detached mode
docker-compose up -d

# View logs
docker-compose logs -f
```

The application will be available at:
- **Application**: http://localhost:8000
- **PHPMyAdmin** (dev only): http://localhost:8080

### 3. Initial Setup

After starting the containers for the first time:

```bash
# Generate application key (if not set)
docker-compose exec app php artisan key:generate

# Run migrations (if not auto-run)
docker-compose exec app php artisan migrate --force

# Create storage symlink
docker-compose exec app php artisan storage:link

# (Optional) Seed database
docker-compose exec app php artisan db:seed
```

## Service Management

### Start Services
```bash
docker-compose up -d
```

### Stop Services
```bash
docker-compose down
```

### Restart Services
```bash
docker-compose restart
```

### View Logs
```bash
# All services
docker-compose logs -f

# Specific service
docker-compose logs -f app
docker-compose logs -f nginx
docker-compose logs -f queue
```

### Access Container Shell
```bash
# PHP application
docker-compose exec app bash

# MySQL
docker-compose exec mysql bash

# Redis CLI
docker-compose exec redis redis-cli
```

## Common Tasks

### Run Artisan Commands
```bash
docker-compose exec app php artisan [command]

# Examples:
docker-compose exec app php artisan migrate
docker-compose exec app php artisan cache:clear
docker-compose exec app php artisan queue:work
```

### Run Composer Commands
```bash
docker-compose exec app composer [command]

# Examples:
docker-compose exec app composer install
docker-compose exec app composer update
```

### Database Management

#### Access MySQL CLI
```bash
docker-compose exec mysql mysql -u boost_user -p boost_io
```

#### Backup Database
```bash
docker-compose exec mysql mysqldump -u boost_user -p boost_io > backup.sql
```

#### Restore Database
```bash
cat backup.sql | docker-compose exec -T mysql mysql -u boost_user -p boost_io
```

### Clear All Caches
```bash
docker-compose exec app php artisan optimize:clear
```

## Production Deployment

### 1. Environment Configuration

Ensure your `.env` file has production settings:

```env
APP_ENV=production
APP_DEBUG=false
LOG_LEVEL=error
```

### 2. Build Production Images

```bash
docker-compose build --no-cache
```

### 3. Deploy with Resource Limits

Create a `docker-compose.prod.yml` override:

```yaml
version: '3.8'

services:
  app:
    deploy:
      resources:
        limits:
          cpus: '2'
          memory: 1G
        reservations:
          cpus: '1'
          memory: 512M
      restart_policy:
        condition: on-failure
        max_attempts: 3
```

Deploy:
```bash
docker-compose -f docker-compose.yml -f docker-compose.prod.yml up -d
```

### 4. Enable PHPMyAdmin in Development Only

```bash
# Start with PHPMyAdmin
docker-compose --profile dev up -d

# Production (without PHPMyAdmin)
docker-compose up -d
```

## Troubleshooting

### Container Won't Start

**Check logs:**
```bash
docker-compose logs app
```

**Rebuild containers:**
```bash
docker-compose down
docker-compose build --no-cache
docker-compose up -d
```

### Database Connection Issues

**Verify MySQL is healthy:**
```bash
docker-compose ps mysql
```

**Check database credentials:**
```bash
docker-compose exec app php artisan db:show
```

### Permission Issues

**Fix storage permissions:**
```bash
docker-compose exec app chown -R www-data:www-data storage bootstrap/cache
docker-compose exec app chmod -R 775 storage bootstrap/cache
```

### Queue Not Processing Jobs

**Check queue worker logs:**
```bash
docker-compose logs -f queue
```

**Restart queue worker:**
```bash
docker-compose restart queue
```

### Out of Memory

**Reduce resource usage:**
```bash
# Restart services one by one
docker-compose restart app
docker-compose restart nginx
```

**Check memory usage:**
```bash
docker stats
```

## Data Persistence

The following data is persisted in Docker volumes:

- **mysql_data**: MySQL database files
- **redis_data**: Redis persistence data

### Backup Volumes

```bash
# Stop services
docker-compose down

# Backup volumes
docker run --rm -v boost_mysql_data:/data -v $(pwd):/backup alpine tar czf /backup/mysql-backup.tar.gz /data

# Restore volumes
docker run --rm -v boost_mysql_data:/data -v $(pwd):/backup alpine tar xzf /backup/mysql-backup.tar.gz -C /
```

## Updating the Application

```bash
# Pull latest code
git pull origin main

# Rebuild images
docker-compose build

# Restart services
docker-compose down
docker-compose up -d

# Run migrations
docker-compose exec app php artisan migrate --force

# Clear caches
docker-compose exec app php artisan optimize:clear
```

## Security Best Practices

1. **Use strong passwords** for database credentials
2. **Never commit** `.env` file to version control
3. **Disable** PHPMyAdmin in production
4. **Use HTTPS** with reverse proxy (Nginx/Traefik) in production
5. **Regularly update** Docker images and dependencies
6. **Limit** exposed ports in production
7. **Monitor** logs for suspicious activity

## Performance Optimization

1. **Enable OPcache** (already configured in `docker/php/custom.ini`)
2. **Use Redis** for cache and sessions
3. **Run queue workers** for background jobs
4. **Cache routes and configs** in production
5. **Use CDN** for static assets
6. **Enable Nginx gzip** compression (already configured)

## Support

For issues or questions:
- Check the logs: `docker-compose logs -f`
- Review Laravel logs: `docker-compose exec app tail -f storage/logs/laravel.log`
- Verify environment configuration
- Ensure all services are healthy: `docker-compose ps`
