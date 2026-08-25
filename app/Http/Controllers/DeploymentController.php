<?php

namespace App\Http\Controllers;

use App\Enums\DeploymentTriggerType;
use App\Jobs\RunDeploymentJob;
use App\Models\Deployment;
use App\Models\Site;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class DeploymentController extends Controller
{
    /**
     * Trigger a new deployment for the site.
     */
    public function store(Request $request, Site $site): RedirectResponse
    {
        Gate::authorize('create', [Deployment::class, $site]);

        $deployment = $site->deployments()->create([
            'team_id' => $site->team_id,
            'triggered_by_type' => DeploymentTriggerType::User,
            'triggered_by_user_id' => $request->user()->id,
        ]);

        RunDeploymentJob::dispatch($deployment);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Deployment started.')]);

        return back();
    }

    /**
     * Show a deployment's live or historical log.
     */
    public function show(Deployment $deployment): Response
    {
        Gate::authorize('view', $deployment);

        return Inertia::render('deployments/Show', [
            'deployment' => $deployment->load('site', 'triggeredByUser'),
        ]);
    }
}
