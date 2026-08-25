<?php

use App\Enums\ServerProvisioningStatus;
use App\Jobs\HeartbeatServerJob;
use App\Models\Server;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::call(function () {
    Server::query()
        ->where('provisioning_status', ServerProvisioningStatus::Active)
        ->each(fn (Server $server) => HeartbeatServerJob::dispatch($server));
})->everyFiveMinutes()->name('server-heartbeats')->withoutOverlapping();
