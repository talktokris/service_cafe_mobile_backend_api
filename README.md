# Serve Cafe Backend API

Laravel 11 API for the Serve Cafe mobile app. Shares the same MySQL database as `web-app/`.

## Setup

```bash
cd backend-api
composer install
cp .env.example .env   # configure DB_* to match web-app
php artisan key:generate
php artisan migrate    # only adds sanctum personal_access_tokens if missing
php artisan serve --port=8001
```

## Auth

- `POST /api/auth/login` — `{ email, password }` → `{ token, user }`
- `GET /api/auth/me` — Bearer token
- `POST /api/auth/logout` — Bearer token

Paid-only routes return `403` with `requires_paid: true` for free members.

## Mobile app

Point Flutter to `http://127.0.0.1:8001/api` (Android emulator: `http://10.0.2.2:8001/api`).

## Deploy to cPanel

See **[DEPLOY.md](DEPLOY.md)** — SSH deploy to `repositories/backend-api` with public URL `public_html/backend-mobile-api`.

```bash
cp deploy-config.env.example deploy-config.env
./deploy.sh
```
