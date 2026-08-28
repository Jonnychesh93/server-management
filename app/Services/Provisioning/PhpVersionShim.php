<?php

namespace App\Services\Provisioning;

/**
 * A bare "php"/"composer" invocation resolves to whichever version
 * update-alternatives currently treats as the system-wide default — not
 * necessarily the version a specific site is configured for, which matters
 * the moment a server hosts sites on different PHP versions. Shadows "php"
 * with a shim pointing at the right binary, prepended ahead of everything
 * else on PATH, so both direct "php ..." calls and composer (which itself
 * invokes "env php") resolve consistently to it.
 */
class PhpVersionShim
{
    public static function script(string $releasePath, string $version): string
    {
        return <<<BASH
            mkdir -p {$releasePath}/.bin
            ln -sf /usr/bin/php{$version} {$releasePath}/.bin/php
            export PATH="{$releasePath}/.bin:/usr/local/bin:\$PATH"
            BASH;
    }
}
