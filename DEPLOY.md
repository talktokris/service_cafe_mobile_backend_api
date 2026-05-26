# Deploy mobile API (backend-api) to cPanel

## Server layout

| Purpose | Path |
|---------|------|
| Web app | `~/repositories/service_cafe` |
| **Mobile API** | `~/repositories/backend-api` |
| **Public URL** | `~/public_html/backend-mobile-api` → symlink to `backend-api/public` |

**API base URL (Flutter `ApiConfig`):**

```text
https://servecafe.com/backend-mobile-api/api
```

## Config

```bash
cd backend-api
cp deploy-config.env.example deploy-config.env
```

| Variable | Value |
|----------|--------|
| `SSH_HOST` | `servecafe.com` |
| `SSH_USER` | `servi5ne` |
| `REMOTE_PATH` | `repositories/backend-api` |
| `REMOTE_PUBLIC_LINK` | `public_html/backend-mobile-api` |
| `LOCAL_VENDOR` | `1` (install Composer deps on Mac, upload `vendor/`) |

`deploy-config.env` is gitignored — never commit it.

## Deploy

From **Terminal.app**:

```bash
cd backend-api
npm run deploy
```

With migrations (only after `.env` exists on the server):

```bash
npm run deploy:migrate
```

Or:

```bash
./deploy.sh
MIGRATE=1 CLEAR_CACHE=1 ./deploy.sh
```

## First-time server setup

After the first deploy:

```bash
ssh servi5ne@servecafe.com
cd ~/repositories/backend-api
cp .env.example .env
# Edit .env: DB_*, APP_KEY, APP_URL=https://servecafe.com/backend-mobile-api
php artisan key:generate
php artisan migrate --force
```

Use the **same database** as the web app if both apps share data.

## Troubleshooting

- **Composer on server:** Keep `LOCAL_VENDOR=1` in `deploy-config.env` (default).
- **404 on `/backend-mobile-api`:** Re-run deploy; script recreates the `public_html/backend-mobile-api` symlink.
- **Wrong folder:** `REMOTE_PATH` must be `repositories/backend-api`, not inside `service_cafe/`.
- **Password deploy:** Run from Terminal.app, or set `SSH_KEY=~/.ssh/id_rsa` in `deploy-config.env`.
