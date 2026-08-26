#!/usr/bin/env bash
#
# Re-deploy the Anchor control plane on this box after pushing changes.
# Run with: sudo bash deploy/redeploy-control-plane.sh
#
# This is the one-command counterpart to bootstrap-control-plane.sh —
# use that once to set the box up, use this every time after.
#
# Pulls the latest code, rebuilds dependencies/assets, migrates,
# re-caches config/routes/views, and restarts Horizon/Reverb/PHP-FPM
# so the new code actually takes effect.

set -euo pipefail

if [[ "${EUID}" -ne 0 ]]; then
    echo "Please run this script as root (sudo bash deploy/redeploy-control-plane.sh)." >&2
    exit 1
fi

DEPLOY_PATH="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
DEPLOY_KEY_PATH=/root/.ssh/anchor_deploy_key

cd "${DEPLOY_PATH}"

# Files here are owned by www-data (set at the end of every deploy), but
# this script runs as root — git refuses to touch a repo it doesn't own
# unless explicitly told it's safe to.
git config --global --add safe.directory "${DEPLOY_PATH}"

echo "==> Pulling latest code"
if [[ -f "${DEPLOY_KEY_PATH}" ]]; then
    GIT_SSH_COMMAND="ssh -i ${DEPLOY_KEY_PATH} -o StrictHostKeyChecking=accept-new" git pull
else
    git pull
fi

echo "==> Installing dependencies"
composer install --no-dev --optimize-autoloader --no-interaction
npm ci
npm run build

echo "==> Fixing ownership"
chown -R www-data:www-data "${DEPLOY_PATH}"

echo "==> Migrating + re-caching"
sudo -u www-data php artisan migrate --force
sudo -u www-data php artisan config:cache
sudo -u www-data php artisan route:cache
sudo -u www-data php artisan view:cache

echo "==> Restarting workers"
supervisorctl restart anchor-horizon:* anchor-reverb:* || true
systemctl reload php8.3-fpm

echo "==> Done"
