<?php

use App\Http\Controllers\Teams\AcceptTeamInvitationController;
use App\Http\Controllers\Teams\CurrentTeamController;
use App\Http\Controllers\Teams\TeamController;
use App\Http\Controllers\Teams\TeamInvitationController;
use App\Http\Controllers\Teams\TeamMemberController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('teams/create', [TeamController::class, 'create'])->name('teams.create');
    Route::post('teams', [TeamController::class, 'store'])->name('teams.store');
    Route::get('teams/{team}', [TeamController::class, 'show'])->name('teams.show');
    Route::put('teams/{team}', [TeamController::class, 'update'])->name('teams.update');
    Route::delete('teams/{team}', [TeamController::class, 'destroy'])->name('teams.destroy');

    Route::put('current-team/{team}', [CurrentTeamController::class, 'update'])->name('current-team.update');

    Route::put('teams/{team}/members/{member}', [TeamMemberController::class, 'update'])->name('team-members.update');
    Route::delete('teams/{team}/members/{member}', [TeamMemberController::class, 'destroy'])->name('team-members.destroy');

    Route::post('teams/{team}/invitations', [TeamInvitationController::class, 'store'])->name('team-invitations.store');
    Route::delete('teams/{team}/invitations/{invitation}', [TeamInvitationController::class, 'destroy'])->name('team-invitations.destroy');

    Route::get('team-invitations/{invitation:token}', [AcceptTeamInvitationController::class, 'show'])->name('team-invitations.show');
    Route::post('team-invitations/{invitation:token}', [AcceptTeamInvitationController::class, 'store'])->name('team-invitations.accept');
});
