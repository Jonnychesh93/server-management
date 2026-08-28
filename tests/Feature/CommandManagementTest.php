<?php

use App\Enums\TeamRole;
use App\Jobs\RunCommandJob;
use App\Models\Command;
use App\Models\Server;
use App\Models\Site;
use Illuminate\Support\Facades\Bus;

test('an owner can run a command against a site', function () {
    Bus::fake();

    [$team, $owner] = teamWithMember(TeamRole::Owner);
    $server = Server::factory()->for($team)->active()->create();
    $site = Site::factory()->for($team)->for($server)->active()->create();

    $this->actingAs($owner)->post(route('commands.store', $site), [
        'command' => 'php artisan queue:restart',
    ])->assertRedirect();

    $command = Command::where('site_id', $site->id)->first();
    expect($command)->not->toBeNull();
    expect($command->command)->toBe('php artisan queue:restart');
    expect($command->user_id)->toBe($owner->id);

    Bus::assertDispatched(RunCommandJob::class, fn ($job) => $job->command->is($command));
});

test('a member cannot run a command against a site', function () {
    [$team, $member] = teamWithMember(TeamRole::Member);
    $server = Server::factory()->for($team)->active()->create();
    $site = Site::factory()->for($team)->for($server)->active()->create();

    $this->actingAs($member)->post(route('commands.store', $site), [
        'command' => 'php artisan queue:restart',
    ])->assertForbidden();
});

test('a command is routed to by its uuid, not its sequential id', function () {
    [$team, $owner] = teamWithMember(TeamRole::Owner);
    $server = Server::factory()->for($team)->active()->create();
    $site = Site::factory()->for($team)->for($server)->active()->create();
    $command = Command::factory()->for($team)->for($site)->create();

    $url = route('commands.show', $command);

    expect($url)->toEndWith('/commands/'.$command->uuid);
    expect($url)->not->toEndWith('/commands/'.$command->id);

    $this->actingAs($owner)->get($url)->assertOk();
    $this->actingAs($owner)->get('/commands/'.$command->id)->assertNotFound();
});

test('a user outside the team cannot run a command against a site', function () {
    [, $outsider] = teamWithMember(TeamRole::Owner);
    $site = Site::factory()->active()->create();

    $this->actingAs($outsider)->post(route('commands.store', $site), [
        'command' => 'php artisan about',
    ])->assertForbidden();
});

test('a user outside the team cannot view a command', function () {
    [, $outsider] = teamWithMember(TeamRole::Owner);
    $command = Command::factory()->create();

    $this->actingAs($outsider)->get(route('commands.show', $command))->assertForbidden();
});

test('a command requires a non-empty command string', function () {
    [$team, $owner] = teamWithMember(TeamRole::Owner);
    $server = Server::factory()->for($team)->active()->create();
    $site = Site::factory()->for($team)->for($server)->active()->create();

    $this->actingAs($owner)->post(route('commands.store', $site), [
        'command' => '',
    ])->assertInvalid(['command']);
});
