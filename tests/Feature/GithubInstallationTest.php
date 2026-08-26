<?php

use App\Enums\TeamRole;
use App\Models\GithubInstallation;
use App\Models\Team;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;

test('an owner can start connecting github', function () {
    [$team, $owner] = teamWithMember(TeamRole::Owner);

    config(['services.github.app_slug' => 'my-app']);

    $response = $this->actingAs($owner)->get(route('github.install'));

    $response->assertRedirect();
    expect($response->headers->get('Location'))->toStartWith('https://github.com/apps/my-app/installations/new?state=');
});

test('a member cannot start connecting github', function () {
    [$team, $member] = teamWithMember(TeamRole::Member);

    $this->actingAs($member)->get(route('github.install'))->assertForbidden();
});

test('the callback stores the installation for the correct team', function () {
    config([
        'services.github.app_id' => '1',
        'services.github.private_key' => fakeGithubAppPrivateKey(),
    ]);

    Http::fake([
        'api.github.com/app/installations/*' => Http::response([
            'account' => ['login' => 'acme', 'type' => 'Organization'],
        ]),
    ]);

    [$team, $owner] = teamWithMember(TeamRole::Owner);
    $state = Crypt::encryptString((string) $team->id);

    $this->actingAs($owner)->get(route('github.callback', [
        'installation_id' => 12345,
        'state' => $state,
    ]))->assertRedirect(route('teams.show', $team));

    $installation = GithubInstallation::where('team_id', $team->id)->first();
    expect($installation)->not->toBeNull();
    expect($installation->installation_id)->toBe(12345);
    expect($installation->account_login)->toBe('acme');
});

test('the callback rejects a state for a different team', function () {
    [$team, $owner] = teamWithMember(TeamRole::Owner);
    $otherTeam = Team::factory()->create();
    $state = Crypt::encryptString((string) $otherTeam->id);

    $this->actingAs($owner)->get(route('github.callback', [
        'installation_id' => 12345,
        'state' => $state,
    ]))->assertForbidden();
});

test('an owner can disconnect github', function () {
    [$team, $owner] = teamWithMember(TeamRole::Owner);
    GithubInstallation::factory()->for($team)->create();

    $this->actingAs($owner)->delete(route('github.destroy'))->assertRedirect();

    expect(GithubInstallation::where('team_id', $team->id)->exists())->toBeFalse();
});
