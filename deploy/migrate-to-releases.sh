#!/usr/bin/env bash
#
# One-time migration for a control-plane box that was set up with an
# older version of bootstrap-control-plane.sh, which deployed straight
# into BASE_PATH instead of using a releases/+current layout. Run this
# ONCE to adopt the zero-downtime layout that anchor-deploy now expects.
#
# Causes a brief interruption while it runs (a few seconds, moving
# files around) — after this, every future `deploy` builds a whole new
# release and only swaps over once it's fully ready, so there's no more
# downtime window during composer/npm.
#
# Copy this file somewhere OUTSIDE the app directory before running it
# (e.g. /root/migrate-to-releases.sh) — it moves that directory while
# running, and a script can't safely move itself out from under its own
# execution.
#
# Run as: sudo bash migrate-to-releases.sh

set -euo pipefail

if [[ "${EUID}" -ne 0 ]]; then
    echo "Please run this script as root." >&2
    exit 1
fi

read -rp "Base path currently holding the app [/var/www/anchor]: " BASE_PATH
BASE_PATH=${BASE_PATH:-/var/www/anchor}

if [[ -L "${BASE_PATH}/current" ]]; then
    echo "${BASE_PATH}/current already exists — this box looks already migrated." >&2
    exit 1
fi

if [[ ! -f "${BASE_PATH}/artisan" ]]; then
    echo "${BASE_PATH}/artisan not found — is ${BASE_PATH} really the flat app checkout?" >&2
    exit 1
fi

TS=$(date +%Y%m%d%H%M%S)
STAGING="$(dirname "${BASE_PATH}")/.anchor-migrate-${TS}"

echo "==> Moving ${BASE_PATH} aside"
mv "${BASE_PATH}" "${STAGING}"

echo "==> Rebuilding as releases/${TS}"
mkdir -p "${BASE_PATH}/releases" "${BASE_PATH}/shared"
mv "${STAGING}" "${BASE_PATH}/releases/${TS}"
RELEASE_PATH="${BASE_PATH}/releases/${TS}"

echo "==> Extracting .env and storage into shared/"
mv "${RELEASE_PATH}/.env" "${BASE_PATH}/shared/.env"
ln -sfn "${BASE_PATH}/shared/.env" "${RELEASE_PATH}/.env"

mv "${RELEASE_PATH}/storage" "${BASE_PATH}/shared/storage"
ln -sfn "${BASE_PATH}/shared/storage" "${RELEASE_PATH}/storage"

ln -sfn "${RELEASE_PATH}" "${BASE_PATH}/current"

chown -R www-data:www-data "${BASE_PATH}"

echo "==> Updating nginx"
grep -rl "root ${BASE_PATH}/public;" /etc/nginx/sites-available 2>/dev/null | while read -r f; do
    sed -i "s#root ${BASE_PATH}/public;#root ${BASE_PATH}/current/public;#" "$f"
done
nginx -t
systemctl reload nginx

echo "==> Updating Supervisor"
sed -i "s#${BASE_PATH}/artisan#${BASE_PATH}/current/artisan#g; s#${BASE_PATH}/storage/logs#${BASE_PATH}/shared/storage/logs#g" \
    /etc/supervisor/conf.d/anchor-horizon.conf /etc/supervisor/conf.d/anchor-reverb.conf
supervisorctl reread
supervisorctl update
supervisorctl restart anchor-horizon:* anchor-reverb:* || true

echo "==> Updating cron"
CRON_LINE="* * * * * www-data cd ${BASE_PATH}/current && php artisan schedule:run >> /dev/null 2>&1"
echo "${CRON_LINE}" > /etc/cron.d/anchor-scheduler
chmod 644 /etc/cron.d/anchor-scheduler

echo "==> Refreshing the installed deploy command"
cp "${RELEASE_PATH}/deploy/redeploy-control-plane.sh" /usr/local/bin/anchor-deploy
chmod +x /usr/local/bin/anchor-deploy

systemctl reload php8.3-fpm

echo
echo "Migration complete: ${BASE_PATH}/current -> ${RELEASE_PATH}"
echo "From now on, just run: deploy"
