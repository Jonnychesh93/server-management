<?php

namespace App\Http\Controllers;

use App\Http\Requests\Daemons\StoreDaemonRequest;
use App\Jobs\RemoveDaemonJob;
use App\Jobs\SyncDaemonJob;
use App\Models\Daemon;
use App\Models\Server;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class DaemonController extends Controller
{
    /**
     * Add a new daemon to the server.
     */
    public function store(StoreDaemonRequest $request, Server $server): RedirectResponse
    {
        $daemon = $server->daemons()->create([
            'team_id' => $server->team_id,
            ...$request->validated(),
        ]);

        SyncDaemonJob::dispatch($daemon);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Daemon added.')]);

        return back();
    }

    /**
     * Remove a daemon from the server.
     */
    public function destroy(Daemon $daemon): RedirectResponse
    {
        Gate::authorize('delete', $daemon);

        RemoveDaemonJob::dispatch($daemon->server, $daemon->slug());

        $daemon->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Daemon removed.')]);

        return back();
    }
}
