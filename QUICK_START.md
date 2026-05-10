# 🚀 QUICK START GUIDE - Sentinel Backend

Fast reference for the most common tasks.

---

## 📦 Initial Setup (5 minutes)

```bash
git clone git@github.com:yourusername/magang-polda-backend.git
cd sentinel-app
cp .env.example .env
# Edit .env with your credentials
nano .env

composer install --no-dev --optimize-autoloader
php artisan key:generate
php artisan migrate --force
php artisan storage:link
```

---

## 🐳 Docker Deployment (3 steps)

```bash
# 1. Build and start
docker-compose up -d

# 2. Setup database
docker-compose exec web php artisan migrate --force

# 3. Check status
docker-compose ps
```

---

## 🔍 Check Status

```bash
docker-compose ps                    # View containers
docker-compose logs -f web          # View logs
curl http://localhost/up            # Health check
```

---

## 📝 Common Tasks

### Run Crawlers
```bash
docker-compose exec web php artisan crawl:x --limit=50
```

### Run Database Cleanup
```bash
docker-compose exec web php artisan data:retention
```

### Execute Custom Command
```bash
docker-compose exec web php artisan COMMAND_NAME
```

### SSH into Container
```bash
docker-compose exec web /bin/bash
```

### View Application Logs
```bash
docker-compose logs web --tail=100 -f
```

### Backup Database
```bash
docker-compose exec db pg_dump -U sentinel_user sentinel_db | gzip > backup.sql.gz
```

### Restore Database
```bash
gunzip < backup.sql.gz | docker-compose exec -T db psql -U sentinel_user -d sentinel_db
```

---

## 🔧 Troubleshooting

### Permission Error?
```bash
docker-compose exec web chown -R www-data:www-data storage bootstrap
```

### Database Error?
```bash
docker-compose logs db
docker-compose exec db psql -U sentinel_user -d sentinel_db -c "SELECT version();"
```

### Queue Not Working?
```bash
docker-compose exec web php artisan queue:work --once
docker-compose logs web | grep Queue
```

### Clear All Caches
```bash
docker-compose exec web php artisan cache:clear
docker-compose exec web php artisan config:clear
```

---

## 🔐 Security Checklist Before Deploy

- [ ] Change `APP_DEBUG=false` in .env
- [ ] Generate new `SENTINEL_API_KEY`
- [ ] Update all database credentials
- [ ] Update X/Twitter API tokens
- [ ] Set `APP_ENV=production`
- [ ] Set proper `APP_URL`
- [ ] Verify `.env` is in `.gitignore`

---

## 📊 Monitoring

```bash
# Real-time status
watch -n 5 docker-compose ps

# Disk usage
docker-compose exec web df -h

# Memory usage
docker stats --no-stream

# Error tracking
docker-compose logs web | grep ERROR
```

---

## 🔄 Update & Restart

```bash
# Pull latest code
git pull origin main

# Update dependencies
composer update --no-dev

# Restart containers
docker-compose down
docker-compose up -d
```

---

## 💾 Key Files

| File | Purpose |
|------|---------|
| `.env` | Environment configuration (secrets) |
| `.env.example` | Environment template |
| `Dockerfile` | Docker container definition |
| `docker-compose.yml` | Multi-container orchestration |
| `DEPLOYMENT.md` | Full deployment guide |
| `docker/nginx.conf` | Nginx configuration |
| `docker/supervisor.conf` | Process management |

---

## 🚨 Emergency Commands

```bash
# Stop everything
docker-compose down

# Restart all services
docker-compose restart

# Force rebuild
docker-compose build --no-cache
docker-compose up -d

# Rollback to previous version
git revert HEAD
git push origin main
docker-compose down
docker-compose up -d

# Restore from backup
gunzip < backup.sql.gz | docker-compose exec -T db psql -U sentinel_user -d sentinel_db
```

---

## 📞 When Something Goes Wrong

1. **Check logs**: `docker-compose logs web`
2. **Check status**: `docker-compose ps`
3. **Restart**: `docker-compose restart`
4. **Full rebuild**: `docker-compose down && docker-compose up -d`
5. **Restore backup**: Follow [DEPLOYMENT.md](./DEPLOYMENT.md#restore-from-backup)

---

**For detailed information, see [DEPLOYMENT.md](./DEPLOYMENT.md)**
