#!/usr/bin/env bash
#
# Deploy mobile API (backend-api) to cPanel via SSH.
# 1. cp deploy-config.env.example deploy-config.env
# 2. npm run deploy   or   ./deploy.sh
#    npm run deploy:migrate   (after .env exists on server)
#

set -e
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$SCRIPT_DIR"

# macOS: stop tar/rsync from uploading AppleDouble "._*" junk and .DS_Store
export COPYFILE_DISABLE=1
export COPY_EXTENDED_ATTRIBUTES_DISABLE=1

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

# Canonical server folder (cPanel: repositories → backend-api). See docs/SERVER_PATHS.md
CANONICAL_API_PATH="repositories/backend-api"

# Never deploy inside service_cafe/
if [[ "$REMOTE_PATH" == *service_cafe* ]]; then
  echo "ERROR: API must not deploy under service_cafe/."
  echo "  Current:  ${REMOTE_PATH}"
  echo "  Required: ${CANONICAL_API_PATH}"
  echo "  File Manager: repositories → backend-api (sibling of service_cafe)"
  exit 1
fi

# Normalize relative path
REMOTE_PATH="${REMOTE_PATH#./}"
REMOTE_PATH="${REMOTE_PATH%/}"

if [[ "$REMOTE_PATH" != "$CANONICAL_API_PATH" && "$REMOTE_PATH" != *"/${CANONICAL_API_PATH}" ]]; then
  echo "ERROR: REMOTE_PATH must be ${CANONICAL_API_PATH}"
  echo "  Current: ${REMOTE_PATH}"
  echo "  Edit deploy-config.env — see docs/SERVER_PATHS.md"
  exit 1
fi

# Always deploy to ~/repositories/backend-api (not web-app, not public_html)
if [[ "$REMOTE_PATH" == */repositories/backend-api ]]; then
  REMOTE_PATH="repositories/backend-api"
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

# cPanel SSH often has no `composer` in non-interactive PATH — install locally and upload vendor.
LOCAL_VENDOR="${LOCAL_VENDOR:-1}"

ensure_local_vendor() {
  if [[ "$LOCAL_VENDOR" != "1" && "$LOCAL_VENDOR" != "true" ]]; then
    return 0
  fi
  if [[ -f vendor/autoload.php ]]; then
    echo "Local vendor/ present — will upload with app."
    return 0
  fi
  echo "Running composer install locally (for upload to server)..."
  if ! command -v composer >/dev/null 2>&1; then
    echo "ERROR: composer not found locally. Install Composer, or set LOCAL_VENDOR=0 and COMPOSER_BIN on server in deploy-config.env"
    return 1
  fi
  # Match cPanel PHP 8.2 — do not install Symfony 8.x / PHP 8.4-only deps from a Mac on PHP 8.4
  composer config platform.php 8.2.29
  composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist
}

# Shared excludes for macOS metadata junk (._* AppleDouble, .DS_Store)
DEPLOY_RSYNC_EXCLUDES=(
  --exclude .env
  --exclude deploy-config.env
  --exclude .git
  --exclude node_modules
  --exclude storage/logs
  --exclude .phpunit.cache
  --exclude '.DS_Store'
  --exclude '._*'
)

upload_application_via_tar() {
  echo "Uploading Laravel API via tar (fallback when rsync fails)..."
  local excludes=(
    --exclude=.git
    --exclude=.env
    --exclude=node_modules
    --exclude=storage/logs
    --exclude=.phpunit.cache
    --exclude=deploy-config.env
    --exclude=.DS_Store
    --exclude='._*'
  )
  if [[ "$LOCAL_VENDOR" != "1" && "$LOCAL_VENDOR" != "true" ]]; then
    excludes+=(--exclude=vendor)
  fi
  # COPYFILE_DISABLE prevents macOS tar from packing ._ files; 2>/dev/null hides xattr noise
  tar czf - "${excludes[@]}" . 2>/dev/null | "${SSH_BASE[@]}" "${SSH_USER}@${SSH_HOST}" \
    "mkdir -p ${REMOTE_PATH} && tar xzf - -C ${REMOTE_PATH} 2>/dev/null"
  echo "Done application (tar)."
}

cleanup_remote_mac_junk() {
  echo "Removing macOS metadata files (._* and .DS_Store) on server..."
  "${SSH_BASE[@]}" "${SSH_USER}@${SSH_HOST}" bash -s -- "${REMOTE_PATH}" <<'EOF'
set -e
cd "$1"
count=0
while IFS= read -r -d '' f; do
  rm -f "$f"
  count=$((count + 1))
done < <(find . -name '._*' -print0 2>/dev/null)
while IFS= read -r -d '' f; do
  rm -f "$f"
  count=$((count + 1))
done < <(find . -name '.DS_Store' -print0 2>/dev/null)
echo "Removed ${count} junk file(s)."
EOF
}

resolve_remote_path() {
  local configured="${REMOTE_PATH}"
  local detected=""

  # Only use configured path — do not fall back to old service_cafe/backend-api location
  detected=$("${SSH_BASE[@]}" "${SSH_USER}@${SSH_HOST}" bash -s -- "$configured" <<'REMOTE_PATH_DETECT' || true
configured="$1"
if [[ -f "$configured/artisan" && -d "$configured/public" ]]; then
  echo "$configured"
  exit 0
fi
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

  echo "ERROR: Cannot resolve ~/${configured}. Create it in cPanel or set REMOTE_PATH=repositories/backend-api in deploy-config.env"
  return 1
}

upload_application() {
  echo "Uploading Laravel API (.env and .git excluded)..."
  "${SSH_BASE[@]}" "${SSH_USER}@${SSH_HOST}" "mkdir -p ${REMOTE_PATH}"

  local rsync_excludes=("${DEPLOY_RSYNC_EXCLUDES[@]}")
  if [[ "$LOCAL_VENDOR" != "1" && "$LOCAL_VENDOR" != "true" ]]; then
    rsync_excludes+=(--exclude vendor)
  fi

  if rsync_upload "${rsync_excludes[@]}" --delete-excluded ./ "${REMOTE}"; then
    echo "Done application (rsync)."
    return 0
  fi
  echo ""
  echo "Rsync failed — retrying with tar upload..."
  upload_application_via_tar
}

run_remote_composer() {
  if [[ "$LOCAL_VENDOR" == "1" || "$LOCAL_VENDOR" == "true" ]]; then
    if "${SSH_BASE[@]}" "${SSH_USER}@${SSH_HOST}" bash -s -- "${REMOTE_PATH}" <<'EOF'
set -e
cd "$1"
if [[ -f vendor/autoload.php ]]; then
  echo "vendor/ already on server (uploaded from local) — skip server composer."
  exit 0
fi
exit 2
EOF
    then
      return 0
    fi
  fi

  echo "Running composer install on server..."
  "${SSH_BASE[@]}" "${SSH_USER}@${SSH_HOST}" bash -l -s -- "${REMOTE_PATH}" "${COMPOSER_BIN:-}" <<'EOF'
set -e
cd "$1"
override="$2"
composer_cmd=""
if [[ -n "$override" && -x "$override" ]]; then
  composer_cmd="$override"
else
  for p in /usr/local/bin/composer /opt/cpanel/composer/bin/composer /usr/local/cpanel/3rdparty/bin/composer /usr/bin/composer; do
    if [[ -x "$p" ]]; then
      composer_cmd="$p"
      break
    fi
  done
  if [[ -z "$composer_cmd" ]] && command -v composer >/dev/null 2>&1; then
    composer_cmd="$(command -v composer)"
  fi
fi
if [[ -z "$composer_cmd" && -f "$HOME/composer.phar" ]]; then
  composer_cmd="php $HOME/composer.phar"
elif [[ -z "$composer_cmd" && -f composer.phar ]]; then
  composer_cmd="php composer.phar"
fi
if [[ -z "$composer_cmd" ]]; then
  echo "ERROR: composer not found on server."
  echo "Fix: run deploy with LOCAL_VENDOR=1 (default), or SSH in and install Composer, or set COMPOSER_BIN in deploy-config.env"
  exit 1
fi
export HOME="${HOME:-$HOME}"
export COMPOSER_HOME="${COMPOSER_HOME:-$HOME/.composer}"
$composer_cmd install --no-dev --optimize-autoloader --no-interaction --prefer-dist
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
  "${SSH_BASE[@]}" "${SSH_USER}@${SSH_HOST}" bash -l -s -- "${REMOTE_PATH}" "${REMOTE_PUBLIC_LINK}" <<'EOF'
set -e
remote_path="$1"
public_link="$2"
home="${HOME:-$(cd ~ && pwd)}"
api_public="${home}/${remote_path}/public"
link_path="${home}/${public_link}"

if [[ ! -f "${api_public}/index.php" ]]; then
  echo "ERROR: ${api_public}/index.php missing — upload did not complete."
  exit 1
fi
mkdir -p "${home}/public_html"
mkdir -p "$(dirname "${link_path}")"
rm -rf "${link_path}"
ln -sfn "${api_public}" "${link_path}"
ls -la "${link_path}"
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
echo "Target: ~/${REMOTE_PATH}  (File Manager: repositories → backend-api)"

resolve_remote_path || exit 1
ensure_local_vendor || exit 1
open_ssh_master

UPLOAD_OK=1
upload_application || UPLOAD_OK=0
if [[ "$UPLOAD_OK" != "1" ]]; then
  echo "ERROR: File upload failed. Fix SSH/network and re-run ./deploy.sh"
  exit 1
fi
cleanup_remote_mac_junk || true
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

verify_remote_deploy() {
  echo ""
  echo "=== Server paths (File Manager) ==="
  "${SSH_BASE[@]}" "${SSH_USER}@${SSH_HOST}" bash -l -s -- "${REMOTE_PATH}" "${REMOTE_PUBLIC_LINK}" <<'EOF'
set -e
remote_path="$1"
public_link="$2"
home="${HOME:-$(cd ~ && pwd)}"
api_root="${home}/${remote_path}"
echo "Mobile API files: ${api_root}"
if [[ -f "${api_root}/artisan" ]]; then
  echo "  OK: artisan found"
  ls -la "${api_root}" | head -20
else
  echo "  WARNING: artisan missing at ${api_root}"
  echo "  Searching for backend-api under ~/repositories..."
  find "${home}/repositories" -maxdepth 4 -type d -name backend-api 2>/dev/null || true
fi
echo ""
echo "Public URL symlink:"
ls -la "${home}/${public_link}" 2>/dev/null || echo "  (symlink not found)"
echo ""
echo "In cPanel File Manager open:"
echo "  repositories → $(basename "${api_root}")"
EOF
}

verify_remote_deploy

echo ""
echo "=== Deploy finished ==="
echo "API base URL (after .env is configured):"
echo "  https://${SSH_HOST}/backend-mobile-api/api"
echo ""
echo "cPanel File Manager: repositories → backend-api"
echo "  (sibling of service_cafe/, not inside it)"
echo ""
echo "Next steps on server:"
echo "  1. ssh ${SSH_USER}@${SSH_HOST}"
echo "  2. cd ~/${REMOTE_PATH} && cp .env.example .env  (if first time)"
echo "  3. Edit .env — DB_*, APP_KEY, APP_URL (see DEPLOY.md)"
echo "  4. php artisan key:generate && php artisan migrate --force"
echo ""
echo "Mobile app: set ApiConfig.productionApiBaseUrl to:"
echo "  https://${SSH_HOST}/backend-mobile-api/api"
