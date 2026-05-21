# Deploy mobile API (backend-api) to cPanel

Same workflow as the web app: **SSH + rsync** from your Mac, with the Laravel app under `repositories/service_cafe/backend-api` and the public site at `public_html/backend-mobile-api`.

---

## Server layout (matches your cPanel)

| Purpose | Path on server |
|--------|----------------|
| Web app (existing) | `~/repositories/service_cafe` (Laravel `artisan` + `public/`) |
| **Mobile API (this app)** | `~/repositories/service_cafe/backend-api` |
| **Public API URL** | `~/public_html/backend-mobile-api` → symlink to `backend-api/public` |

**Live API base URL for the Flutter app:**

```text
https://servecafe.com/backend-mobile-api/api
```

Example login: `POST https://servecafe.com/backend-mobile-api/api/auth/login`

---

## One-time setup on cPanel

1. You already created **`public_html/backend-mobile-api`** — good. The deploy script will replace it with a **symlink** to `backend-api/public` (do not upload files manually into that folder long-term).

2. **SSH** — same as web: cPanel → **SSH Access** (user `servi5ne`, host `servecafe.com`).

3. **MySQL** — use the **same database** as the web app (recommended) or a new database; you will put credentials in `.env` on the server.

---

## Deploy from your computer

### 1. Config

```bash
cd backend-api
cp deploy-config.env.example deploy-config.env
```

Edit `deploy-config.env` (same SSH as web app):

```env
SSH_HOST=servecafe.com
SSH_USER=servi5ne
REMOTE_PATH=repositories/service_cafe/backend-api
REMOTE_PUBLIC_LINK=public_html/backend-mobile-api
SSH_PORT=22
```

### 2. Run deploy

```bash
chmod +x deploy.sh deploy-to-cpanel.sh
./deploy.sh
```

With migrations + cache (only after `.env` exists on server):

```bash
MIGRATE=1 CLEAR_CACHE=1 ./deploy.sh
```

The script will:

- Upload code (no `vendor/`, no `.env`)
- Run `composer install --no-dev` on the server
- Set `storage` / `bootstrap/cache` permissions
- Symlink `public_html/backend-mobile-api` → `backend-api/public`
- Add `RewriteBase /backend-mobile-api/` in `public/.htaccess` when needed

---

## Configure `.env` on the server (you do this)

SSH in:

```bash
ssh servi5ne@servecafe.com
cd ~/repositories/service_cafe/backend-api
```

**First time only:**

```bash
cp .env.production.example .env
# Or: cp .env.example .env
nano .env
```

**Important values:**

| Variable | Example |
|----------|---------|
| `APP_ENV` | `production` |
| `APP_DEBUG` | `false` |
| `APP_URL` | `https://servecafe.com/backend-mobile-api` |
| `APP_KEY` | Run `php artisan key:generate` after creating `.env` |
| `DB_*` | Same as web app `.env` in `repositories/service_cafe` |

Generate key and migrate:

```bash
php artisan key:generate
php artisan migrate --force
php artisan config:cache
```

**Test API:**

```bash
curl -s https://servecafe.com/backend-mobile-api/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"test@example.com","password":"password"}'
```

(Use a real member email/password from your DB.)

---

## Mobile app — point to production API

Edit `mobile-app/lib/core/config/api_config.dart`:

```dart
static const String productionApiBaseUrl =
    'https://servecafe.com/backend-mobile-api/api';
```

Build/run:

```bash
flutter build apk --dart-define=API_BASE_URL=https://servecafe.com/backend-mobile-api/api
```

---

## Deploy via Git (alternative, like web CI)

If the full monorepo is on the server at `~/repositories/service_cafe`:

```bash
ssh servi5ne@servecafe.com
cd ~/repositories/service_cafe
git pull origin main
cd backend-api
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache
ln -sfn "$(pwd)/public" "$HOME/public_html/backend-mobile-api"
```

---

## Troubleshooting

| Issue | Fix |
|-------|-----|
| 404 on `/backend-mobile-api/api/...` | Check symlink: `ls -la ~/public_html/backend-mobile-api` |
| 500 error | `tail ~/repositories/service_cafe/backend-api/storage/logs/laravel.log` |
| DB connection | Match web `.env` `DB_*`; cPanel often uses `localhost` |
| CORS (browser only) | Mobile app uses Bearer tokens; CORS is permissive in `config/cors.php` |
| `composer` not found | Use cPanel **Select PHP Version** → enable composer or SSH path from `which composer` |

---

## Quick reference

| Goal | Command |
|------|---------|
| Deploy API files | `cd backend-api && ./deploy.sh` |
| Deploy + migrate | `MIGRATE=1 ./deploy.sh` (needs `.env` on server) |
| Edit production DB | SSH → `nano ~/repositories/service_cafe/backend-api/.env` |
