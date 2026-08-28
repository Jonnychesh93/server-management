<?php

use App\Enums\BootstrapCredentialType;
use App\Enums\ServerOs;
use App\Enums\TeamRole;
use App\Jobs\ProvisionServerJob;
use App\Models\Server;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;

test('an owner can add a server to the team', function () {
    Bus::fake();

    [$team, $owner] = teamWithMember(TeamRole::Owner);

    $this->actingAs($owner)->post(route('servers.store'), [
        'name' => 'web-1',
        'ip_address' => '203.0.113.10',
        'ssh_port' => 22,
        'ssh_user' => 'root',
        'os' => ServerOs::Ubuntu2404->value,
        'bootstrap_credential_type' => BootstrapCredentialType::Password->value,
        'bootstrap_credential' => 'super-secret-password',
    ])->assertRedirect();

    $server = Server::where('team_id', $team->id)->first();

    expect($server)->not->toBeNull();
    expect($server->bootstrap_credential)->toBe('super-secret-password');

    $raw = DB::table('servers')->where('id', $server->id)->value('bootstrap_credential');
    expect($raw)->not->toBe('super-secret-password');

    Bus::assertDispatched(ProvisionServerJob::class, fn ($job) => $job->server->is($server));
});

test('a member cannot add a server to the team', function () {
    [, $member] = teamWithMember(TeamRole::Member);

    $this->actingAs($member)->post(route('servers.store'), [
        'name' => 'web-1',
        'ip_address' => '203.0.113.10',
        'ssh_port' => 22,
        'ssh_user' => 'root',
        'os' => ServerOs::Ubuntu2404->value,
        'bootstrap_credential_type' => BootstrapCredentialType::Password->value,
        'bootstrap_credential' => 'super-secret-password',
    ])->assertForbidden();
});

test('a server is routed to by its uuid, not its sequential id', function () {
    [$team, $owner] = teamWithMember(TeamRole::Owner);
    $server = Server::factory()->for($team)->create();

    $url = route('servers.show', $server);

    expect($url)->toEndWith('/servers/'.$server->uuid);
    expect($url)->not->toEndWith('/servers/'.$server->id);

    $this->actingAs($owner)->get($url)->assertOk();
    $this->actingAs($owner)->get('/servers/'.$server->id)->assertNotFound();
});

test('a user cannot view a server belonging to another team', function () {
    $server = Server::factory()->create();
    $outsider = User::factory()->create(['current_team_id' => Team::factory()->create()->id]);

    $this->actingAs($outsider)->get(route('servers.show', $server))->assertForbidden();
});

test('two servers on the same team cannot share a name', function () {
    [$team, $owner] = teamWithMember(TeamRole::Owner);
    Server::factory()->for($team)->create(['name' => 'web-1']);

    $this->actingAs($owner)->post(route('servers.store'), [
        'name' => 'web-1',
        'ip_address' => '203.0.113.11',
        'ssh_port' => 22,
        'ssh_user' => 'root',
        'os' => ServerOs::Ubuntu2404->value,
        'bootstrap_credential_type' => BootstrapCredentialType::Password->value,
        'bootstrap_credential' => 'super-secret-password',
    ])->assertInvalid(['name']);
});

test('a manager can update server connection details', function () {
    [$team, $owner] = teamWithMember(TeamRole::Owner);
    $server = Server::factory()->for($team)->create();

    $this->actingAs($owner)->put(route('servers.update', $server), [
        'name' => $server->name,
        'ip_address' => '203.0.113.99',
        'ssh_port' => 2222,
        'ssh_user' => 'deploy',
    ])->assertRedirect(route('servers.show', $server));

    expect($server->refresh()->ip_address)->toBe('203.0.113.99');
});

test('a member cannot update server connection details', function () {
    [$team, $member] = teamWithMember(TeamRole::Member);
    $server = Server::factory()->for($team)->create();

    $this->actingAs($member)->put(route('servers.update', $server), [
        'name' => $server->name,
        'ip_address' => '203.0.113.99',
        'ssh_port' => 2222,
        'ssh_user' => 'deploy',
    ])->assertForbidden();
});

test('an owner can remove a server from the team', function () {
    [$team, $owner] = teamWithMember(TeamRole::Owner);
    $server = Server::factory()->for($team)->create();

    $this->actingAs($owner)->delete(route('servers.destroy', $server))
        ->assertRedirect(route('servers.index'));

    expect(Server::find($server->id))->toBeNull();
});
