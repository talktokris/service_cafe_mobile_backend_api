# Deploy mobile API (backend-api) to cPanel

Same workflow as the web app: **SSH + rsync** from your Mac, with the Laravel app at `repositories/backend-api` and the public site at `public_html/backend-mobile-api`.

---

## Server layout (matches your cPanel)

| Purpose | Path on server |
|--------|----------------|
| Web app (existing) | `~/repositories/service_cafe` |
| **Mobile API (this app)** | `~/repositories/backend-api` |
| **Public API URL** | `~/public_html/backend-mobile-api` → symlink to `backend-api/public` |

### Where to look in File Manager

```text
Home → repositories → backend-api
```

You should see `backend-api` **next to** `service_cafe` (not inside it):

```text
repositories/
  ├── service_cafe/     ← web app
  └── backend-api/      ← mobile API (artisan, app, public, vendor, …)
```

The live URL uses `public_html/backend-mobile-api` (symlink to `backend-api/public`).

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
REMOTE_PATH=repositories/backend-api
REMOTE_PUBLIC_LINK=public_html/backend-mobile-api
SSH_PORT=22
```

**Important:** If you deployed before, update `REMOTE_PATH` in your existing `deploy-config.env` (it is not committed to git).

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

- Upload code (no `.env`; `vendor/` included when `LOCAL_VENDOR=1`)
- Set `storage` / `bootstrap/cache` permissions
- Symlink `public_html/backend-mobile-api` → `repositories/backend-api/public`
- Add `RewriteBase /backend-mobile-api/` in `public/.htaccess` when needed

---

## Configure `.env` on the server (you do this)

SSH in:

```bash
ssh servi5ne@servecafe.com
cd ~/repositories/backend-api
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

**Required for mobile login:** `migrate` creates the `personal_access_tokens` table (Laravel Sanctum).  
If login returns **500 / Server Error**, this migration was likely not run. After migrate, login should return JSON (422 for wrong password, 200 for success).

Check on server:

```bash
php artisan migrate:status | grep personal_access_tokens
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

## Deploy via Git (alternative)

If you clone the API repo under `~/repositories/backend-api`:

```bash
ssh servi5ne@servecafe.com
cd ~/repositories/backend-api
git pull origin main
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache
ln -sfn "$(pwd)/public" "$HOME/public_html/backend-mobile-api"
```

---

## Troubleshooting

| Issue | Fix |
|-------|-----|
| `rsync ... unexpected end of file` / exit 22 | Re-run `./deploy.sh` — script retries with **tar**. Check disk quota in cPanel. |
| `composer not found on server PATH` | Default **`LOCAL_VENDOR=1`**: runs `composer install` on your Mac and uploads `vendor/`. |
| `ln: failed to create symbolic link ... No such file` | Upload failed. Ensure `~/repositories/backend-api/public/index.php` exists. |
| 404 on `/backend-mobile-api/api/...` | Check symlink: `ls -la ~/public_html/backend-mobile-api` |
| 500 error | `tail ~/repositories/backend-api/storage/logs/laravel.log` |
| DB connection | Match web `.env` `DB_*`; cPanel often uses `localhost` |
| Old path under `service_cafe/` | Delete `~/repositories/service_cafe/backend-api` if leftover; redeploy to `repositories/backend-api` |
| Many `._filename` files (163 bytes) | Harmless macOS junk from tar; redeploy runs cleanup. Or SSH: `find ~/repositories/backend-api -name '._*' -delete` |
| HTTP 500: `require PHP >= 8.4.0` | `vendor/` was built on PHP 8.4. Redeploy after `composer config platform.php 8.2.29 && composer update --no-dev`, or set cPanel **PHP 8.4** for this path. |

---

## Quick reference

| Goal | Command |
|------|---------|
| Deploy API files | `cd backend-api && ./deploy.sh` |
| Deploy + migrate | `MIGRATE=1 ./deploy.sh` (needs `.env` on server) |
| Edit production DB | SSH → `nano ~/repositories/backend-api/.env` |
