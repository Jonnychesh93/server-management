<?php

use App\Enums\TeamRole;
use App\Jobs\ProvisionSiteJob;
use App\Models\GitConnection;
use App\Models\GithubInstallation;
use App\Models\Server;
use App\Models\Site;
use App\Models\SshKey;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;

test('an owner can add a site to a server', function () {
    Bus::fake();

    [$team, $owner] = teamWithMember(TeamRole::Owner);
    $server = Server::factory()->for($team)->active()->create();

    $this->actingAs($owner)->post(route('sites.store', $server), [
        'domain' => 'example.com',
        'php_version' => '8.3',
    ])->assertRedirect();

    $site = Site::where('domain', 'example.com')->first();

    expect($site)->not->toBeNull();
    expect($site->server_id)->toBe($server->id);
    expect($site->team_id)->toBe($team->id);

    Bus::assertDispatched(ProvisionSiteJob::class, fn ($job) => $job->site->is($site));
});

test('a member cannot add a site to a server', function () {
    [$team, $member] = teamWithMember(TeamRole::Member);
    $server = Server::factory()->for($team)->active()->create();

    $this->actingAs($member)->post(route('sites.store', $server), [
        'domain' => 'example.com',
        'php_version' => '8.3',
    ])->assertForbidden();
});

test('an invalid domain is rejected', function () {
    [$team, $owner] = teamWithMember(TeamRole::Owner);
    $server = Server::factory()->for($team)->active()->create();

    $this->actingAs($owner)->post(route('sites.store', $server), [
        'domain' => 'not a domain; rm -rf /',
        'php_version' => '8.3',
    ])->assertInvalid(['domain']);
});

test('two sites on the same server cannot share a domain', function () {
    Bus::fake();

    [$team, $owner] = teamWithMember(TeamRole::Owner);
    $server = Server::factory()->for($team)->active()->create();
    Site::factory()->for($team)->for($server)->create(['domain' => 'example.com']);

    $this->actingAs($owner)->post(route('sites.store', $server), [
        'domain' => 'example.com',
        'php_version' => '8.3',
    ])->assertInvalid(['domain']);
});

test('adding a repository generates a deploy key and git connection', function () {
    Bus::fake();

    [$team, $owner] = teamWithMember(TeamRole::Owner);
    $server = Server::factory()->for($team)->active()->create();

    $this->actingAs($owner)->post(route('sites.store', $server), [
        'domain' => 'example.com',
        'php_version' => '8.3',
        'repository' => 'git@github.com:acme/example.git',
        'branch' => 'main',
    ])->assertRedirect();

    $site = Site::where('domain', 'example.com')->first();

    $connection = GitConnection::where('site_id', $site->id)->first();
    expect($connection)->not->toBeNull();
    expect($connection->repository)->toBe('git@github.com:acme/example.git');

    $key = SshKey::where('site_id', $site->id)->first();
    expect($key)->not->toBeNull();
    expect($connection->deploy_key_id)->toBe($key->id);
});

test('the create form lists the team\'s github repositories when connected', function () {
    config([
        'services.github.app_id' => '1',
        'services.github.private_key' => fakeGithubAppPrivateKey(),
    ]);

    Http::fake([
        'api.github.com/app/installations/*/access_tokens' => Http::response(['token' => 'installation-token']),
        'api.github.com/installation/repositories' => Http::response([
            'repositories' => [
                ['full_name' => 'acme/example', 'default_branch' => 'main'],
            ],
        ]),
    ]);

    [$team, $owner] = teamWithMember(TeamRole::Owner);
    GithubInstallation::factory()->for($team)->create(['installation_id' => 12345]);
    $server = Server::factory()->for($team)->active()->create();

    $this->actingAs($owner)->get(route('sites.create', $server))
        ->assertInertia(fn ($page) => $page->where('repositories', [
            ['full_name' => 'acme/example', 'default_branch' => 'main'],
        ]));
});

test('a github repository\'s branches can be fetched for the create form', function () {
    config([
        'services.github.app_id' => '1',
        'services.github.private_key' => fakeGithubAppPrivateKey(),
    ]);

    Http::fake([
        'api.github.com/app/installations/*/access_tokens' => Http::response(['token' => 'installation-token']),
        'api.github.com/repos/acme/example/branches*' => Http::response([
            ['name' => 'main'],
            ['name' => 'develop'],
        ]),
    ]);

    [$team, $owner] = teamWithMember(TeamRole::Owner);
    GithubInstallation::factory()->for($team)->create(['installation_id' => 12345]);

    $this->actingAs($owner)
        ->get(route('github.repositories.branches', ['owner' => 'acme', 'repo' => 'example']))
        ->assertOk()
        ->assertJson(['branches' => ['main', 'develop']]);
});

test('a member cannot fetch a github repository\'s branches', function () {
    [$team, $member] = teamWithMember(TeamRole::Member);
    GithubInstallation::factory()->for($team)->create();

    $this->actingAs($member)
        ->get(route('github.repositories.branches', ['owner' => 'acme', 'repo' => 'example']))
        ->assertForbidden();
});

test('a user cannot view a site belonging to another team', function () {
    [, $outsider] = teamWithMember(TeamRole::Owner);
    $site = Site::factory()->create();

    $this->actingAs($outsider)->get(route('sites.show', $site))->assertForbidden();
});

test('a manager can update a site deploy script', function () {
    [$team, $owner] = teamWithMember(TeamRole::Owner);
    $server = Server::factory()->for($team)->active()->create();
    $site = Site::factory()->for($team)->for($server)->active()->create();

    $this->actingAs($owner)->put(route('sites.update', $site), [
        'deploy_script' => 'echo "hello"',
    ])->assertRedirect(route('sites.show', $site));

    expect($site->refresh()->deploy_script)->toBe('echo "hello"');
});

test('an owner can remove a site from the server', function () {
    [$team, $owner] = teamWithMember(TeamRole::Owner);
    $server = Server::factory()->for($team)->active()->create();
    $site = Site::factory()->for($team)->for($server)->active()->create();

    $this->actingAs($owner)->delete(route('sites.destroy', $site))
        ->assertRedirect(route('servers.show', $server));

    expect(Site::find($site->id))->toBeNull();
});
