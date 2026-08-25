<?php

namespace App\Http\Controllers;

use App\Http\Requests\Crons\StoreCronRequest;
use App\Jobs\RemoveCronJob;
use App\Jobs\SyncCronJob;
use App\Models\Cron;
use App\Models\Server;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class CronController extends Controller
{
    /**
     * Add a new cron job to the server.
     */
    public function store(StoreCronRequest $request, Server $server): RedirectResponse
    {
        $cron = $server->crons()->create([
            'team_id' => $server->team_id,
            ...$request->validated(),
        ]);

        SyncCronJob::dispatch($cron);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Cron job added.')]);

        return back();
    }

    /**
     * Remove a cron job from the server.
     */
    public function destroy(Cron $cron): RedirectResponse
    {
        Gate::authorize('delete', $cron);

        RemoveCronJob::dispatch($cron->server, $cron->filename());

        $cron->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Cron job removed.')]);

        return back();
    }
}
