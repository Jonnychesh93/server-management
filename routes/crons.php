<?php

use App\Http\Controllers\CronController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::post('servers/{server}/crons', [CronController::class, 'store'])->name('crons.store');
    Route::delete('crons/{cron}', [CronController::class, 'destroy'])->name('crons.destroy');
});
