<?php

namespace App\Http\Controllers;

use App\Http\Requests\Commands\StoreCommandRequest;
use App\Jobs\RunCommandJob;
use App\Models\Command;
use App\Models\Site;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class CommandController extends Controller
{
    /**
     * Run an ad-hoc command against the site.
     */
    public function store(StoreCommandRequest $request, Site $site): RedirectResponse
    {
        $command = $site->commands()->create([
            'team_id' => $site->team_id,
            'user_id' => $request->user()->id,
            'command' => $request->string('command')->toString(),
        ]);

        RunCommandJob::dispatch($command);

        return to_route('commands.show', $command);
    }

    /**
     * Show a command's live or historical log.
     */
    public function show(Command $command): Response
    {
        Gate::authorize('view', $command);

        return Inertia::render('commands/Show', [
            'command' => $command->load('site'),
        ]);
    }
}
