<?php

namespace App\Http\Controllers;

use App\Http\Requests\Sites\UpdateSiteEnvironmentRequest;
use App\Jobs\SyncSiteEnvironmentJob;
use App\Models\Site;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class SiteEnvironmentController extends Controller
{
    /**
     * Update a site's .env contents and push it to the server.
     */
    public function update(UpdateSiteEnvironmentRequest $request, Site $site): RedirectResponse
    {
        $site->update(['env_encrypted' => $request->validated('env')]);

        SyncSiteEnvironmentJob::dispatch($site);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Environment file updated.')]);

        return back();
    }
}
