# 📚 DEPLOYMENT & MAINTENANCE GUIDE - Sentinel Backend

Complete step-by-step guide for deploying, maintaining, and debugging the Sentinel backend on Jagoan Hosting.

---

## 📋 TABLE OF CONTENTS

1. [Prerequisites](#prerequisites)
2. [Initial Setup & Pull](#initial-setup--pull)
3. [Local Development](#local-development)
4. [Docker Deployment](#docker-deployment)
5. [Jagoan Hosting Deployment](#jagoan-hosting-deployment)
6. [Debugging & Troubleshooting](#debugging--troubleshooting)
7. [Maintenance Tasks](#maintenance-tasks)
8. [Security Checklist](#security-checklist)
9. [Monitoring & Logs](#monitoring--logs)
10. [Rollback Procedures](#rollback-procedures)

---

## Prerequisites

### System Requirements
- **Docker & Docker Compose** (for containerized deployment)
- **PHP 8.3+** (for local development)
- **PostgreSQL 14+** (database)
- **Composer** (PHP package manager)
- **Node.js 18+** (for frontend, if applicable)
- **Git** (version control)

### Jagoan Hosting Requirements
- SSH access enabled
- Docker support
- Minimum 2GB RAM
- 10GB+ storage space
- Port 80/443 available

### Credentials & Access
- GitHub/Git repository SSH key
- Database credentials
- X/Twitter API tokens
- Sentinel API key
- Jagoan Hosting SSH key

---

## Initial Setup & Pull

### Step 1: Clone the Repository

```bash
# Via SSH (recommended for servers)
git clone git@github.com:yourusername/magang-polda-backend.git sentinel-app
cd sentinel-app

# Via HTTPS (if SSH not configured)
git clone https://github.com/yourusername/magang-polda-backend.git sentinel-app
cd sentinel-app
```

### Step 2: Configure Environment Variables

```bash
# Copy example configuration
cp .env.example .env

# Edit with your credentials (use a secure editor)
nano .env
# OR
vi .env
```

**Required values to update in .env:**
```env
APP_URL=https://yourdomain.com
APP_DEBUG=false
APP_ENV=production

# Database (PostgreSQL)
DB_HOST=localhost          # or your database host
DB_PORT=5432
DB_DATABASE=sentinel_db
DB_USERNAME=sentinel_user
DB_PASSWORD=YOUR_SECURE_DB_PASSWORD  # Change this!

# X/Twitter API
X_AUTH_TOKEN=YOUR_X_AUTH_TOKEN
X_CT0=YOUR_X_CT0_TOKEN

# Sentinel API Key (generate: openssl rand -base64 32)
SENTINEL_API_KEY=YOUR_SECURE_API_KEY
```

### Step 3: Install Dependencies

```bash
# Install PHP dependencies
composer install --no-dev --optimize-autoloader

# Generate application key (if .env is new)
php artisan key:generate

# Create necessary directories
mkdir -p storage/app/public
mkdir -p storage/logs
chmod -R 775 storage bootstrap/cache
```

### Step 4: Setup Database

```bash
# Create database (on your PostgreSQL server)
createdb sentinel_db -U postgres

# Run migrations
php artisan migrate --force

# Seed initial data (optional)
php artisan db:seed

# Clear cache and config
php artisan config:clear
php artisan cache:clear
```

---

## Local Development

### Development Environment Setup

```bash
# Install all dependencies (including dev)
composer install

# Copy environment file
cp .env.example .env

# Generate app key
php artisan key:generate

# Create symlink for public storage
php artisan storage:link

# Run migrations
php artisan migrate

# Start local server
php artisan serve
# Access at: http://localhost:8000
```

### Running Commands Locally

```bash
# Crawl X posts
php artisan crawl:x --limit=50

# Run data retention cleanup
php artisan data:retention

# Clear all cache
php artisan cache:clear

# Tail logs
tail -f storage/logs/laravel.log
```

### Database Management Locally

```bash
# Fresh migration (WARNING: deletes data)
php artisan migrate:fresh

# Reset migrations
php artisan migrate:reset

# View all migrations status
php artisan migrate:status

# Seed database
php artisan db:seed
```

---

## Docker Deployment

### Build Docker Image

```bash
# Build the image
docker build -t sentinel-app:latest .

# Verify build
docker images | grep sentinel-app
```

### Run Container Locally

```bash
# Build and start with docker-compose
docker-compose up -d

# Check status
docker-compose ps

# View logs
docker-compose logs -f web

# Stop containers
docker-compose down
```

### Docker Commands Reference

```bash
# Execute command in container
docker-compose exec web php artisan migrate

# SSH into container
docker-compose exec web /bin/bash

# View container logs
docker-compose logs web

# Restart service
docker-compose restart web

# Rebuild image
docker-compose build --no-cache web
```

---

## Jagoan Hosting Deployment

### Step 1: Prepare Jagoan Hosting

```bash
# SSH into your Jagoan Hosting server
ssh your_username@your_server_ip

# Update system packages
sudo apt update && sudo apt upgrade -y

# Install Docker and Docker Compose (if not installed)
sudo apt install -y docker.io docker-compose

# Add your user to docker group
sudo usermod -aG docker $USER
newgrp docker

# Verify Docker installation
docker --version
docker-compose --version
```

### Step 2: Deploy Application

```bash
# Navigate to deployment directory
cd /home/your_user/public_html
# or
cd /var/www

# Clone repository with deployment SSH key
git clone git@github.com:yourusername/magang-polda-backend.git sentinel-app
cd sentinel-app

# Set permissions
chmod -R 755 .

# Copy environment file
cp .env.example .env

# Edit environment with your production credentials
nano .env
```

### Step 3: Configure Production Environment

**Edit .env with production values:**

```bash
# Critical settings
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com
QUEUE_CONNECTION=database

# Database - use Jagoan Hosting's managed PostgreSQL
DB_HOST=your_jagoan_db_host
DB_PORT=5432
DB_DATABASE=sentinel_prod_db
DB_USERNAME=prod_user
DB_PASSWORD=SECURE_PASSWORD_32_CHARS_MINIMUM

# Mail configuration (use Jagoan Hosting's SMTP)
MAIL_MAILER=smtp
MAIL_HOST=your.mailserver.com
MAIL_PORT=587
MAIL_USERNAME=your_email@domain.com
MAIL_PASSWORD=email_password
MAIL_FROM_ADDRESS=noreply@yourdomain.com

# Security - generate with: openssl rand -base64 32
SENTINEL_API_KEY=your_secure_api_key_here
```

### Step 4: Deploy with Docker

```bash
# Start containers in background
docker-compose up -d

# Check if services are running
docker-compose ps

# View startup logs
docker-compose logs -f web

# Run migrations
docker-compose exec web php artisan migrate --force

# Generate optimized autoloader
docker-compose exec web composer dump-autoload -o

# Cache configuration
docker-compose exec web php artisan config:cache
docker-compose exec web php artisan route:cache
docker-compose exec web php artisan view:cache
```

### Step 5: Configure Reverse Proxy (Nginx)

If Jagoan Hosting doesn't auto-setup reverse proxy, create `/etc/nginx/sites-available/sentinel`:

```nginx
upstream sentinel_backend {
    server 127.0.0.1:80;
}

server {
    listen 80;
    listen [::]:80;
    server_name yourdomain.com www.yourdomain.com;

    # Redirect HTTP to HTTPS
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name yourdomain.com www.yourdomain.com;

    ssl_certificate /path/to/ssl/certificate.crt;
    ssl_certificate_key /path/to/ssl/private.key;

    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers HIGH:!aNULL:!MD5;
    ssl_prefer_server_ciphers on;

    root /home/your_user/public_html/sentinel-app/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        proxy_pass http://sentinel_backend;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }

    # Security headers (also set by Laravel middleware)
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
}
```

Enable the site:
```bash
sudo ln -s /etc/nginx/sites-available/sentinel /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl restart nginx
```

### Step 6: Configure SSL Certificate

```bash
# Using Let's Encrypt (Certbot)
sudo apt install -y certbot python3-certbot-nginx

# Generate certificate
sudo certbot certonly --nginx -d yourdomain.com -d www.yourdomain.com

# Auto-renewal (set cron job)
sudo systemctl enable certbot.timer
sudo systemctl start certbot.timer
```

---

## Debugging & Troubleshooting

### Check Service Status

```bash
# View running containers
docker-compose ps

# Check container health
docker-compose exec web curl http://localhost/up

# View application logs
docker-compose logs web | head -100

# View error logs (last 50 lines)
docker-compose logs web --tail=50 -f
```

### Common Issues & Solutions

#### Issue 1: Database Connection Error

```bash
# Check database logs
docker-compose logs db

# Test database connection
docker-compose exec web php -r "
    \$pdo = new PDO('pgsql:host=db;dbname=sentinel_db', 'sentinel_user', 'password');
    echo 'Connection successful';
"

# Verify .env database settings
docker-compose exec web grep DB_ .env
```

#### Issue 2: Permission Denied on Storage/Logs

```bash
# Fix permissions
docker-compose exec web chown -R www-data:www-data /app/storage /app/bootstrap/cache
docker-compose exec web chmod -R 775 /app/storage /app/bootstrap/cache
```

#### Issue 3: Migrations Not Running

```bash
# Run migrations with verbose output
docker-compose exec web php artisan migrate --verbose

# Check migration status
docker-compose exec web php artisan migrate:status

# Rollback last batch
docker-compose exec web php artisan migrate:rollback
```

#### Issue 4: Queue Jobs Not Processing

```bash
# Check queue status
docker-compose exec web php artisan queue:failed

# Process queue manually
docker-compose exec web php artisan queue:work --once

# Clear failed jobs
docker-compose exec web php artisan queue:flush
```

#### Issue 5: API Key Middleware 401 Errors

```bash
# Verify API key in .env
docker-compose exec web grep SENTINEL_API_KEY .env

# Test API endpoint
curl -H "x-api-key: YOUR_API_KEY" https://yourdomain.com/api/data

# Regenerate if needed
docker-compose exec web php -r "echo openssl_random_pseudo_bytes(32, true);"
```

### Enable Debug Mode (Temporarily)

⚠️ **NEVER enable in production without supervision!**

```bash
# Temporarily enable debug
docker-compose exec web sed -i 's/APP_DEBUG=false/APP_DEBUG=true/' .env
docker-compose exec web php artisan config:clear

# Tail logs for errors
docker-compose logs -f web

# Disable again after debugging
docker-compose exec web sed -i 's/APP_DEBUG=true/APP_DEBUG=false/' .env
docker-compose exec web php artisan config:clear
```

### View Application Logs

```bash
# Real-time log stream
docker-compose exec web tail -f storage/logs/laravel.log

# Last 100 lines
docker-compose exec web tail -100 storage/logs/laravel.log

# Search for errors
docker-compose exec web grep "ERROR\|Exception" storage/logs/laravel.log

# Clear logs
docker-compose exec web rm storage/logs/laravel.log
```

---

## Maintenance Tasks

### Regular Maintenance Schedule

#### Daily
- Monitor error logs: `docker-compose logs web | grep ERROR`
- Check database size: `docker-compose exec db psql -U sentinel_user -d sentinel_db -c "\l+"`

#### Weekly
```bash
# Clear expired caches
docker-compose exec web php artisan cache:clear

# Optimize application
docker-compose exec web php artisan optimize

# Check backup status
# (Configure your own backup strategy)
```

#### Monthly
```bash
# Update dependencies securely
composer update --no-dev --dry-run  # preview changes
composer update --no-dev             # apply updates

# Run full test suite
./vendor/bin/phpunit

# Database maintenance
docker-compose exec db PGPASSWORD=password psql -U sentinel_user -d sentinel_db -c "VACUUM FULL;"
```

### Backup & Restore

#### Backup Database

```bash
# Backup PostgreSQL
docker-compose exec db pg_dump -U sentinel_user sentinel_db | gzip > sentinel_db_$(date +%Y%m%d_%H%M%S).sql.gz

# Backup application files
tar -czf sentinel-app_$(date +%Y%m%d_%H%M%S).tar.gz \
    --exclude='.git' \
    --exclude='node_modules' \
    --exclude='vendor' \
    --exclude='storage/logs' \
    --exclude='.env' \
    .
```

#### Restore Database

```bash
# Restore from backup
gunzip < sentinel_db_20240510_120000.sql.gz | docker-compose exec -T db psql -U sentinel_user -d sentinel_db

# Verify restoration
docker-compose exec web php artisan migrate:status
```

### Data Retention Cleanup

```bash
# Run cleanup manually
docker-compose exec web php artisan data:retention

# Setup automated cleanup (daily at 2 AM)
# Edit docker/supervisor.conf and add:
# [program:data-retention]
# command=bash -c "while true; do (sleep \$(( ($(date +%s -d '02:00:00') - $(date +%s)) % 86400 )); php /app/artisan data:retention) & wait \$!; done"
```

### Update Dependencies

```bash
# Check for updates
composer outdated

# Update with security focus
composer update --no-dev

# Verify no breaking changes
./vendor/bin/phpunit

# Rebuild autoloader
composer dump-autoload -o

# Restart containers
docker-compose restart web
```

---

## Security Checklist

### Pre-Deployment

- [ ] Change all default credentials in .env
- [ ] Generate secure API key: `openssl rand -base64 32`
- [ ] Set `APP_DEBUG=false`
- [ ] Set `APP_ENV=production`
- [ ] Update `APP_URL` to your domain
- [ ] Review `.env.example` vs `.env` for all required keys
- [ ] Ensure `.env` is in `.gitignore`
- [ ] Remove `.env` from Git history if accidentally committed

### Post-Deployment

```bash
# Verify .env is not accessible
curl https://yourdomain.com/.env  # should return 403

# Verify git directory is not accessible
curl https://yourdomain.com/.git  # should return 403

# Test HTTPS
curl -I https://yourdomain.com  # verify SSL certificate

# Check security headers
curl -I https://yourdomain.com | grep -E "X-Frame|X-Content|HSTS|CSP"
```

### Regular Security Maintenance

```bash
# Update base image monthly
docker pull php:8.3-fpm-alpine
docker build -t sentinel-app:latest --no-cache .

# Scan dependencies for vulnerabilities
composer audit

# Check file permissions
docker-compose exec web find /app -type f -perm /077 | head -20

# Review access logs for suspicious activity
docker-compose exec web tail -100 /var/log/nginx/access.log | grep -E "403|401|400"
```

---

## Monitoring & Logs

### Real-Time Monitoring

```bash
# Watch all services
watch -n 5 docker-compose ps

# Monitor resource usage
docker stats --no-stream

# Track container metrics
docker-compose exec web free -h  # memory
docker-compose exec web df -h   # disk space
```

### Log Management

#### Application Logs Location
```
storage/logs/laravel.log           # Main application log
storage/logs/queue.log             # Queue worker logs
```

#### Access Logs
```
/var/log/nginx/access.log          # Nginx access logs
/var/log/nginx/error.log           # Nginx errors
```

#### Database Logs
```bash
docker-compose logs db | tail -100
```

### Set Up Log Rotation

Create `/etc/logrotate.d/sentinel-app`:

```conf
/home/*/public_html/sentinel-app/storage/logs/laravel.log {
    daily
    missingok
    rotate 14
    compress
    delaycompress
    notifempty
    create 0664 www-data www-data
    sharedscripts
}
```

---

## Rollback Procedures

### Rollback to Previous Version

```bash
# View deployment history
git log --oneline | head -20

# Rollback to previous commit
git revert HEAD
git push origin main

# Or reset to specific commit (destructive)
git reset --hard COMMIT_HASH

# Restart containers to load new code
docker-compose down
docker-compose up -d
```

### Rollback Database Migrations

```bash
# Check migration history
docker-compose exec web php artisan migrate:status

# Rollback last batch
docker-compose exec web php artisan migrate:rollback

# Rollback all (WARNING: DATA LOSS)
docker-compose exec web php artisan migrate:reset

# Rollback to specific migration
docker-compose exec web php artisan migrate:rollback --step=5
```

### Restore from Backup

```bash
# Stop containers
docker-compose down

# Restore database
gunzip < backup.sql.gz | docker-compose exec -T db psql -U sentinel_user -d sentinel_db

# Restore files
tar -xzf sentinel-app_backup.tar.gz

# Start containers
docker-compose up -d

# Verify integrity
docker-compose exec web php artisan migrate:status
```

---

## Useful Commands Reference

### Container Management
```bash
docker-compose restart web         # Restart Laravel
docker-compose restart db          # Restart database
docker-compose restart              # Restart all
docker-compose stop                # Gracefully stop
docker-compose down                # Stop and remove containers
docker-compose down -v             # Also remove volumes (DATA LOSS!)
```

### Laravel Artisan Commands
```bash
# Cache management
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan cache:clear

# Database
php artisan migrate
php artisan migrate:rollback
php artisan db:seed
php artisan tinker

# Queue
php artisan queue:work
php artisan queue:failed
php artisan queue:retry all
php artisan queue:flush

# Other
php artisan optimize
php artisan storage:link
php artisan make:migration
php artisan make:command
```

### Docker Commands
```bash
docker-compose logs web              # View logs
docker-compose logs -f web           # Follow logs
docker-compose logs --tail=100 web   # Last 100 lines
docker-compose exec web bash         # SSH into container
docker-compose exec web php artisan  # Run artisan command
docker ps                             # List running containers
docker images                         # List images
docker network ls                    # List networks
docker volume ls                     # List volumes
```

---

## Troubleshooting Quick Reference

| Issue | Solution |
|-------|----------|
| **Database connection failed** | Check `.env` DB credentials, verify PostgreSQL is running |
| **Permission denied on storage** | `docker-compose exec web chown -R www-data:www-data storage bootstrap` |
| **Out of memory** | Increase Docker memory limit or reduce worker processes |
| **Migrations failing** | Check `php artisan migrate:status`, check migration files |
| **Queue not processing** | Verify `QUEUE_CONNECTION`, check supervisor logs |
| **API returns 401** | Verify `SENTINEL_API_KEY` in `.env`, check request header |
| **Slow response times** | Check database queries, enable caching, optimize routes |
| **SSL certificate error** | Verify certificate paths, check renewal status |
| **Disk full** | Rotate logs, clean old files, increase storage |

---

## Support & Resources

- **Laravel Documentation**: https://laravel.com/docs/11.x
- **Docker Documentation**: https://docs.docker.com/
- **PostgreSQL Documentation**: https://www.postgresql.org/docs/
- **Jagoan Hosting Support**: https://jagoan.co.id/support

---

## Change Log

### Version 1.0.0 (2024-05-10)
- Initial deployment guide
- Docker containerization
- Security hardening
- Monitoring and maintenance procedures

---

**Last Updated**: 2024-05-10  
**Maintained By**: Backend Team  
**Status**: Production Ready ✅
