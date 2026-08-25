<?php

use App\Enums\TeamRole;
use App\Jobs\IssueSslCertificateJob;
use App\Jobs\SyncSiteEnvironmentJob;
use App\Models\Server;
use App\Models\Site;
use Illuminate\Support\Facades\Bus;

test('an owner can update a site environment file', function () {
    Bus::fake();

    [$team, $owner] = teamWithMember(TeamRole::Owner);
    $server = Server::factory()->for($team)->active()->create();
    $site = Site::factory()->for($team)->for($server)->active()->create();

    $this->actingAs($owner)->put(route('sites.environment.update', $site), [
        'env' => 'APP_NAME=Example',
    ])->assertRedirect();

    expect($site->refresh()->env_encrypted)->toBe('APP_NAME=Example');
    Bus::assertDispatched(SyncSiteEnvironmentJob::class);
});

test('a member cannot update a site environment file', function () {
    [$team, $member] = teamWithMember(TeamRole::Member);
    $server = Server::factory()->for($team)->active()->create();
    $site = Site::factory()->for($team)->for($server)->active()->create();

    $this->actingAs($member)->put(route('sites.environment.update', $site), [
        'env' => 'APP_NAME=Example',
    ])->assertForbidden();
});

test('a member cannot see the decrypted environment on the show page', function () {
    [$team, $member] = teamWithMember(TeamRole::Member);
    $server = Server::factory()->for($team)->active()->create();
    $site = Site::factory()->for($team)->for($server)->active()->create([
        'env_encrypted' => 'SECRET=shh',
    ]);

    $response = $this->actingAs($member)->get(route('sites.show', $site));

    $response->assertInertia(fn ($page) => $page->where('env', null)->where('canManageEnvironment', false));
});

test('an owner can issue an ssl certificate for an active site', function () {
    Bus::fake();

    [$team, $owner] = teamWithMember(TeamRole::Owner);
    $server = Server::factory()->for($team)->active()->create();
    $site = Site::factory()->for($team)->for($server)->active()->create();

    $this->actingAs($owner)->post(route('sites.ssl.store', $site))->assertRedirect();

    Bus::assertDispatched(IssueSslCertificateJob::class, fn ($job) => $job->site->is($site));
});

test('ssl cannot be issued for a site that has not finished provisioning', function () {
    [$team, $owner] = teamWithMember(TeamRole::Owner);
    $server = Server::factory()->for($team)->active()->create();
    $site = Site::factory()->for($team)->for($server)->create();

    $this->actingAs($owner)->post(route('sites.ssl.store', $site))->assertStatus(409);
});
