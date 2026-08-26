<?php

use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');
});

require __DIR__.'/settings.php';
require __DIR__.'/teams.php';
require __DIR__.'/servers.php';
require __DIR__.'/sites.php';
require __DIR__.'/deployments.php';
require __DIR__.'/daemons.php';
require __DIR__.'/crons.php';
require __DIR__.'/github.php';
require __DIR__.'/webhooks.php';
