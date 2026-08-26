<?php

namespace App\Http\Controllers;

use App\Enums\ServerProvisioningStatus;
use App\Models\ActivityLog;
use App\Models\Deployment;
use App\Models\Site;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Show the team's dashboard: at-a-glance stats, recent deployments,
     * and recent activity.
     */
    public function __invoke(Request $request): Response
    {
        $team = $request->user()->currentTeam;

        $siteIds = Site::query()->where('team_id', $team->id)->pluck('id');

        return Inertia::render('Dashboard', [
            'stats' => [
                'servers' => $team->servers()->count(),
                'activeServers' => $team->servers()->where('provisioning_status', ServerProvisioningStatus::Active)->count(),
                'sites' => $siteIds->count(),
                'deployments' => Deployment::query()->where('team_id', $team->id)->count(),
            ],
            'recentServers' => $team->servers()->latest()->limit(4)->get(),
            'recentDeployments' => Deployment::query()
                ->where('team_id', $team->id)
                ->with('site:id,domain')
                ->latest()
                ->limit(5)
                ->get(),
            'recentActivity' => ActivityLog::query()
                ->where('team_id', $team->id)
                ->latest('created_at')
                ->limit(8)
                ->get(),
        ]);
    }
}
