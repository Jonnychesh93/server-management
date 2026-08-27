<?php

namespace App\Services\Provisioning\Steps;

class InstallPhp extends AptStep
{
    /**
     * The base PHP-FPM version installed at provisioning time. Sites may
     * request additional versions later, installed on demand.
     */
    public const DEFAULT_VERSION = '8.3';

    /**
     * PHP-FPM versions sites are allowed to request.
     *
     * @var array<int, string>
     */
    public const SUPPORTED_VERSIONS = ['8.1', '8.2', '8.3', '8.4'];

    public function name(): string
    {
        return 'install_php';
    }

    protected function commands(): string
    {
        return self::installScriptFor(self::DEFAULT_VERSION);
    }

    /**
     * The non-interactive apt script that installs (or no-ops if already
     * present) a given PHP-FPM version, used both for base provisioning and
     * for installing an additional version a site requests on demand.
     */
    public static function installScriptFor(string $version): string
    {
        return <<<BASH
            set -e
            export DEBIAN_FRONTEND=noninteractive
            rm -f /etc/apt/sources.list.d/*ondrej*
            UBUNTU_CODENAME=\$(. /etc/os-release && echo "\${VERSION_CODENAME}")
            case "\${UBUNTU_CODENAME}" in
                jammy|noble)
                    add-apt-repository -y ppa:ondrej/php
                    ;;
                *)
                    curl -sSLo /etc/apt/trusted.gpg.d/php.gpg https://packages.sury.org/php/apt.gpg
                    echo "deb https://packages.sury.org/php/ \${UBUNTU_CODENAME} main" > /etc/apt/sources.list.d/php.list
                    ;;
            esac
            apt-get update -y
            apt-get install -y php{$version}-fpm php{$version}-cli php{$version}-common \\
                php{$version}-mysql php{$version}-pgsql php{$version}-sqlite3 php{$version}-xml php{$version}-curl \\
                php{$version}-mbstring php{$version}-zip php{$version}-bcmath php{$version}-gd \\
                php{$version}-redis
            systemctl enable php{$version}-fpm
            systemctl start php{$version}-fpm
            BASH;
    }
}
