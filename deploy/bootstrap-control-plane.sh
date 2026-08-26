#!/usr/bin/env bash
#
# One-time bootstrap for a fresh Ubuntu 22.04/24.04 VPS that will host the
# Anchor control-plane app itself (this repo). Anchor can provision servers
# you add to it, but it can't provision the box it's running on — so this
# script does that part by hand, once.
#
# Run this as root (or via sudo) on the box you're dedicating to Anchor.
# Read it top to bottom before running it. It's interactive: it will ask
# a handful of questions up front, then run unattended.
#
# What it does, in order:
#   1. System packages, PHP 8.3, Composer, Node 22, MySQL, Redis, nginx,
#      Supervisor, certbot, UFW
#   2. Generates a dedicated SSH deploy key and waits for you to add it to
#      GitHub as a read-only Deploy Key on this repo
#   3. Clones the repo into a releases/<timestamp> folder, installs
#      dependencies, builds frontend assets
#   4. Writes a production .env under shared/ (DB credentials, Reverb
#      keys, app key), shared across every release
#   5. Runs migrations, caches config/routes/views, flips the `current`
#      symlink onto this release
#   6. Supervisor programs for `horizon` and `reverb:start`
#   7. nginx vhost (incl. the Reverb WebSocket proxy) + certbot SSL
#   8. UFW firewall rules + a cron entry for the scheduler
#
# Every later deploy (via the installed `deploy`/`anchor-deploy` commands)
# builds a brand new releases/<timestamp> folder the same way and only
# flips the `current` symlink once it's fully built and migrated — so the
# live app never serves a half-installed `vendor/` or `node_modules`
# build, and there's no downtime window during composer/npm.
#
# What it deliberately does NOT do (out of scope for a single-purpose box):
#   - Inertia SSR (client-side rendering only; fine for a login-gated app)
#   - Real outbound mail (MAIL_MAILER stays "log" until you configure SMTP)
#   - Multi-app / multi-tenant FPM pool separation (this box only runs Anchor,
#     so PHP-FPM and Supervisor both just run as www-data)

set -euo pipefail

if [[ "${EUID}" -ne 0 ]]; then
    echo "Please run this script as root (sudo bash bootstrap-control-plane.sh)." >&2
    exit 1
fi

# ── 1. Ask the questions up front ──────────────────────────────────────────

read -rp "Domain Anchor will be reachable at (e.g. anchor.yoursite.com): " APP_DOMAIN
read -rp "Admin email (SSL renewal notices + Horizon dashboard access): " ADMIN_EMAIL
read -rp "Git repository SSH URL [git@github.com:Jonnychesh93/server-management.git]: " REPO_URL
REPO_URL=${REPO_URL:-git@github.com:Jonnychesh93/server-management.git}
read -rp "Base path [/var/www/anchor]: " BASE_PATH
BASE_PATH=${BASE_PATH:-/var/www/anchor}
read -rp "MySQL database name [anchor]: " DB_NAME
DB_NAME=${DB_NAME:-anchor}
read -rp "MySQL app user [anchor]: " DB_USER
DB_USER=${DB_USER:-anchor}
DB_PASSWORD=$(openssl rand -hex 16)

echo
echo "Domain:       ${APP_DOMAIN}"
echo "Base path:    ${BASE_PATH}"
echo "Database:     ${DB_NAME} (user: ${DB_USER}, password generated)"
echo
read -rp "Look right? Press enter to continue, Ctrl+C to abort..." _

# ── 2. Base system + packages ──────────────────────────────────────────────

export DEBIAN_FRONTEND=noninteractive

# Remove any ondrej/php PPA entry left over from a previous run on a
# codename it doesn't publish for — a stale entry here breaks every
# apt-get update below, not just the PHP install step. Newer Ubuntu
# releases use the deb822 .sources format instead of .list, so match
# broadly rather than assuming one extension.
rm -f /etc/apt/sources.list.d/*ondrej*

apt-get update
apt-get upgrade -y
apt-get install -y software-properties-common curl gnupg2 ca-certificates lsb-release unzip git ufw

# The ondrej/php PPA only tracks Ubuntu LTS codenames (jammy 22.04,
# noble 24.04) plus the current one still in transition. Anything newer
# gets its packages from packages.sury.org instead — same maintainer,
# just a different distribution channel. Detect which one to use.
UBUNTU_CODENAME=$(. /etc/os-release && echo "${VERSION_CODENAME}")
case "${UBUNTU_CODENAME}" in
    jammy|noble)
        add-apt-repository -y ppa:ondrej/php
        ;;
    *)
        curl -sSLo /etc/apt/trusted.gpg.d/php.gpg https://packages.sury.org/php/apt.gpg
        echo "deb https://packages.sury.org/php/ ${UBUNTU_CODENAME} main" > /etc/apt/sources.list.d/php.list
        ;;
esac
apt-get update
apt-get install -y \
    php8.3-fpm php8.3-cli php8.3-common php8.3-mysql php8.3-xml \
    php8.3-curl php8.3-mbstring php8.3-zip php8.3-bcmath php8.3-gd \
    php8.3-redis php8.3-intl

curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

curl -fsSL https://deb.nodesource.com/setup_22.x | bash -
apt-get install -y nodejs

apt-get install -y mysql-server redis-server nginx supervisor certbot python3-certbot-nginx

systemctl enable --now php8.3-fpm mysql redis-server nginx supervisor

# ── 3. Database ─────────────────────────────────────────────────────────────

mysql --execute="
CREATE DATABASE IF NOT EXISTS \`${DB_NAME}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS '${DB_USER}'@'127.0.0.1' IDENTIFIED BY '${DB_PASSWORD}';
GRANT ALL PRIVILEGES ON \`${DB_NAME}\`.* TO '${DB_USER}'@'127.0.0.1';
FLUSH PRIVILEGES;
"

# ── 4. Deploy key for cloning the repo ──────────────────────────────────────

mkdir -p /root/.ssh
DEPLOY_KEY_PATH=/root/.ssh/anchor_deploy_key
if [[ ! -f "${DEPLOY_KEY_PATH}" ]]; then
    ssh-keygen -t ed25519 -f "${DEPLOY_KEY_PATH}" -N "" -C "anchor-control-plane"
fi
ssh-keyscan -H github.com >> /root/.ssh/known_hosts 2>/dev/null

echo
echo "Add this deploy key to the GitHub repo (Settings > Deploy keys, read-only is enough):"
echo "----------------------------------------------------------------------"
cat "${DEPLOY_KEY_PATH}.pub"
echo "----------------------------------------------------------------------"
read -rp "Press enter once the deploy key has been added..." _

# ── 5. First release ─────────────────────────────────────────────────────

RELEASE_PATH="${BASE_PATH}/releases/$(date +%Y%m%d%H%M%S)"
mkdir -p "${BASE_PATH}/releases" "${BASE_PATH}/shared"

git config --global --add safe.directory "${RELEASE_PATH}"
GIT_SSH_COMMAND="ssh -i ${DEPLOY_KEY_PATH} -o StrictHostKeyChecking=accept-new" \
    git clone "${REPO_URL}" "${RELEASE_PATH}"

# ── 6. .env (shared across every release) ───────────────────────────────

if [[ ! -f "${BASE_PATH}/shared/.env" ]]; then
    cp "${RELEASE_PATH}/.env.example" "${BASE_PATH}/shared/.env"
fi
ln -sfn "${BASE_PATH}/shared/.env" "${RELEASE_PATH}/.env"

mkdir -p "${BASE_PATH}/shared/storage"
if [[ -d "${RELEASE_PATH}/storage" && ! -L "${RELEASE_PATH}/storage" ]]; then
    cp -rn "${RELEASE_PATH}/storage/." "${BASE_PATH}/shared/storage/"
    rm -rf "${RELEASE_PATH}/storage"
fi
ln -sfn "${BASE_PATH}/shared/storage" "${RELEASE_PATH}/storage"

REVERB_APP_ID=$(openssl rand -hex 10)
REVERB_APP_KEY=$(openssl rand -hex 20)
REVERB_APP_SECRET=$(openssl rand -hex 20)

set_env() {
    local key="$1" value="$2"
    if grep -q "^${key}=" "${BASE_PATH}/shared/.env"; then
        sed -i "s#^${key}=.*#${key}=${value}#" "${BASE_PATH}/shared/.env"
    else
        echo "${key}=${value}" >> "${BASE_PATH}/shared/.env"
    fi
}

set_env "APP_ENV" "production"
set_env "APP_DEBUG" "false"
set_env "APP_URL" "https://${APP_DOMAIN}"
set_env "DB_CONNECTION" "mysql"
set_env "DB_HOST" "127.0.0.1"
set_env "DB_PORT" "3306"
set_env "DB_DATABASE" "${DB_NAME}"
set_env "DB_USERNAME" "${DB_USER}"
set_env "DB_PASSWORD" "${DB_PASSWORD}"
set_env "HORIZON_ADMIN_EMAILS" "${ADMIN_EMAIL}"
set_env "REVERB_APP_ID" "${REVERB_APP_ID}"
set_env "REVERB_APP_KEY" "${REVERB_APP_KEY}"
set_env "REVERB_APP_SECRET" "${REVERB_APP_SECRET}"
set_env "REVERB_HOST" "127.0.0.1"
set_env "REVERB_PORT" "8080"
set_env "REVERB_SCHEME" "http"
set_env "VITE_REVERB_HOST" "${APP_DOMAIN}"
set_env "VITE_REVERB_PORT" "443"
set_env "VITE_REVERB_SCHEME" "https"

# ── 7. Build the first release ───────────────────────────────────────────

cd "${RELEASE_PATH}"

composer install --no-dev --optimize-autoloader --no-interaction
npm ci
npm run build

php artisan key:generate --force

chown -R www-data:www-data "${BASE_PATH}"
chmod -R ug+rwx "${BASE_PATH}/shared/storage" "${RELEASE_PATH}/bootstrap/cache"

sudo -u www-data php artisan migrate --force
sudo -u www-data php artisan storage:link
sudo -u www-data php artisan config:cache
sudo -u www-data php artisan route:cache
sudo -u www-data php artisan view:cache

ln -sfn "${RELEASE_PATH}" "${BASE_PATH}/current"

# ── 8. Supervisor: Horizon + Reverb ─────────────────────────────────────────

cat > /etc/supervisor/conf.d/anchor-horizon.conf <<EOF
[program:anchor-horizon]
process_name=%(program_name)s
command=php ${BASE_PATH}/current/artisan horizon
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=${BASE_PATH}/shared/storage/logs/horizon.log
stopwaitsecs=3600
EOF

cat > /etc/supervisor/conf.d/anchor-reverb.conf <<EOF
[program:anchor-reverb]
process_name=%(program_name)s
command=php ${BASE_PATH}/current/artisan reverb:start
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=${BASE_PATH}/shared/storage/logs/reverb.log
minfds=10000
EOF

supervisorctl reread
supervisorctl update
supervisorctl start anchor-horizon:* anchor-reverb:* || true

# ── 9. nginx ─────────────────────────────────────────────────────────────

cat > "/etc/nginx/sites-available/${APP_DOMAIN}" <<EOF
server {
    listen 80;
    listen [::]:80;
    server_name ${APP_DOMAIN};
    root ${BASE_PATH}/current/public;

    index index.php;
    charset utf-8;

    client_max_body_size 20M;

    location /app {
        proxy_http_version 1.1;
        proxy_set_header Host \$http_host;
        proxy_set_header Scheme \$scheme;
        proxy_set_header SERVER_PORT \$server_port;
        proxy_set_header REMOTE_ADDR \$remote_addr;
        proxy_set_header X-Forwarded-For \$proxy_add_x_forwarded_for;
        proxy_set_header Upgrade \$http_upgrade;
        proxy_set_header Connection "Upgrade";
        proxy_pass http://127.0.0.1:8080;
    }

    location /apps {
        proxy_http_version 1.1;
        proxy_set_header Host \$http_host;
        proxy_pass http://127.0.0.1:8080;
    }

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location ~ \.php\$ {
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;
        include fastcgi_params;
        # Vite's module-preload Link headers plus encrypted session/XSRF
        # cookies exceed nginx's default FastCGI buffer, causing
        # "upstream sent too big header" on every page load.
        fastcgi_buffers 16 16k;
        fastcgi_buffer_size 32k;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
EOF

ln -sf "/etc/nginx/sites-available/${APP_DOMAIN}" "/etc/nginx/sites-enabled/${APP_DOMAIN}"
rm -f /etc/nginx/sites-enabled/default
nginx -t
systemctl reload nginx

SSL_ISSUED=true
if ! certbot --nginx -d "${APP_DOMAIN}" --non-interactive --agree-tos -m "${ADMIN_EMAIL}" --redirect; then
    SSL_ISSUED=false
    echo
    echo "Certbot failed — usually because ${APP_DOMAIN} doesn't point at this"
    echo "server's IP yet. Continuing without SSL for now; once DNS resolves,"
    echo "re-run: certbot --nginx -d ${APP_DOMAIN} --non-interactive --agree-tos -m ${ADMIN_EMAIL} --redirect"
    echo
fi

# ── 10. Firewall ──────────────────────────────────────────────────────────

ufw allow OpenSSH
ufw allow "Nginx Full"
ufw --force enable

# ── 11. Scheduler cron ───────────────────────────────────────────────────

CRON_LINE="* * * * * www-data cd ${BASE_PATH}/current && php artisan schedule:run >> /dev/null 2>&1"
echo "${CRON_LINE}" > /etc/cron.d/anchor-scheduler
chmod 644 /etc/cron.d/anchor-scheduler

# ── 12. Redeploy shortcut ────────────────────────────────────────────────

# The canonical deploy script is installed as a standalone copy, not a
# wrapper pointing back into a release — releases get pruned, so the
# installed command can't depend on any specific one surviving. Every
# successful deploy refreshes this copy from the release it just built,
# so improvements committed to the repo take effect from the next deploy.
cp "${RELEASE_PATH}/deploy/redeploy-control-plane.sh" /usr/local/bin/anchor-deploy
chmod +x /usr/local/bin/anchor-deploy

# A plain PATH command rather than a shell alias, so it works from any
# shell or session (browser-based consoles included) without needing
# .bashrc/.zshrc to be sourced first.
cat > /usr/local/bin/deploy <<'EOF'
#!/usr/bin/env bash
exec sudo anchor-deploy "$@"
EOF
chmod +x /usr/local/bin/deploy

echo
echo "========================================================================"
if [[ "${SSL_ISSUED}" == "true" ]]; then
    echo "Anchor is live at: https://${APP_DOMAIN}"
else
    echo "Anchor is set up but has no SSL certificate yet — point DNS at this"
    echo "server's IP, then re-run the certbot command printed above."
    echo "Until then it's reachable at: http://${APP_DOMAIN}"
fi
echo
echo "Database password (save this somewhere safe): ${DB_PASSWORD}"
echo
echo "Still to do manually:"
echo "  - Public registration is disabled by default. Create your account with:"
echo "      sudo -u www-data php artisan tinker --execute='app(App\Actions\Fortify\CreateNewUser::class)->create([\"name\" => \"Your Name\", \"email\" => \"you@example.com\", \"password\" => \"change-me\", \"password_confirmation\" => \"change-me\"]);'"
echo "  - Real outbound email isn't configured (MAIL_MAILER=log) — team"
echo "    invites will only appear in storage/logs/laravel.log until you"
echo "    set real SMTP credentials in ${BASE_PATH}/shared/.env and run 'deploy'"
echo "    (or artisan config:cache directly) to pick them up"
echo "  - GitHub App integration (GITHUB_APP_*) is optional and still unset"
echo
echo "To deploy future changes, just run: deploy"
echo "========================================================================"
