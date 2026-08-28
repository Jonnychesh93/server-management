<?php

use App\Enums\TeamRole;
use App\Jobs\RunDeploymentJob;
use App\Models\Deployment;
use App\Models\Server;
use App\Models\Site;
use Illuminate\Support\Facades\Bus;

test('a member can trigger a deployment', function () {
    Bus::fake();

    [$team, $member] = teamWithMember(TeamRole::Member);
    $server = Server::factory()->for($team)->active()->create();
    $site = Site::factory()->for($team)->for($server)->active()->create();

    $this->actingAs($member)->post(route('deployments.store', $site))
        ->assertRedirect();

    $deployment = Deployment::where('site_id', $site->id)->first();
    expect($deployment)->not->toBeNull();
    expect($deployment->triggered_by_type->value)->toBe('user');
    expect($deployment->triggered_by_user_id)->toBe($member->id);

    Bus::assertDispatched(RunDeploymentJob::class, fn ($job) => $job->deployment->is($deployment));
});

test('a deployment is routed to by its uuid, not its sequential id', function () {
    [$team, $owner] = teamWithMember(TeamRole::Owner);
    $server = Server::factory()->for($team)->active()->create();
    $site = Site::factory()->for($team)->for($server)->active()->create();
    $deployment = Deployment::factory()->for($team)->for($site)->create();

    $url = route('deployments.show', $deployment);

    expect($url)->toEndWith('/deployments/'.$deployment->uuid);
    expect($url)->not->toEndWith('/deployments/'.$deployment->id);

    $this->actingAs($owner)->get($url)->assertOk();
    $this->actingAs($owner)->get('/deployments/'.$deployment->id)->assertNotFound();
});

test('a user outside the team cannot trigger a deployment', function () {
    [, $outsider] = teamWithMember(TeamRole::Owner);
    $site = Site::factory()->active()->create();

    $this->actingAs($outsider)->post(route('deployments.store', $site))
        ->assertForbidden();
});
