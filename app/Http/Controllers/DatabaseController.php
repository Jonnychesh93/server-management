<?php

namespace App\Http\Controllers;

use App\Http\Requests\Databases\StoreDatabaseRequest;
use App\Jobs\CreateDatabaseJob;
use App\Jobs\DeleteDatabaseJob;
use App\Models\Database;
use App\Models\Server;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Inertia\Inertia;

class DatabaseController extends Controller
{
    /**
     * Add a new database to the server.
     */
    public function store(StoreDatabaseRequest $request, Server $server): RedirectResponse
    {
        $database = $server->databases()->create([
            'team_id' => $server->team_id,
            'name' => $request->validated('name'),
            'username' => $request->validated('username'),
            'password' => Str::random(32),
        ]);

        CreateDatabaseJob::dispatch($database);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Database added.')]);

        return back();
    }

    /**
     * Remove a database from the server.
     */
    public function destroy(Database $database): RedirectResponse
    {
        Gate::authorize('delete', $database);

        DeleteDatabaseJob::dispatch($database->server, $database->name, $database->username);

        $database->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Database removed.')]);

        return back();
    }
}
