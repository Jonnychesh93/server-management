<?php

namespace App\Http\Controllers;

use App\Enums\SiteStatus;
use App\Enums\SslStatus;
use App\Jobs\IssueSslCertificateJob;
use App\Models\Site;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class SiteSslController extends Controller
{
    /**
     * Issue a Let's Encrypt SSL certificate for the site.
     */
    public function store(Site $site): RedirectResponse
    {
        Gate::authorize('update', $site);

        abort_unless($site->status === SiteStatus::Active, 409);

        $site->forceFill(['ssl_status' => SslStatus::Pending])->save();

        IssueSslCertificateJob::dispatch($site);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Issuing SSL certificate.')]);

        return back();
    }
}
