#!/usr/bin/env bash
# One-command deploy: push backend-api to cPanel and link public_html/backend-mobile-api
# Usage: ./deploy.sh
# Full: MIGRATE=1 CLEAR_CACHE=1 ./deploy.sh
set -e
cd "$(dirname "$0")"
export MIGRATE="${MIGRATE:-0}"
export CLEAR_CACHE="${CLEAR_CACHE:-1}"
exec ./deploy-to-cpanel.sh
