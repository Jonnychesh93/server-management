<?php

use App\Models\Deployment;
use App\Models\Server;
use App\Models\Site;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// $server is deliberately the raw id, not an implicitly-bound Server model:
// the model's route key is its uuid (for URLs), but this channel name is
// never exposed in a URL and is broadcast on using the plain id (see
// ServerProvisioningOutputReceived/Finished), so resolving it against the
// uuid column here would never match.
Broadcast::channel('teams.{team}.servers.{server}.provisioning', function (User $user, Team $team, int $server) {
    return $user->belongsToTeam($team) && Server::where('id', $server)->where('team_id', $team->id)->exists();
});

Broadcast::channel('teams.{team}.sites.{site}.provisioning', function (User $user, Team $team, Site $site) {
    return $user->belongsToTeam($team) && $site->team_id === $team->id;
});

Broadcast::channel('teams.{team}.deployments.{deployment}', function (User $user, Team $team, Deployment $deployment) {
    return $user->belongsToTeam($team) && $deployment->team_id === $team->id;
});
