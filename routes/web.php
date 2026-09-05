<?php

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    $now = CarbonImmutable::now((string) config('app.timezone'));

    return view('app', [
        'defaultDate' => $now->format('Y-m-d'),
        'defaultTime' => $now->format('H:i'),
    ]);
});
