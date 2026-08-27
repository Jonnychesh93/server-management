<?php

use App\Http\Controllers\DatabaseController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::post('servers/{server}/databases', [DatabaseController::class, 'store'])->name('databases.store');
    Route::delete('databases/{database}', [DatabaseController::class, 'destroy'])->name('databases.destroy');
});
