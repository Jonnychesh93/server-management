<?php

namespace App\Services\Provisioning;

use App\Models\Daemon;

class SupervisorDaemonConfig
{
    public static function render(Daemon $daemon): string
    {
        $template = <<<'CONF'
            [program:__SLUG__]
            command=__COMMAND__
            directory=__DIRECTORY__
            user=__USER__
            numprocs=__PROCESSES__
            autostart=true
            autorestart=true
            stopasgroup=true
            killasgroup=true
            redirect_stderr=true
            stdout_logfile=__DIRECTORY__/__SLUG__.log
            process_name=%(program_name)s_%(process_num)02d
            CONF;

        return strtr($template, [
            '__SLUG__' => $daemon->slug(),
            '__COMMAND__' => $daemon->command,
            '__DIRECTORY__' => $daemon->directory,
            '__USER__' => $daemon->user,
            '__PROCESSES__' => (string) $daemon->processes,
        ]);
    }
}
