<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MissingReportController;

Route::get('/test-connection', function () {
    return response()->json(['hello' => 'world']);
});

Route::get('/missing-persons', [MissingReportController::class, 'index']);

// User missing reports API
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user/missing-reports', [MissingReportController::class, 'userReports']);
});
