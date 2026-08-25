<?php

use App\Enums\TeamRole;
use App\Jobs\RemoveCronJob;
use App\Jobs\RemoveDaemonJob;
use App\Jobs\SyncCronJob;
use App\Jobs\SyncDaemonJob;
use App\Models\Cron;
use App\Models\Daemon;
use App\Models\Server;
use Illuminate\Support\Facades\Bus;

test('an owner can add a daemon to a server', function () {
    Bus::fake();

    [$team, $owner] = teamWithMember(TeamRole::Owner);
    $server = Server::factory()->for($team)->active()->create();

    $this->actingAs($owner)->post(route('daemons.store', $server), [
        'command' => 'php artisan queue:work',
        'directory' => '/home/appuser',
        'user' => 'appuser',
        'processes' => 2,
    ])->assertRedirect();

    $daemon = Daemon::where('server_id', $server->id)->first();
    expect($daemon)->not->toBeNull();
    expect($daemon->processes)->toBe(2);

    Bus::assertDispatched(SyncDaemonJob::class, fn ($job) => $job->daemon->is($daemon));
});

test('a member cannot add a daemon to a server', function () {
    [$team, $member] = teamWithMember(TeamRole::Member);
    $server = Server::factory()->for($team)->active()->create();

    $this->actingAs($member)->post(route('daemons.store', $server), [
        'command' => 'php artisan queue:work',
        'directory' => '/home/appuser',
        'user' => 'appuser',
        'processes' => 1,
    ])->assertForbidden();
});

test('an owner can remove a daemon', function () {
    Bus::fake();

    [$team, $owner] = teamWithMember(TeamRole::Owner);
    $server = Server::factory()->for($team)->active()->create();
    $daemon = Daemon::factory()->for($team)->for($server)->create();

    $this->actingAs($owner)->delete(route('daemons.destroy', $daemon))
        ->assertRedirect();

    expect(Daemon::find($daemon->id))->toBeNull();
    Bus::assertDispatched(RemoveDaemonJob::class);
});

test('an owner can add a cron job to a server', function () {
    Bus::fake();

    [$team, $owner] = teamWithMember(TeamRole::Owner);
    $server = Server::factory()->for($team)->active()->create();

    $this->actingAs($owner)->post(route('crons.store', $server), [
        'command' => 'php artisan schedule:run',
        'user' => 'appuser',
        'schedule' => '* * * * *',
    ])->assertRedirect();

    $cron = Cron::where('server_id', $server->id)->first();
    expect($cron)->not->toBeNull();

    Bus::assertDispatched(SyncCronJob::class, fn ($job) => $job->cron->is($cron));
});

test('an invalid cron schedule is rejected', function () {
    [$team, $owner] = teamWithMember(TeamRole::Owner);
    $server = Server::factory()->for($team)->active()->create();

    $this->actingAs($owner)->post(route('crons.store', $server), [
        'command' => 'php artisan schedule:run',
        'user' => 'appuser',
        'schedule' => 'not a schedule',
    ])->assertInvalid(['schedule']);
});

test('an owner can remove a cron job', function () {
    Bus::fake();

    [$team, $owner] = teamWithMember(TeamRole::Owner);
    $server = Server::factory()->for($team)->active()->create();
    $cron = Cron::factory()->for($team)->for($server)->create();

    $this->actingAs($owner)->delete(route('crons.destroy', $cron))
        ->assertRedirect();

    expect(Cron::find($cron->id))->toBeNull();
    Bus::assertDispatched(RemoveCronJob::class);
});
