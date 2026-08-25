<?php

use App\Enums\TeamRole;
use App\Models\Team;
use App\Models\User;
use App\Services\Ssh\BootstrapConnection;
use App\Services\Ssh\SshConnection;
use App\Services\Ssh\SshConnector;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function something()
{
    // ..
}

/**
 * Create a team with a single member holding the given role, and switch
 * that member's current team to it.
 *
 * @return array{0: Team, 1: User}
 */
function teamWithMember(TeamRole $role = TeamRole::Owner): array
{
    $team = Team::factory()->create();
    $user = User::factory()->create(['current_team_id' => $team->id]);

    $team->users()->attach($user, ['role' => $role]);

    return [$team, $user];
}

/**
 * Bind a fake connector so a provisioning job never touches the network: the
 * bootstrap mock hands back a connection mock whose run() is driven by the
 * given callback, standing in for however many provisioning steps run.
 */
function fakeSshConnector(callable $onRun): SshConnector
{
    $connection = Mockery::mock(SshConnection::class);
    $connection->shouldReceive('run')->andReturnUsing($onRun);

    $bootstrap = Mockery::mock(BootstrapConnection::class);
    $bootstrap->shouldReceive('installControlPlaneKey')->andReturn($connection);

    $connector = Mockery::mock(SshConnector::class);
    $connector->shouldReceive('bootstrap')->andReturn($bootstrap);

    return $connector;
}

/**
 * Bind a fake connector for site provisioning jobs, which authenticate as
 * root directly rather than bootstrapping, and also write files over SFTP.
 */
function fakeSiteSshConnector(callable $onRun): SshConnector
{
    $connection = Mockery::mock(SshConnection::class);
    $connection->shouldReceive('run')->andReturnUsing($onRun);
    $connection->shouldReceive('writeFile')->andReturnNull();

    $connector = Mockery::mock(SshConnector::class);
    $connector->shouldReceive('connectAsRoot')->andReturn($connection);
    $connector->shouldReceive('connect')->andReturn($connection);

    return $connector;
}
