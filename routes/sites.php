<?php

use App\Http\Controllers\SiteController;
use App\Http\Controllers\SiteEnvironmentController;
use App\Http\Controllers\SiteSslController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('servers/{server}/sites/create', [SiteController::class, 'create'])->name('sites.create');
    Route::post('servers/{server}/sites', [SiteController::class, 'store'])->name('sites.store');

    Route::get('sites/{site}', [SiteController::class, 'show'])->name('sites.show');
    Route::get('sites/{site}/edit', [SiteController::class, 'edit'])->name('sites.edit');
    Route::put('sites/{site}', [SiteController::class, 'update'])->name('sites.update');
    Route::delete('sites/{site}', [SiteController::class, 'destroy'])->name('sites.destroy');
    Route::post('sites/{site}/retry', [SiteController::class, 'retry'])->name('sites.retry');

    Route::put('sites/{site}/environment', [SiteEnvironmentController::class, 'update'])->name('sites.environment.update');
    Route::post('sites/{site}/ssl', [SiteSslController::class, 'store'])->name('sites.ssl.store');
});
