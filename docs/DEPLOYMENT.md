# Keyora — Deployment Runbook

> **Scope**: Internal tool for a single company with subsidiaries, max ~100 staff.
> **Threat model**: Behind VPN/firewall, authenticated employees, no public attack surface.

---

## 1. Server Requirements

### Production Server

| Requirement | Minimum | Recommended |
|---|---|---|
| **PHP** | 8.3 | 8.4+ |
| **PHP Extensions** | bcmath, ctype, json, mbstring, openssl, pdo, tokenizer, xml | + redis, gd (if file previews needed) |
| **Database** | MySQL 8.0 / PostgreSQL 16 | PostgreSQL 16 |
| **Redis** | 7.0+ (for queue + cache) | 7.2+ |
| **Web Server** | Nginx + PHP-FPM | Nginx + PHP-FPM |
| **Storage** | 10 GB (for encrypted files) | 50+ GB depending on usage |
| **Memory** | 2 GB | 4 GB |

### Required Software

- `composer` (PHP dependency manager)
- `mysqldump` or `pg_dump` (for database backups)
- `supervisor` or `systemd` (for queue worker process management)
- `cron` (for scheduled tasks)

---

## 2. Environment Variables

Copy `.env.example` to `.env` and configure the following:

### Critical Settings

```bash
# Application
APP_NAME=Keyora
APP_ENV=production
APP_KEY=              # Generate with: php artisan key:generate
APP_DEBUG=false
APP_URL=https://keyora.your-company.internal

# Database
DB_CONNECTION=pgsql   # or mysql
DB_HOST=127.0.0.1
DB_PORT=5432          # 3306 for mysql
DB_DATABASE=keyora
DB_USERNAME=keyora
DB_PASSWORD=<strong-password>

# Redis (queue + cache)
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=<redis-password>
REDIS_PORT=6379

# Queue — MUST be redis in production, not sync
QUEUE_CONNECTION=redis

# Cache
CACHE_STORE=redis

# Mail
MAIL_MAILER=smtp
MAIL_HOST=your-smtp-server
MAIL_PORT=587
MAIL_USERNAME=<smtp-user>
MAIL_PASSWORD=<smtp-password>
MAIL_FROM_ADDRESS="noreply@your-company.internal"
MAIL_FROM_NAME="Keyora"

# Filesystem — local private disk for encrypted files
FILESYSTEM_DISK=local
```

### Security-Critical Settings

- **`APP_KEY`**: Must be generated and kept secret. If rotated, all encrypted data becomes undecryptable (see §6.2).
- **`APP_DEBUG`**: Must be `false` in production.
- **`QUEUE_CONNECTION`**: Must be `redis` (not `sync`). Synchronous queues will block HTTP responses while sending email.

---

## 3. Installation

### First-Time Setup

```bash
# 1. Clone the repository
git clone <repo-url> /opt/keyora
cd /opt/keyora/src

# 2. Install PHP dependencies (no dev packages in production)
composer install --no-dev --optimize-autoloader

# 3. Configure environment
cp .env.example .env
php artisan key:generate
# Edit .env with production values (see §2)

# 4. Run database migrations
php artisan migrate --force

# 5. Create the storage symlink
php artisan storage:link

# 6. Set permissions
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

# 7. Cache configuration for production
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Queue Worker (Supervisor)

Create `/etc/supervisor/conf.d/keyora-worker.conf`:

```ini
[program:keyora-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /opt/keyora/src/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/opt/keyora/src/storage/logs/worker.log
stopwaitsecs=3600
```

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start keyora-worker:*
```

### Scheduled Tasks (Cron)

Add to crontab for the `www-data` user:

```bash
* * * * * cd /opt/keyora/src && php artisan schedule:run >> /dev/null 2>&1
```

This runs the following scheduled tasks:
- **Every minute**: Revoke expired access grants (`access:check-expired`)
- **Every 15 min**: Detect suspicious activity (`security:detect-suspicious`)
- **Every 5 min**: Warm dashboard caches
- **Hourly**: Send expiration warnings, check expiring/expired access
- **Daily at midnight**: Soft-delete expired secure files
- **Daily at 2 AM**: Database backup with 7-day rotation (`db:backup --keep=7`)

---

## 4. Backup Procedure

### Automated Backups

Backups run automatically via the scheduler at 2 AM daily. The `db:backup` command:
- Creates a SQL dump in `storage/app/backups/`
- Rotates old backups (keeps 7 by default)
- Supports MySQL (`mysqldump`), PostgreSQL (`pg_dump`), and SQLite

**Backup location**: `storage/app/backups/keyora_backup_YYYY-MM-DD_HHMMSS.sql`

### Manual Backup

```bash
php artisan db:backup --keep=7
# Or with a custom path:
php artisan db:backup --path=/mnt/backups/keyora --keep=30
```

### Pre-Migration Backup

Always take a backup before running migrations:

```bash
php artisan db:backup --keep=7
php artisan migrate --force
```

### What to Back Up

1. **Database** — automated via `db:backup`
2. **Encrypted files** — `storage/app/private/` (user-uploaded secure files)
3. **APP_KEY** — stored in `.env`. Without this, all encrypted data is unrecoverable.
4. **`.env` file** — contains all configuration secrets

### Recommended: Off-Site Backup

```bash
# Sync backups to off-site storage daily
rsync -avz /opt/keyora/src/storage/app/backups/ backup-server:/backups/keyora/
# Sync encrypted files
rsync -avz /opt/keyora/src/storage/app/private/ backup-server:/backups/keyora-files/
```

---

## 5. Restore Procedure

### 5.1 Database Restore

#### PostgreSQL

```bash
# 1. Stop the application (queue workers, web server)
sudo supervisorctl stop keyora-worker:*
sudo systemctl stop nginx

# 2. Drop and recreate the database
psql -U postgres -c "DROP DATABASE keyora;"
psql -U postgres -c "CREATE DATABASE keyora OWNER keyora;"

# 3. Restore from backup
psql -U keyora -d keyora < /opt/keyora/src/storage/app/backups/keyora_backup_YYYY-MM-DD_HHMMSS.sql

# 4. Restart services
sudo systemctl start nginx
sudo supervisorctl start keyora-worker:*
```

#### MySQL

```bash
# 1. Stop services
sudo supervisorctl stop keyora-worker:*
sudo systemctl stop nginx

# 2. Drop and recreate
mysql -u root -p -e "DROP DATABASE keyora; CREATE DATABASE keyora;"

# 3. Restore
mysql -u keyora -p keyora < /opt/keyora/src/storage/app/backups/keyora_backup_YYYY-MM-DD_HHMMSS.sql

# 4. Restart
sudo systemctl start nginx
sudo supervisorctl start keyora-worker:*
```

### 5.2 File Restore

```bash
# Restore encrypted files from backup
rsync -avz backup-server:/backups/keyora-files/ /opt/keyora/src/storage/app/private/

# Fix permissions
chown -R www-data:www-data /opt/keyora/src/storage/app/private/
```

### 5.3 APP_KEY Restore

If `APP_KEY` is lost, **all encrypted data is permanently unrecoverable**. There is no way to restore data without the original key. Keep the key backed up in a secure location (password manager, vault).

---

## 6. Update Procedure

### 6.1 Standard Update

```bash
cd /opt/keyora

# 1. Put the app in maintenance mode
php artisan down --message="Upgrading Keyora" --retry=60

# 2. Pull the latest code
git pull origin main

# 3. Install/update dependencies
cd src
composer install --no-dev --optimize-autoloader

# 4. Take a pre-migration backup
php artisan db:backup --keep=7

# 5. Run migrations
php artisan migrate --force

# 6. Clear and rebuild caches
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# 7. Restart queue workers (to pick up code changes)
sudo supervisorctl restart keyora-worker:*

# 8. Bring the app back up
php artisan up
```

### 6.2 APP_KEY Rotation

**WARNING**: Rotating `APP_KEY` will make all encrypted data (passwords, notes, custom fields) permanently undecryptable. Do NOT rotate the key unless you have a plan to re-encrypt all data.

If key rotation is absolutely necessary:
1. Write a migration script that decrypts all data with the old key
2. Rotate the key
3. Re-encrypt all data with the new key
4. Test thoroughly on a staging environment first

---

## 7. Troubleshooting

### 7.1 Common Issues

| Issue | Cause | Fix |
|---|---|---|
| **500 errors on all requests** | `APP_KEY` missing or changed | Check `.env`, run `php artisan config:clear` |
| **Email not sending** | `QUEUE_CONNECTION=sync` | Set to `redis`, restart queue workers |
| **Queue jobs not processing** | Queue worker not running | `sudo supervisorctl status keyora-worker` |
| **Decryption errors in logs** | `APP_KEY` changed or data corrupted | Check `Encryptable decryption failed` log entries — affected fields return `null` (fail-closed) |
| **Dashboard not updating** | Cache not invalidated | `php artisan cache:clear` |
| **Login fails after deploy** | Sanctum tokens invalidated | Users need to re-authenticate |
| **403 on all API requests** | Tenant not resolved | Check `X-Tenant-ID` header is being sent |

### 7.2 Useful Commands

```bash
# Check application status
curl http://localhost/api/v1/health

# View scheduled tasks
php artisan schedule:list

# Check queue worker status
sudo supervisorctl status keyora-worker

# View failed jobs
php artisan queue:failed

# Retry a failed job
php artisan queue:retry <job-id>

# Clear all caches (debugging only — don't run in prod without reason)
php artisan optimize:clear

# Check Telescope (if enabled in dev)
# Visit http://your-host/telescope
``### 7.3 Log Locations

| Log | Location | Purpose |
|---|---|---|
| **Application logs** | `storage/logs/laravel.log` | General errors, decryption failures |
| **Queue worker logs** | `storage/logs/worker.log` (via supervisor) | Job processing errors |
| **Supervisor logs** | `/var/log/supervisor/` | Worker process management |
| **Nginx logs** | `/var/log/nginx/` | HTTP access and errors |
| **Activity logs** | In database (`activity_logs` table) | Audit trail (viewable via API) |

---

## 8. Health Monitoring

### Health Check Endpoint

```bash
# Unauthenticated health check for load balancers
curl http://your-host/api/v1/health
```

Response (200 = healthy, 503 = degraded):
```json
{
  "status": "ok",
  "checks": {
    "database": { "status": "ok" },
    "cache": { "status": "ok" },
    "storage": { "status": "ok" }
  },
  "timestamp": "2026-09-03T00:00:00+00:00"
}
```

### Recommended Monitoring

- Monitor `/api/v1/health` every 60 seconds
- Alert on 503 status or any check with `"status": "failed"`
- Monitor queue worker process count (should always be > 0)
- Monitor disk space on `storage/` (backups + encrypted files)
- Monitor `Encryptable decryption failed` log entries (indicates key or data issues)
