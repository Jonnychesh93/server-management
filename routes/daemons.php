<?php

use App\Http\Controllers\DaemonController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::post('servers/{server}/daemons', [DaemonController::class, 'store'])->name('daemons.store');
    Route::delete('daemons/{daemon}', [DaemonController::class, 'destroy'])->name('daemons.destroy');
});
