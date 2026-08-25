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

Broadcast::channel('teams.{team}.servers.{server}.provisioning', function (User $user, Team $team, Server $server) {
    return $user->belongsToTeam($team) && $server->team_id === $team->id;
});

Broadcast::channel('teams.{team}.sites.{site}.provisioning', function (User $user, Team $team, Site $site) {
    return $user->belongsToTeam($team) && $site->team_id === $team->id;
});

Broadcast::channel('teams.{team}.deployments.{deployment}', function (User $user, Team $team, Deployment $deployment) {
    return $user->belongsToTeam($team) && $deployment->team_id === $team->id;
});
