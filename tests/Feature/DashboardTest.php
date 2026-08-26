<?php

use App\Enums\TeamRole;
use App\Models\Server;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('a user with no servers sees the onboarding dashboard', function () {
    [, $user] = teamWithMember(TeamRole::Owner);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('stats.servers', 0));
});

test('a user with servers sees their stats', function () {
    [$team, $user] = teamWithMember(TeamRole::Owner);
    Server::factory()->for($team)->active()->create();
    Server::factory()->for($team)->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('stats.servers', 2)
            ->where('stats.activeServers', 1)
        );
});
