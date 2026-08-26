#!/usr/bin/env bash
#
# Re-deploy the Anchor control plane on this box after pushing changes.
# Normally invoked as: sudo anchor-deploy   (or just: deploy)
#
# This is the one-command counterpart to bootstrap-control-plane.sh —
# use that once to set the box up, use this every time after.
#
# Builds a brand new releases/<timestamp> folder from scratch (fresh
# clone, composer install, npm build, migrate) and only swaps the
# `current` symlink onto it once it's fully ready. The live app keeps
# serving the previous release throughout, so there's no window where
# requests can hit a half-installed vendor/ or a stale asset build —
# unlike deploying in place, which caused exactly that as a real,
# observed problem (the site going down while "Discovering packages"
# ran mid-composer-install).
#
# This script is installed as a standalone copy at /usr/local/bin/
# anchor-deploy — not a wrapper into a release — because releases get
# pruned and the installed command can't depend on any one of them
# surviving. It refreshes its own installed copy from the release it
# just built, so changes made here take effect from the next deploy.

set -euo pipefail

if [[ "${EUID}" -ne 0 ]]; then
    echo "Please run this script as root (sudo anchor-deploy)." >&2
    exit 1
fi

BASE_PATH=/var/www/anchor
REPO_URL=git@github.com:Jonnychesh93/server-management.git
DEPLOY_KEY_PATH=/root/.ssh/anchor_deploy_key
KEEP_RELEASES=5

RELEASE_PATH="${BASE_PATH}/releases/$(date +%Y%m%d%H%M%S)"

echo "==> Cloning into new release: ${RELEASE_PATH}"
git config --global --add safe.directory "${RELEASE_PATH}"
if [[ -f "${DEPLOY_KEY_PATH}" ]]; then
    GIT_SSH_COMMAND="ssh -i ${DEPLOY_KEY_PATH} -o StrictHostKeyChecking=accept-new" \
        git clone --depth 1 "${REPO_URL}" "${RELEASE_PATH}"
else
    git clone --depth 1 "${REPO_URL}" "${RELEASE_PATH}"
fi

echo "==> Linking shared resources"
ln -sfn "${BASE_PATH}/shared/.env" "${RELEASE_PATH}/.env"
rm -rf "${RELEASE_PATH}/storage"
ln -sfn "${BASE_PATH}/shared/storage" "${RELEASE_PATH}/storage"

echo "==> Installing dependencies"
cd "${RELEASE_PATH}"
composer install --no-dev --optimize-autoloader --no-interaction
npm ci
npm run build

echo "==> Fixing ownership"
chown -R www-data:www-data "${RELEASE_PATH}"
chmod -R ug+rwx "${RELEASE_PATH}/bootstrap/cache"

echo "==> Migrating + re-caching"
sudo -u www-data php artisan migrate --force
sudo -u www-data php artisan config:cache
sudo -u www-data php artisan route:cache
sudo -u www-data php artisan view:cache

echo "==> Switching to new release"
ln -sfn "${RELEASE_PATH}" "${BASE_PATH}/current"

echo "==> Restarting workers"
supervisorctl restart anchor-horizon:* anchor-reverb:* || true
systemctl reload php8.3-fpm

echo "==> Pruning old releases (keeping the last ${KEEP_RELEASES})"
cd "${BASE_PATH}/releases"
# shellcheck disable=SC2012
ls -1t | tail -n "+$((KEEP_RELEASES + 1))" | xargs -r -I{} rm -rf -- {}

echo "==> Refreshing the installed deploy command from this release"
cp "${RELEASE_PATH}/deploy/redeploy-control-plane.sh" /usr/local/bin/anchor-deploy
chmod +x /usr/local/bin/anchor-deploy

echo "==> Done — now live at ${RELEASE_PATH}"
