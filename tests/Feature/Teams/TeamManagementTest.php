<?php

use App\Enums\TeamRole;
use App\Models\Team;
use App\Models\User;

test('a team member can view the team page', function () {
    [$team, $user] = teamWithMember();

    $this->actingAs($user)->get(route('teams.show', $team))->assertOk();
});

test('a non-member cannot view the team page', function () {
    [$team] = teamWithMember();
    $outsider = User::factory()->create();

    $this->actingAs($outsider)->get(route('teams.show', $team))->assertForbidden();
});

test('an owner can rename the team', function () {
    [$team, $owner] = teamWithMember(TeamRole::Owner);

    $this->actingAs($owner)->put(route('teams.update', $team), ['name' => 'Renamed'])
        ->assertRedirect(route('teams.show', $team));

    expect($team->refresh()->name)->toBe('Renamed');
});

test('a member cannot rename the team', function () {
    [$team, $member] = teamWithMember(TeamRole::Member);

    $this->actingAs($member)->put(route('teams.update', $team), ['name' => 'Renamed'])
        ->assertForbidden();
});

test('an owner cannot delete their only team', function () {
    [$team, $owner] = teamWithMember(TeamRole::Owner);

    $this->actingAs($owner)->delete(route('teams.destroy', $team))
        ->assertRedirect();

    expect(Team::find($team->id))->not->toBeNull();
});

test('an owner can delete a team when they belong to another one', function () {
    [$team, $owner] = teamWithMember(TeamRole::Owner);
    $otherTeam = Team::factory()->create();
    $otherTeam->users()->attach($owner, ['role' => TeamRole::Member]);

    $this->actingAs($owner)->delete(route('teams.destroy', $team))
        ->assertRedirect(route('dashboard'));

    expect(Team::find($team->id))->toBeNull();
});

test('an admin cannot delete the team', function () {
    [$team, $admin] = teamWithMember(TeamRole::Admin);

    $this->actingAs($admin)->delete(route('teams.destroy', $team))
        ->assertForbidden();

    expect(Team::find($team->id))->not->toBeNull();
});

test('an owner can remove a member from the team', function () {
    [$team, $owner] = teamWithMember(TeamRole::Owner);
    $member = User::factory()->create();
    $team->users()->attach($member, ['role' => TeamRole::Member]);

    $this->actingAs($owner)->delete(route('team-members.destroy', [$team, $member]))
        ->assertRedirect();

    expect($team->hasUser($member))->toBeFalse();
});

test('the team owner cannot be removed', function () {
    [$team, $owner] = teamWithMember(TeamRole::Owner);
    $admin = User::factory()->create();
    $team->users()->attach($admin, ['role' => TeamRole::Admin]);

    $this->actingAs($admin)->delete(route('team-members.destroy', [$team, $owner]))
        ->assertForbidden();

    expect($team->hasUser($owner))->toBeTrue();
});

test('a user can switch their current team', function () {
    [$team, $user] = teamWithMember();
    $otherTeam = Team::factory()->create();
    $otherTeam->users()->attach($user, ['role' => TeamRole::Member]);

    $this->actingAs($user)->put(route('current-team.update', $otherTeam))
        ->assertRedirect();

    expect($user->refresh()->current_team_id)->toBe($otherTeam->id);
});

test('a user cannot switch to a team they do not belong to', function () {
    [, $user] = teamWithMember();
    $otherTeam = Team::factory()->create();

    $this->actingAs($user)->put(route('current-team.update', $otherTeam))
        ->assertForbidden();
});
