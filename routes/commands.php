<?php

use App\Http\Controllers\CommandController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::post('sites/{site}/commands', [CommandController::class, 'store'])->name('commands.store');
    Route::get('commands/{command}', [CommandController::class, 'show'])->name('commands.show');
});
