<?php

use App\Http\Controllers\WorklogController;
use Illuminate\Support\Facades\Route;

Route::post('/worklogs', WorklogController::class);
