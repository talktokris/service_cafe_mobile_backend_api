# Deploy mobile API (backend-api) to cPanel

**Server paths (read first):** [../docs/SERVER_PATHS.md](../docs/SERVER_PATHS.md)

## Where files go on the server

| What | Path |
|------|------|
| Laravel app (upload target) | `~/repositories/backend-api` |
| File Manager | **repositories** → **backend-api** |
| Full path | `/home4/servi5ne/repositories/backend-api` |
| Public URL (symlink) | `~/public_html/backend-mobile-api` → `backend-api/public` |

The API is a **sibling** of `service_cafe`, not inside it.

## Config

```bash
cd backend-api
cp deploy-config.env.example deploy-config.env
```

Required in `deploy-config.env`:

```env
REMOTE_PATH=repositories/backend-api
REMOTE_PUBLIC_LINK=public_html/backend-mobile-api
LOCAL_VENDOR=1
```

## Deploy

```bash
cd backend-api
npm run deploy
```

With migrations (after `.env` exists on server):

```bash
npm run deploy:migrate
```

## API URL (Flutter)

```text
https://servecafe.com/backend-mobile-api/api
```

Set in `mobile-app/lib/core/config/api_config.dart` → `productionApiBaseUrl`.

## First-time on server

```bash
ssh servi5ne@servecafe.com
cd ~/repositories/backend-api
cp .env.example .env
# DB_* same as web app; APP_URL=https://servecafe.com/backend-mobile-api
php artisan key:generate
php artisan migrate --force
```

## Troubleshooting

- **Wrong folder in File Manager:** Open `repositories/backend-api`, not `service_cafe` and not `public_html/backend-mobile-api` (that is only the symlink).
- **Old `deploy-to-cpanel.sh` on server:** Safe to delete; use Mac `deploy.sh` only.
- **404 on API:** Re-run `npm run deploy` to refresh the `public_html/backend-mobile-api` symlink.
