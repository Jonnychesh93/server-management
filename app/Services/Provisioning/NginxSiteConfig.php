<?php

namespace App\Services\Provisioning;

use App\Models\Site;

/**
 * Renders the Nginx server block for a site, based on Laravel's recommended
 * Nginx configuration (https://laravel.com/docs/deployment#nginx).
 */
class NginxSiteConfig
{
    public static function render(Site $site): string
    {
        $template = <<<'NGINX'
            server {
                listen 80;
                listen [::]:80;
                server_name __DOMAIN__;
                root __ROOT__;

                add_header X-Frame-Options "SAMEORIGIN";
                add_header X-Content-Type-Options "nosniff";

                index index.php index.html;

                charset utf-8;

                location / {
                    try_files $uri $uri/ /index.php?$query_string;
                }

                location = /favicon.ico { access_log off; log_not_found off; }
                location = /robots.txt  { access_log off; log_not_found off; }

                error_page 404 /index.php;

                location ~ \.php$ {
                    fastcgi_pass unix:/run/php/php__PHP_VERSION__-fpm.sock;
                    fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
                    include fastcgi_params;
                    fastcgi_hide_header X-Powered-By;
                }

                location ~ /\.(?!well-known).* {
                    deny all;
                }
            }
            NGINX;

        return strtr($template, [
            '__DOMAIN__' => $site->domain,
            '__ROOT__' => $site->remotePath().'/current'.$site->document_root,
            '__PHP_VERSION__' => $site->php_version,
        ]);
    }
}
