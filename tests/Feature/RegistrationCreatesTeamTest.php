<?php

use App\Enums\TeamRole;
use App\Models\User;
use Laravel\Fortify\Features;

beforeEach(function () {
    $this->skipUnlessFortifyHas(Features::registration());
});

test('registering a new user creates a personal team with them as owner', function () {
    $this->post(route('register.store'), [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $user = User::whereEmail('test@example.com')->first();

    expect($user)->not->toBeNull();
    expect($user->current_team_id)->not->toBeNull();
    expect($user->currentTeam->name)->toBe("Test User's Team");
    expect($user->roleOn($user->currentTeam))->toBe(TeamRole::Owner);
});
