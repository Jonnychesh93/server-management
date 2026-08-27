<?php

use App\Http\Controllers\GithubInstallationController;
use App\Http\Controllers\GithubRepositoryController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('github/install', [GithubInstallationController::class, 'create'])->name('github.install');
    Route::get('github/callback', [GithubInstallationController::class, 'callback'])->name('github.callback');
    Route::delete('github', [GithubInstallationController::class, 'destroy'])->name('github.destroy');
    Route::get('github/repositories/{owner}/{repo}/branches', [GithubRepositoryController::class, 'branches'])->name('github.repositories.branches');
});
