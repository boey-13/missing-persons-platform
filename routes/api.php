<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MissingReportController;
use App\Http\Controllers\SocialShareController;

Route::get('/test-connection', function () {
    return response()->json(['hello' => 'world']);
});

Route::get('/missing-persons', [MissingReportController::class, 'index']);

// User missing reports API
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user/missing-reports', [MissingReportController::class, 'userReports']);
});

// Social share endpoint
Route::post('/social-share', [SocialShareController::class, 'store']);

// Search missing persons for chatbot
Route::get('/search-missing-persons', function (Request $request) {
    $query = $request->get('q', '');
    
    if (empty($query)) {
        return response()->json(['results' => []]);
    }
    
    $results = \App\Models\MissingReport::where('case_status', 'Approved')
        ->where(function($q) use ($query) {
            $q->where('full_name', 'LIKE', "%{$query}%")
              ->orWhere('last_seen_location', 'LIKE', "%{$query}%")
              ->orWhere('age', 'LIKE', "%{$query}%")
              ->orWhere('gender', 'LIKE', "%{$query}%");
        })
        ->limit(5)
        ->get(['id', 'full_name', 'age', 'gender', 'last_seen_location', 'photo_paths'])
        ->map(function($report) {
            $report->photo_url = $report->photo_paths && count($report->photo_paths) > 0 
                ? asset('storage/' . $report->photo_paths[0]) 
                : null;
            return $report;
        });
    
    return response()->json(['results' => $results]);
});
