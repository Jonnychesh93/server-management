<?php

namespace App\Http\Controllers;

use App\Enums\BootstrapCredentialType;
use App\Enums\ServerOs;
use App\Enums\ServerProvisioningStatus;
use App\Http\Requests\Servers\StoreServerRequest;
use App\Http\Requests\Servers\UpdateServerRequest;
use App\Jobs\ProvisionServerJob;
use App\Models\ActivityLog;
use App\Models\Server;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ServerController extends Controller
{
    /**
     * List the current team's servers.
     */
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Server::class);

        return Inertia::render('servers/Index', [
            'servers' => $request->user()->currentTeam->servers()->orderBy('name')->get(),
        ]);
    }

    /**
     * Show the form for adding a new server.
     */
    public function create(): Response
    {
        Gate::authorize('create', Server::class);

        return Inertia::render('servers/Create', [
            'operatingSystems' => array_map(
                fn (ServerOs $os) => ['value' => $os->value, 'label' => $os->label()],
                ServerOs::cases(),
            ),
            'credentialTypes' => array_column(BootstrapCredentialType::cases(), 'value'),
        ]);
    }

    /**
     * Add a new server to the current team, pending provisioning.
     */
    public function store(StoreServerRequest $request): RedirectResponse
    {
        $server = $request->user()->currentTeam->servers()->create($request->validated());

        ActivityLog::record(
            $request->user()->currentTeam,
            $request->user(),
            $server,
            'server.created',
            "{$request->user()->name} added server \"{$server->name}\".",
        );

        ProvisionServerJob::dispatch($server);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Server added, provisioning has started.')]);

        return to_route('servers.show', $server);
    }

    /**
     * Retry provisioning a server that previously failed.
     */
    public function retry(Server $server): RedirectResponse
    {
        Gate::authorize('update', $server);

        abort_unless($server->provisioning_status === ServerProvisioningStatus::Failed, 409);

        $server->forceFill([
            'provisioning_status' => ServerProvisioningStatus::Pending,
            'provisioning_failed_step' => null,
        ])->save();

        ProvisionServerJob::dispatch($server);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Retrying provisioning.')]);

        return to_route('servers.show', $server);
    }

    /**
     * Show a server's details.
     */
    public function show(Server $server): Response
    {
        Gate::authorize('view', $server);

        return Inertia::render('servers/Show', [
            'server' => $server,
            'sites' => $server->sites()->orderBy('domain')->get(),
            'daemons' => $server->daemons()->orderBy('command')->get(),
            'crons' => $server->crons()->orderBy('command')->get(),
        ]);
    }

    /**
     * Show the form for editing a server's connection details.
     */
    public function edit(Server $server): Response
    {
        Gate::authorize('update', $server);

        return Inertia::render('servers/Edit', [
            'server' => $server,
        ]);
    }

    /**
     * Update a server's connection details.
     */
    public function update(UpdateServerRequest $request, Server $server): RedirectResponse
    {
        $server->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Server updated.')]);

        return to_route('servers.show', $server);
    }

    /**
     * Remove a server from the team.
     */
    public function destroy(Server $server): RedirectResponse
    {
        Gate::authorize('delete', $server);

        $server->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Server removed.')]);

        return to_route('servers.index');
    }
}
