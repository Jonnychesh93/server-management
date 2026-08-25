<?php

use App\Enums\TeamRole;
use App\Models\TeamInvitation;
use App\Models\User;
use Illuminate\Support\Facades\Notification;

test('an owner can invite a new member by email', function () {
    Notification::fake();
    [$team, $owner] = teamWithMember(TeamRole::Owner);

    $this->actingAs($owner)->post(route('team-invitations.store', $team), [
        'email' => 'invitee@example.com',
        'role' => TeamRole::Member->value,
    ])->assertRedirect();

    expect($team->invitations()->where('email', 'invitee@example.com')->exists())->toBeTrue();
});

test('a member cannot invite anyone to the team', function () {
    [$team, $member] = teamWithMember(TeamRole::Member);

    $this->actingAs($member)->post(route('team-invitations.store', $team), [
        'email' => 'invitee@example.com',
        'role' => TeamRole::Member->value,
    ])->assertForbidden();
});

test('a user already on the team cannot be invited again', function () {
    [$team, $owner] = teamWithMember(TeamRole::Owner);

    $this->actingAs($owner)->post(route('team-invitations.store', $team), [
        'email' => $owner->email,
        'role' => TeamRole::Member->value,
    ])->assertInvalid(['email']);
});

test('a duplicate pending invitation cannot be created', function () {
    [$team, $owner] = teamWithMember(TeamRole::Owner);
    TeamInvitation::factory()->for($team)->create(['email' => 'invitee@example.com']);

    $this->actingAs($owner)->post(route('team-invitations.store', $team), [
        'email' => 'invitee@example.com',
        'role' => TeamRole::Member->value,
    ])->assertInvalid(['email']);
});

test('a user can accept an invitation addressed to them', function () {
    [$team] = teamWithMember(TeamRole::Owner);
    $invitee = User::factory()->create(['email' => 'invitee@example.com']);
    $invitation = TeamInvitation::factory()->for($team)->create([
        'email' => 'invitee@example.com',
        'role' => TeamRole::Member,
    ]);

    $this->actingAs($invitee)->post(route('team-invitations.accept', $invitation->token))
        ->assertRedirect(route('teams.show', $team));

    expect($team->hasUser($invitee))->toBeTrue();
    expect($invitee->refresh()->current_team_id)->toBe($team->id);
    expect(TeamInvitation::find($invitation->id))->toBeNull();
});

test('an invitation cannot be accepted by a different email address', function () {
    [$team] = teamWithMember(TeamRole::Owner);
    $wrongUser = User::factory()->create(['email' => 'someone-else@example.com']);
    $invitation = TeamInvitation::factory()->for($team)->create(['email' => 'invitee@example.com']);

    $this->actingAs($wrongUser)->post(route('team-invitations.accept', $invitation->token))
        ->assertRedirect(route('dashboard'));

    expect($team->hasUser($wrongUser))->toBeFalse();
    expect(TeamInvitation::find($invitation->id))->not->toBeNull();
});

test('an expired invitation cannot be accepted', function () {
    [$team] = teamWithMember(TeamRole::Owner);
    $invitee = User::factory()->create(['email' => 'invitee@example.com']);
    $invitation = TeamInvitation::factory()->for($team)->create([
        'email' => 'invitee@example.com',
        'expires_at' => now()->subDay(),
    ]);

    $this->actingAs($invitee)->post(route('team-invitations.accept', $invitation->token))
        ->assertRedirect(route('dashboard'));

    expect($team->hasUser($invitee))->toBeFalse();
    expect(TeamInvitation::find($invitation->id))->toBeNull();
});

test('an owner can revoke a pending invitation', function () {
    [$team, $owner] = teamWithMember(TeamRole::Owner);
    $invitation = TeamInvitation::factory()->for($team)->create();

    $this->actingAs($owner)->delete(route('team-invitations.destroy', [$team, $invitation]))
        ->assertRedirect();

    expect(TeamInvitation::find($invitation->id))->toBeNull();
});
