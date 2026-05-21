#!/usr/bin/env bash
#
# Deploy backend-api (Laravel mobile API) to cPanel via SSH/rsync.
# 1. cp deploy-config.env.example deploy-config.env  (fill SSH + paths)
# 2. ./deploy-to-cpanel.sh
#    MIGRATE=1 CLEAR_CACHE=1 ./deploy-to-cpanel.sh
#

set -e
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$SCRIPT_DIR"

if [[ -f deploy-config.env ]]; then
  # shellcheck source=/dev/null
  source deploy-config.env
else
  echo "Missing deploy-config.env. Copy deploy-config.env.example and fill in your cPanel SSH details."
  exit 1
fi

if [[ -z "$SSH_USER" || -z "$SSH_HOST" || -z "$REMOTE_PATH" ]]; then
  echo "Set SSH_USER, SSH_HOST, and REMOTE_PATH in deploy-config.env"
  exit 1
fi

REMOTE_PUBLIC_LINK="${REMOTE_PUBLIC_LINK:-public_html/backend-mobile-api}"

if [[ "$SSH_HOST" == *your-server* || "$SSH_USER" == *your_cpanel* ]]; then
  echo "ERROR: deploy-config.env still has placeholder values."
  exit 1
fi

SSH_PORT="${SSH_PORT:-22}"
SSH_KEY_EXPANDED=""
if [[ -n "${SSH_KEY:-}" ]]; then
  SSH_KEY_EXPANDED="${SSH_KEY/#\~/$HOME}"
fi

SSH_CONTROL_DIR="${TMPDIR:-/tmp}/servecafe-api-deploy-$$"
SSH_CONTROL_SOCKET="${SSH_CONTROL_DIR}/control"
mkdir -p "$SSH_CONTROL_DIR"
cleanup_ssh() { ssh -S "$SSH_CONTROL_SOCKET" -O exit "${SSH_USER}@${SSH_HOST}" 2>/dev/null || true; rm -rf "$SSH_CONTROL_DIR"; }
trap cleanup_ssh EXIT

SSH_BASE=(ssh -p "${SSH_PORT}"
  -o ControlMaster=auto
  -o ControlPath="$SSH_CONTROL_SOCKET"
  -o ControlPersist=600
  -o ServerAliveInterval=30
  -o ServerAliveCountMax=6
  -o TCPKeepAlive=yes
  -o ConnectTimeout=30
)
if [[ -n "$SSH_KEY_EXPANDED" ]]; then
  SSH_BASE+=(-o IdentitiesOnly=yes -i "$SSH_KEY_EXPANDED")
else
  unset SSH_AUTH_SOCK
  SSH_BASE+=(
    -F /dev/null
    -o IdentitiesOnly=yes
    -o PubkeyAuthentication=no
    -o PreferredAuthentications=password,keyboard-interactive
  )
fi

open_ssh_master() {
  echo "Opening SSH session (enter password once for this deploy)..."
  if ! "${SSH_BASE[@]}" -o ControlMaster=yes -fN "${SSH_USER}@${SSH_HOST}"; then
    "${SSH_BASE[@]}" "${SSH_USER}@${SSH_HOST}" "echo SSH session ready"
  fi
}

SSH_RSH=$(printf '%q ' "${SSH_BASE[@]}")
SSH_RSH="${SSH_RSH% }"

REMOTE="${SSH_USER}@${SSH_HOST}:${REMOTE_PATH}/"

rsync_upload() {
  rsync -avz --partial --timeout=300 \
    -e "$SSH_RSH" \
    "$@"
}

resolve_remote_path() {
  local configured="${REMOTE_PATH}"
  local detected=""

  detected=$("${SSH_BASE[@]}" "${SSH_USER}@${SSH_HOST}" bash -s -- "$configured" <<'REMOTE_PATH_DETECT' || true
configured="$1"
for p in "$configured" "repositories/service_cafe/backend-api"; do
  [[ -z "$p" ]] && continue
  if [[ -f "$p/artisan" && -d "$p/public" ]]; then
    echo "$p"
    exit 0
  fi
done
# Allow deploy to create path on first run
if [[ -d "$(dirname "$configured")" ]]; then
  echo "$configured"
  exit 0
fi
exit 1
REMOTE_PATH_DETECT
)

  if [[ -n "$detected" ]]; then
    REMOTE_PATH="$detected"
    REMOTE="${SSH_USER}@${SSH_HOST}:${REMOTE_PATH}/"
    echo "Using REMOTE_PATH: ${REMOTE_PATH}"
    return 0
  fi

  echo "ERROR: Cannot resolve REMOTE_PATH. Set REMOTE_PATH=repositories/service_cafe/backend-api in deploy-config.env"
  return 1
}

upload_application() {
  echo "Uploading Laravel API (excluding vendor, .env, .git)..."
  "${SSH_BASE[@]}" "${SSH_USER}@${SSH_HOST}" "mkdir -p ${REMOTE_PATH}"

  rsync_upload \
    --exclude vendor \
    --exclude .env \
    --exclude .git \
    --exclude node_modules \
    --exclude storage/logs \
    --exclude .phpunit.cache \
    --delete-excluded \
    ./ "${REMOTE}"
}

run_remote_composer() {
  echo "Running composer install on server..."
  "${SSH_BASE[@]}" "${SSH_USER}@${SSH_HOST}" bash -s <<EOF
set -e
cd "${REMOTE_PATH}"
if ! command -v composer >/dev/null 2>&1; then
  echo "ERROR: composer not found on server PATH"
  exit 1
fi
composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist
EOF
}

run_remote_permissions() {
  echo "Setting permissions..."
  "${SSH_BASE[@]}" "${SSH_USER}@${SSH_HOST}" bash -s <<EOF
set -e
cd "${REMOTE_PATH}"
mkdir -p storage/framework/{cache,sessions,views} storage/logs bootstrap/cache
chmod -R 755 .
chmod -R 775 storage bootstrap/cache
EOF
}

link_public_folder() {
  echo "Linking ~/${REMOTE_PUBLIC_LINK} → ~/${REMOTE_PATH}/public"
  "${SSH_BASE[@]}" "${SSH_USER}@${SSH_HOST}" bash -s <<EOF
set -e
mkdir -p "$(dirname "${REMOTE_PUBLIC_LINK}")"
rm -rf "${REMOTE_PUBLIC_LINK}"
ln -sfn "\$HOME/${REMOTE_PATH}/public" "\$HOME/${REMOTE_PUBLIC_LINK}"
ls -la "\$HOME/${REMOTE_PUBLIC_LINK}"
EOF
}

apply_subdirectory_htaccess() {
  local base_name
  base_name="$(basename "${REMOTE_PUBLIC_LINK}")"
  echo "Applying RewriteBase /${base_name}/ for subdirectory routing..."
  "${SSH_BASE[@]}" "${SSH_USER}@${SSH_HOST}" bash -s -- "${REMOTE_PATH}" "${base_name}" <<'EOF'
set -e
remote_path="$1"
base_name="$2"
HT="$HOME/${remote_path}/public/.htaccess"
if [[ ! -f "$HT" ]]; then exit 0; fi
if grep -q "RewriteBase /${base_name}" "$HT" 2>/dev/null; then
  echo "RewriteBase already set."
  exit 0
fi
sed -i.bak "/RewriteEngine On/a RewriteBase /${base_name}/" "$HT"
echo "Added RewriteBase /${base_name}/"
EOF
}

run_remote_migrate() {
  echo "Running database migrations..."
  "${SSH_BASE[@]}" "${SSH_USER}@${SSH_HOST}" bash -s <<EOF
set -e
cd "${REMOTE_PATH}"
if [[ ! -f .env ]]; then
  echo "SKIP migrate: .env not found — create .env on server first (see DEPLOY.md)"
  exit 0
fi
php artisan migrate --force
echo "Migrations complete."
EOF
}

clear_remote_cache() {
  echo "Clearing Laravel cache..."
  "${SSH_BASE[@]}" "${SSH_USER}@${SSH_HOST}" bash -s <<EOF
set -e
cd "${REMOTE_PATH}"
if [[ ! -f .env ]]; then exit 0; fi
php artisan optimize:clear
php artisan config:cache
php artisan route:clear
echo "Cache cleared."
EOF
}

echo "=== Deploy mobile API to $SSH_USER@$SSH_HOST ==="

resolve_remote_path || exit 1
open_ssh_master

UPLOAD_OK=1
upload_application || UPLOAD_OK=0
run_remote_composer || UPLOAD_OK=0
run_remote_permissions || UPLOAD_OK=0
link_public_folder || UPLOAD_OK=0
apply_subdirectory_htaccess || true

if [[ "$UPLOAD_OK" != "1" ]]; then
  echo "ERROR: Deploy failed. See messages above."
  exit 1
fi

if [[ "$MIGRATE" == "1" || "$MIGRATE" == "true" ]]; then
  run_remote_migrate
fi

if [[ "$CLEAR_CACHE" == "1" || "$CLEAR_CACHE" == "true" ]]; then
  clear_remote_cache
fi

echo ""
echo "=== Deploy finished ==="
echo "API base URL (after .env is configured):"
echo "  https://${SSH_HOST}/backend-mobile-api/api"
echo ""
echo "Next steps on server:"
echo "  1. ssh ${SSH_USER}@${SSH_HOST}"
echo "  2. cd ~/${REMOTE_PATH} && cp .env.example .env  (if first time)"
echo "  3. Edit .env — DB_*, APP_KEY, APP_URL (see DEPLOY.md)"
echo "  4. php artisan key:generate && php artisan migrate --force"
echo ""
echo "Mobile app: set ApiConfig.productionApiBaseUrl to:"
echo "  https://${SSH_HOST}/backend-mobile-api/api"
