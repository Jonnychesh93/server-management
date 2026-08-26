<?php

use App\Http\Controllers\DeploymentWebhookController;
use App\Http\Controllers\GithubAppWebhookController;
use Illuminate\Support\Facades\Route;

// Unauthenticated: git hosts trigger deployments here, verified via an
// HMAC signature rather than a session — see bootstrap/app.php for the
// matching CSRF exemption.
Route::post('webhooks/sites/{site}/deploy', [DeploymentWebhookController::class, 'store'])
    ->name('webhooks.sites.deploy');

Route::post('webhooks/github', [GithubAppWebhookController::class, 'store'])
    ->name('webhooks.github');
