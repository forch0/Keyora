# Module 01 — Project Setup & Foundation

| Field | Value |
|---|---|
| **Module** | 01 |
| **Name** | Project Setup & Foundation |
| **Dependencies** | None |
| **Status** | ✅ Complete |

---

## Objective

Initialize the Laravel project, set up Docker for stateful services (PostgreSQL, Redis, Mailpit) with PHP running natively in WSL, install required packages, and set up the foundational architecture (directory structure, base classes, configuration files).

> **Architecture decision:** PHP runs natively in WSL (Ubuntu 24.04) for fast `php artisan` iteration. Only stateful services run in Docker. The `app`/`nginx` service definitions remain in `docker-compose.yml` for optional full-Docker mode but are not used day-to-day.
>
> **Database decision:** PostgreSQL 16 (not MySQL) — chosen for Row-Level Security (multi-tenancy, Module 03), native UUID support (Module 02), JSONB with GIN indexes (audit logging, Module 20), and `pgcrypto` for column-level encryption (a password vault needs this).

---

## Tasks

### 1.1 Create Laravel Project

The Laravel application lives in `src/` to keep the project root clean for docs, Docker, and project-level config.

```bash
composer create-project laravel/laravel src
```

> **Native PHP setup (WSL Ubuntu 24.04):** PHP 8.4 and Composer are installed natively — not via Docker, not via the Windows shim. See §1.2.6 for the install steps.

**Project structure after this step:**

```
Zekura/
├── src/                  ← Laravel application
│   ├── app/
│   ├── bootstrap/
│   ├── config/
│   ├── database/
│   ├── public/
│   ├── resources/
│   ├── routes/
│   ├── storage/
│   ├── tests/
│   ├── composer.json
│   └── .env
├── docker/               ← Docker configs (created in 1.2)
├── docs/                 ← Project documentation
├── docker-compose.yml    ← Created in 1.2
├── README.md
└── .gitignore
```

### 1.2 Set Up Docker (Stateful Services) + Native PHP

Stateful services (PostgreSQL, Redis, Mailpit) run in Docker. PHP runs natively in WSL for fast iteration. The `app`/`nginx` service definitions are kept in `docker-compose.yml` for optional full-Docker mode but are not started during normal development.

#### 1.2.1 Create `docker/Dockerfile`

> Used only when running full-Docker mode (`docker compose up -d --build`). Not used for native PHP development.

```dockerfile
FROM php:8.4-fpm-alpine

# Install system dependencies
RUN apk add --no-cache \
    git curl libpng-dev oniguruma-dev libxml2-dev zip unzip \
    libzip-dev libpq-dev postgresql-client redis

# Install PHP extensions
RUN docker-php-ext-install pdo_pgsql zip exif pcntl gd bcmath

# Install Redis extension
RUN pecl install redis && docker-php-ext-enable redis

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy application code from src/ directory
COPY src/ .

# Set permissions
RUN chown -R www-data:www-data /var/www/html
RUN chmod -R 755 /var/www/html/storage
```

#### 1.2.2 Create `docker/nginx/default.conf`

```nginx
server {
    listen 80;
    server_name localhost;
    root /var/www/html/public;
    index index.php index.html;

    client_max_body_size 12m;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass app:9000;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

#### 1.2.3 Create `docker/php/local.ini`

```ini
memory_limit = 256M
upload_max_filesize = 12M
post_max_size = 12M
max_execution_time = 60
```

#### 1.2.4 Create `docker-compose.yml` (project root)

```yaml
services:
  app:
    build:
      context: .
      dockerfile: docker/Dockerfile
    container_name: zekura-app
    restart: unless-stopped
    working_dir: /var/www/html
    volumes:
      - ./src:/var/www/html
      - ./docker/php/local.ini:/usr/local/etc/php/conf.d/local.ini
    networks:
      - zekura
    depends_on:
      postgres:
        condition: service_healthy
      redis:
        condition: service_healthy

  nginx:
    image: nginx:1.25-alpine
    container_name: zekura-nginx
    restart: unless-stopped
    ports:
      - "8080:80"
    volumes:
      - ./src:/var/www/html
      - ./docker/nginx/default.conf:/etc/nginx/conf.d/default.conf
    networks:
      - zekura
    depends_on:
      - app

  postgres:
    image: postgres:16-alpine
    container_name: zekura-postgres
    restart: unless-stopped
    ports:
      - "5432:5432"
    environment:
      POSTGRES_DB: ${DB_DATABASE:-zekura}
      POSTGRES_USER: ${DB_USERNAME:-zekura}
      POSTGRES_PASSWORD: ${DB_PASSWORD:-secret}
    volumes:
      - zekura-postgres:/var/lib/postgresql/data
    networks:
      - zekura
    healthcheck:
      test: ["CMD-SHELL", "pg_isready -U ${DB_USERNAME:-zekura} -d ${DB_DATABASE:-zekura}"]
      interval: 5s
      timeout: 5s
      retries: 10

  redis:
    image: redis:7-alpine
    container_name: zekura-redis
    restart: unless-stopped
    ports:
      - "6379:6379"
    volumes:
      - zekura-redis:/data
    networks:
      - zekura
    healthcheck:
      test: ["CMD", "redis-cli", "ping"]
      interval: 5s
      timeout: 3s
      retries: 5

  # Optional: Mailpit for local email testing
  mailpit:
    image: axllent/mailpit:latest
    container_name: zekura-mailpit
    restart: unless-stopped
    ports:
      - "1025:1025"   # SMTP
      - "8025:8025"   # Web UI
    networks:
      - zekura

networks:
  zekura:
    driver: bridge

volumes:
  zekura-postgres:
  zekura-redis:
```

> **Day-to-day:** Only `postgres`, `redis`, and `mailpit` are started. The `app` and `nginx` services are kept for optional full-Docker mode but are not used during native PHP development.

#### 1.2.5 Create `.dockerignore`

```
.git
docker
node_modules
src/vendor
src/.env
src/.env.backup
*.md
docs
```

#### 1.2.6 Native PHP Setup (WSL Ubuntu 24.04)

PHP and Composer run natively in WSL — not in Docker, not via the Windows shim.

```bash
# Add ondrej/php PPA and install PHP 8.4 + extensions
sudo add-apt-repository ppa:ondrej/php -y && sudo apt update
sudo apt install -y php8.4 php8.4-cli php8.4-mbstring php8.4-xml php8.4-curl \
    php8.4-pgsql php8.4-redis php8.4-zip php8.4-bcmath php8.4-gd php8.4-intl \
    php8.4-readline php8.4-sqlite3 unzip

# Verify
php -v   # must show 8.4.x

# Install Composer natively (do NOT use the Windows shim at /mnt/c/...)
curl -sS https://getcomposer.org/installer | php -- --install-dir=$HOME/.local/bin --filename=composer
# Ensure $HOME/.local/bin is on PATH (add to ~/.bashrc if missing)
export PATH="$HOME/.local/bin:$PATH"

# Verify (must NOT reference /mnt/c/...)
composer --version
```

> **Why `php8.4-sqlite3`?** The test suite uses in-memory SQLite (configured in `phpunit.xml`) for speed and isolation — separate from the PostgreSQL-backed dev app. Both `pdo_pgsql` (for the app) and `pdo_sqlite` (for tests) are required.

#### 1.2.7 Docker Commands (stateful services only)

```bash
# Start stateful services only (NOT app/nginx — PHP runs natively)
docker compose up -d postgres redis mailpit

# Verify all healthy
docker compose ps

# Stop stateful services
docker compose stop postgres redis mailpit

# Stop and wipe volumes (DESTRUCTIVE — drops all DB data)
docker compose down -v
```

> **Optional full-Docker mode:** `docker compose up -d --build` builds and starts `app` + `nginx` too. Not used day-to-day. If `app`/`nginx` start unexpectedly, stop them: `docker compose stop app nginx` or `docker compose rm -sf app nginx`.

#### 1.2.8 Update `src/.env` for native PHP + Docker stateful services

```
APP_URL=http://localhost:8000

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=zekura
DB_USERNAME=zekura
DB_PASSWORD=secret

QUEUE_CONNECTION=redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

MAIL_MAILER=smtp
MAIL_HOST=127.0.0.1
MAIL_PORT=1025
MAIL_FROM_ADDRESS=noreply@zekura.app
MAIL_FROM_NAME="${APP_NAME}"
```

> **Key difference from Docker-only mode:** Hosts are `127.0.0.1` (not the Docker service names `postgres`/`redis`/`mailpit`) because PHP runs outside Docker and reaches the services via the published ports. `APP_URL` is `:8000` (native `php artisan serve`) not `:8080` (nginx).

### 1.3 Install Required Packages

Run these inside the `src/` directory (native PHP/Composer in WSL):

```bash
cd src

# Sanctum (API authentication)
composer require laravel/sanctum

# Nimbus — in-browser API client (dev only)
# Auto-discovers routes, generates schemas from validation rules, handles auth
# Requires @alpha stability flag (Nimbus is v0.7.0-alpha)
composer require "sunchayn/nimbus:@alpha" --dev

# Laravel Pint (code formatting)
composer require laravel/pint --dev

# Larastan (static analysis)
composer require larastan/larastan --dev
```

> **If `vendor/` was copied from Windows,** reinstall natively: `rm -rf vendor composer.lock && composer install`. The Windows-compiled dependencies are not compatible with Linux PHP.

### 1.4 Configure Environment

- [ ] Copy `src/.env.example` to `src/.env` (overwrite any Windows-sourced copy)
- [ ] Generate application key: `cd src && php artisan key:generate`
- [ ] Configure database connection (PostgreSQL 16 — see §1.2.8)
- [ ] Set `APP_URL=http://localhost:8000` and `APP_ENV=local`
- [ ] Configure mail driver (SMTP → Mailpit on 127.0.0.1:1025)
- [ ] Configure queue connection (`redis` on 127.0.0.1:6379)
- [ ] Configure file storage disk (`local` — private)

### 1.5 Set Up Directory Structure

Create the following directories inside `src/app/` per the architecture guidelines:

```
src/app/
├── Actions/
├── Events/
├── Jobs/
├── Listeners/
├── Notifications/
├── Policies/
├── Rules/
├── Services/
├── Traits/
└── Http/
    ├── Controllers/Api/V1/
    ├── Middleware/
    ├── Requests/
    └── Resources/V1/
```

### 1.6 Configure API-Only Mode

- [ ] Remove default `routes/web.php` (or leave empty)
- [ ] Configure all routes in `routes/api.php`
- [ ] Set up API version group: `Route::prefix('v1')->group(...)`
- [ ] Ensure all responses return JSON (no HTML error pages)
- [ ] Configure CORS for future frontend domain
- [ ] Disable session middleware for API routes

### 1.7 Set Up Code Quality Tools

- [ ] Create `src/pint.json` with project config (see ARCHITECTURE.md §7.2)
- [ ] Create `src/phpstan.neon` with Larastan level 8 (see ARCHITECTURE.md §7.3)
- [ ] Create `src/.editorconfig` (see ARCHITECTURE.md §7.3)
- [ ] Run `cd src && ./vendor/bin/pint` to verify formatting works

### 1.8 Base Configuration Files

- [ ] Configure `src/config/auth.php` — set up Sanctum guard
- [ ] Configure `src/config/cors.php` — allow API origins
- [ ] Configure `src/config/filesystems.php` — ensure private disk exists:

```php
'private' => [
    'driver' => 'local',
    'root' => storage_path('app/private'),
    'visibility' => 'private',
],
```

### 1.9 Base Exception Handler

- [ ] Update `src/app/Exceptions/Handler.php` (or `src/bootstrap/app.php` for Laravel 11+) to return JSON for all errors
- [ ] Ensure no HTML error pages in API mode
- [ ] Add consistent error envelope (see ARCHITECTURE.md §5.4 & §5.9)

### 1.10 Base Test Setup

- [ ] Configure `src/phpunit.xml` for in-memory testing database
- [ ] Create base `TestCase` with `RefreshDatabase` trait
- [ ] Create test helpers (auth helper, tenant setup helper)

---

## Acceptance Criteria

- [x] PHP 8.4.x installed natively in WSL (`php -v`)
- [x] Composer installed natively (not the Windows shim — `composer --version` shows no `/mnt/c/` path)
- [x] `docker compose up -d postgres redis mailpit` starts stateful services without errors
- [x] `docker compose ps` shows `postgres`, `redis`, `mailpit` all healthy
- [x] `app`/`nginx` containers are NOT running (PHP runs natively)
- [x] App is accessible at `http://localhost:8000/api/v1/` (native `php artisan serve`)
- [x] PostgreSQL container is healthy and accepts connections on port 5432
- [x] Redis container is healthy and accepts connections on port 6379
- [x] Mailpit web UI accessible at `http://localhost:8025`
- [x] `php artisan migrate` runs (Laravel default migrations)
- [x] `vendor/bin/pint --test` passes (0 issues)
- [x] `vendor/bin/phpstan analyse --no-progress --memory-limit=512M` passes (No errors)
- [x] `php artisan test` passes (2 passed — matches BUILD_LOG.md)
- [x] API route `GET /api/v1/` returns `{"status":"ok"}` with HTTP 200
- [x] Non-existent route returns `404` as JSON, not HTML
- [x] Directory structure matches ARCHITECTURE.md §6.1

---

## Files Created

| File | Purpose |
|---|---|
| `src/.env` | Environment configuration (native PHP + Docker stateful services) |
| `docker-compose.yml` | Docker Compose service definitions (postgres, redis, mailpit + optional app/nginx) |
| `docker/Dockerfile` | PHP-FPM container image (optional full-Docker mode) |
| `docker/nginx/default.conf` | Nginx reverse proxy config (optional full-Docker mode) |
| `docker/php/local.ini` | PHP INI overrides (optional full-Docker mode) |
| `.dockerignore` | Docker build exclusions |
| `src/pint.json` | Code formatting rules |
| `src/phpstan.neon` | Static analysis config |
| `src/.editorconfig` | Editor settings |
| `src/bootstrap/app.php` | API routing, Sanctum middleware, JSON exception handling |
| `src/routes/api.php` | API route definitions (`GET /api/v1/`) |
| `src/tests/TestCase.php` | Base test case with RefreshDatabase |
| `src/tests/Helpers/AuthHelper.php` | Test auth helpers (user creation, token generation) |
| `src/tests/Helpers/TenantHelper.php` | Test tenant helpers (tenant creation, context setup) |

---

## What This Module Does NOT Include

- User model or authentication (Module 02)
- Tenant model or multi-tenancy (Module 03)
- Any business logic or features
- Any migrations beyond Laravel defaults
