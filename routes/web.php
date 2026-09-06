<?php

use App\Http\Controllers\AuthenticationController;
use App\Http\Controllers\WorklogController;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Route;

Route::get('/login', [AuthenticationController::class, 'create'])->name('login');
Route::post('/login', [AuthenticationController::class, 'store'])->name('login.store');

Route::middleware('worklog.auth')->group(function (): void {
    Route::get('/', function () {
        $now = CarbonImmutable::now((string) config('app.timezone'));

        return view('app', [
            'defaultDate' => $now->format('Y-m-d'),
            'defaultTime' => $now->format('H:i'),
        ]);
    })->name('worklog');

    Route::post('/api/worklogs', WorklogController::class)->name('worklogs.store');
    Route::post('/logout', [AuthenticationController::class, 'destroy'])->name('logout');
});
