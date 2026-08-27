<?php

use App\Enums\TeamRole;
use App\Jobs\CreateDatabaseJob;
use App\Jobs\DeleteDatabaseJob;
use App\Models\Database;
use App\Models\Server;
use Illuminate\Support\Facades\Bus;

test('an owner can add a database to a server', function () {
    Bus::fake();

    [$team, $owner] = teamWithMember(TeamRole::Owner);
    $server = Server::factory()->for($team)->active()->create();

    $this->actingAs($owner)->post(route('databases.store', $server), [
        'name' => 'budget_buddy',
        'username' => 'budget_buddy',
    ])->assertRedirect();

    $database = Database::where('server_id', $server->id)->first();
    expect($database)->not->toBeNull();
    expect($database->name)->toBe('budget_buddy');
    expect($database->username)->toBe('budget_buddy');
    expect($database->password)->not->toBeEmpty();

    Bus::assertDispatched(CreateDatabaseJob::class, fn ($job) => $job->database->is($database));
});

test('a member cannot add a database to a server', function () {
    [$team, $member] = teamWithMember(TeamRole::Member);
    $server = Server::factory()->for($team)->active()->create();

    $this->actingAs($member)->post(route('databases.store', $server), [
        'name' => 'budget_buddy',
        'username' => 'budget_buddy',
    ])->assertForbidden();
});

test('a database name must be unique per server', function () {
    [$team, $owner] = teamWithMember(TeamRole::Owner);
    $server = Server::factory()->for($team)->active()->create();
    Database::factory()->for($team)->for($server)->create(['name' => 'budget_buddy']);

    $this->actingAs($owner)->post(route('databases.store', $server), [
        'name' => 'budget_buddy',
        'username' => 'another_user',
    ])->assertInvalid(['name']);
});

test('an owner can remove a database', function () {
    Bus::fake();

    [$team, $owner] = teamWithMember(TeamRole::Owner);
    $server = Server::factory()->for($team)->active()->create();
    $database = Database::factory()->for($team)->for($server)->create();

    $this->actingAs($owner)->delete(route('databases.destroy', $database))
        ->assertRedirect();

    expect(Database::find($database->id))->toBeNull();

    Bus::assertDispatched(
        DeleteDatabaseJob::class,
        fn ($job) => $job->server->is($server)
            && $job->name === $database->name
            && $job->username === $database->username,
    );
});
