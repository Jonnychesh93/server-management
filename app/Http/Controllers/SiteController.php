<?php

namespace App\Http\Controllers;

use App\Enums\GitProvider;
use App\Enums\SiteStatus;
use App\Enums\SshKeyType;
use App\Http\Requests\Sites\StoreSiteRequest;
use App\Http\Requests\Sites\UpdateSiteRequest;
use App\Jobs\ProvisionSiteJob;
use App\Models\ActivityLog;
use App\Models\Server;
use App\Models\Site;
use App\Services\Provisioning\Steps\InstallPhp;
use App\Services\Ssh\KeyPairGenerator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class SiteController extends Controller
{
    /**
     * Show the form for adding a new site to a server.
     */
    public function create(Server $server): Response
    {
        Gate::authorize('create', Site::class);

        return Inertia::render('sites/Create', [
            'server' => $server,
            'phpVersions' => InstallPhp::SUPPORTED_VERSIONS,
        ]);
    }

    /**
     * Add a new site to the server, pending provisioning.
     */
    public function store(StoreSiteRequest $request, Server $server): RedirectResponse
    {
        $site = DB::transaction(function () use ($request, $server) {
            $site = $server->sites()->create([
                'team_id' => $server->team_id,
                'domain' => $request->validated('domain'),
                'php_version' => $request->validated('php_version'),
                'deploy_script' => Site::DEFAULT_DEPLOY_SCRIPT,
            ]);

            if ($request->validated('repository')) {
                $key = KeyPairGenerator::generateEd25519();

                $sshKey = $site->sshKeys()->create([
                    'team_id' => $server->team_id,
                    'name' => "{$site->domain} deploy key",
                    'public_key' => $key['public'],
                    'private_key' => $key['private'],
                    'type' => SshKeyType::DeployKey,
                ]);

                $site->gitConnection()->create([
                    'provider' => GitProvider::Manual,
                    'repository' => $request->validated('repository'),
                    'branch' => $request->validated('branch') ?: 'main',
                    'deploy_key_id' => $sshKey->id,
                    'webhook_secret' => Str::random(40),
                ]);
            }

            return $site;
        });

        ActivityLog::record(
            $server->team,
            $request->user(),
            $site,
            'site.created',
            "{$request->user()->name} added site \"{$site->domain}\".",
        );

        ProvisionSiteJob::dispatch($site);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Site added, provisioning has started.')]);

        return to_route('sites.show', $site);
    }

    /**
     * Show a site's details.
     */
    public function show(Request $request, Site $site): Response
    {
        Gate::authorize('view', $site);

        $canManageEnvironment = $request->user()->canManage($site->team);
        $site->load(['gitConnection.deployKey', 'deployments' => fn ($query) => $query->limit(10)]);

        return Inertia::render('sites/Show', [
            'site' => $site,
            'canManageEnvironment' => $canManageEnvironment,
            'env' => $canManageEnvironment ? $site->env_encrypted : null,
            'webhookSecret' => $canManageEnvironment ? $site->gitConnection?->webhook_secret : null,
            'webhookUrl' => $site->gitConnection ? route('webhooks.sites.deploy', $site) : null,
        ]);
    }

    /**
     * Show the form for editing a site's deploy script.
     */
    public function edit(Site $site): Response
    {
        Gate::authorize('update', $site);

        return Inertia::render('sites/Edit', [
            'site' => $site,
        ]);
    }

    /**
     * Update a site's deploy script.
     */
    public function update(UpdateSiteRequest $request, Site $site): RedirectResponse
    {
        $site->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Deploy script updated.')]);

        return to_route('sites.show', $site);
    }

    /**
     * Remove a site from the server.
     */
    public function destroy(Site $site): RedirectResponse
    {
        Gate::authorize('delete', $site);

        $server = $site->server;

        $site->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Site removed.')]);

        return to_route('servers.show', $server);
    }

    /**
     * Retry provisioning a site that previously failed.
     */
    public function retry(Site $site): RedirectResponse
    {
        Gate::authorize('update', $site);

        abort_unless($site->status === SiteStatus::Failed, 409);

        $site->forceFill([
            'status' => SiteStatus::Provisioning,
            'provisioning_failed_step' => null,
        ])->save();

        ProvisionSiteJob::dispatch($site);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Retrying provisioning.')]);

        return to_route('sites.show', $site);
    }
}
