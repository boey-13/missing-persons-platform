<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Illuminate\Support\Facades\Password;
use App\Http\Controllers\MissingReportController;
use App\Http\Controllers\PosterController;
use App\Models\MissingReport;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\SightingReportController;
use App\Http\Controllers\VolunteerApplicationController;
use Illuminate\Http\Request as HttpRequest;

Route::get('/', function () {
    $recent = \App\Models\MissingReport::whereIn('case_status', ['Approved', 'Missing'])
        ->orderByDesc('created_at')
        ->limit(8)
        ->get([
            'id','full_name','age','last_seen_location','photo_paths','created_at'
        ]);
    return Inertia::render('Home', [
        'recent' => $recent,
    ]);
});

// About Us and Contact Us pages
Route::get('/about', function () {
    return Inertia::render('AboutUs');
})->name('about');

Route::get('/contact', function () {
    return Inertia::render('ContactUs');
})->name('contact');

Route::get('/missing-persons', function() {
    return Inertia::render('MissingPersons/Index');
});

Route::post('/missing-persons', [MissingReportController::class, 'store'])->name('missing-persons.store');


Route::get('/missing-persons/report', function () {
    return Inertia::render('MissingPersons/ReportMissingPerson');
})->name('missing-persons.report');


Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::post('/profile', [ProfileController::class, 'update'])->name('profile.update.post');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Volunteer application & home
    Route::get('/volunteer/apply', [VolunteerApplicationController::class, 'create'])->name('volunteer.apply');
    Route::post('/volunteer/apply', [VolunteerApplicationController::class, 'store'])->name('volunteer.apply.store');
    Route::get('/volunteer/application-pending', [VolunteerApplicationController::class, 'create'])->name('volunteer.application-pending');
    Route::get('/volunteer', [VolunteerApplicationController::class, 'home'])->name('volunteer.home');

    // Volunteer community projects (only approved volunteers)
    Route::get('/volunteer/projects', function () {
        $user = auth()->user();
        if (!$user) {
            return redirect()->route('login');
        }
        $hasApproved = \App\Models\VolunteerApplication::where('user_id', $user->id)
            ->where('status', 'Approved')
            ->exists();
        if (!$hasApproved) {
            return redirect()->route('volunteer.apply')->with('status', 'Only approved volunteers can access Community Projects.');
        }
        return app(\App\Http\Controllers\VolunteerProjectController::class)->index(request());
    })->name('volunteer.projects');
    
    // Volunteer project applications
    Route::post('/volunteer/projects/{project}/apply', [\App\Http\Controllers\VolunteerProjectController::class, 'apply'])
        ->middleware(['auth'])
        ->name('volunteer.projects.apply');
    Route::get('/volunteer/my-applications', [\App\Http\Controllers\VolunteerProjectController::class, 'myApplications'])
        ->middleware(['auth'])
        ->name('volunteer.my-applications');

    // Lightweight notifications feed (JSON)
    Route::get('/notifications', function (HttpRequest $request) {
        $user = $request->user();
        if (!$user) {
            return response()->json([]);
        }
        
        $notifications = \App\Models\Notification::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get(['id', 'type', 'title', 'message', 'data', 'read_at', 'created_at']);
            
        return response()->json($notifications);
    })->name('notifications');

    // Social share API
    Route::post('/api/social-share', [\App\Http\Controllers\RewardController::class, 'recordSocialShare'])
        ->name('api.social-share');

    Route::post('/notifications/read', function (HttpRequest $request) {
        $user = $request->user();
        if (!$user) return response()->json(['ok' => false]);
        $ids = $request->input('ids', []);
        \App\Models\Notification::where('user_id', $user->id)
            ->whereIn('id', $ids)
            ->update(['read_at' => now()]);
        return response()->json(['ok' => true]);
    })->name('notifications.read');
});

// Admin routes group with AdminMiddleware protection
Route::middleware(['auth', \App\Http\Middleware\AdminMiddleware::class])->prefix('admin')->name('admin.')->group(function () {
    // Admin Dashboard
    Route::get('/dashboard', [App\Http\Controllers\AdminController::class, 'dashboard'])
        ->name('dashboard');

    // Manage Missing Person Reports
    Route::get('/missing-reports', [App\Http\Controllers\AdminController::class, 'missingReports'])
        ->name('missing-reports');
    
    Route::get('/missing-reports/{id}', [App\Http\Controllers\AdminController::class, 'showMissingReport'])
        ->name('missing-reports.show');
    Route::get('/missing-reports/{id}/sightings', function($id) {
        return redirect()->route('admin.sighting-reports', ['missing_report_id' => $id]);
    })->name('missing-reports.sightings');
    
    Route::get('/missing-reports/{id}/create-project', [App\Http\Controllers\CommunityProjectController::class, 'createFromMissingReport'])
        ->name('missing-reports.create-project');
    
    Route::post('/missing-reports/{id}/status', [App\Http\Controllers\AdminController::class, 'updateMissingReportStatus'])
        ->name('missing-reports.status');
    
    Route::put('/missing-reports/{id}', [App\Http\Controllers\AdminController::class, 'updateMissingReport'])
        ->name('missing-reports.update');

    // Placeholders for other admin pages
    Route::get('/sighting-reports', [App\Http\Controllers\SightingReportController::class, 'adminIndex'])
        ->name('sighting-reports');
    Route::post('/sighting-reports/{sighting}/status', [App\Http\Controllers\SightingReportController::class, 'updateStatus'])
        ->name('sighting-reports.status');
    Route::get('/sighting-reports/{sighting}', [App\Http\Controllers\SightingReportController::class, 'show'])
        ->name('sighting-reports.show');
        
    // Admin: Community Projects Management
    Route::get('/community-projects', [\App\Http\Controllers\CommunityProjectController::class, 'index'])
        ->name('community-projects');
    Route::post('/community-projects', [\App\Http\Controllers\CommunityProjectController::class, 'store'])
        ->name('community-projects.store');
    Route::put('/community-projects/{project}', [\App\Http\Controllers\CommunityProjectController::class, 'update'])
        ->name('community-projects.update');
    Route::delete('/community-projects/{project}', [\App\Http\Controllers\CommunityProjectController::class, 'destroy'])
        ->name('community-projects.destroy');
    
    // Admin: Project Applications Management
    Route::get('/community-projects/applications', [\App\Http\Controllers\CommunityProjectController::class, 'getApplications'])
        ->name('community-projects.applications');
    Route::post('/community-projects/applications/{application}/approve', [\App\Http\Controllers\CommunityProjectController::class, 'approveApplication'])
        ->name('community-projects.applications.approve');
    Route::post('/community-projects/applications/{application}/reject', [\App\Http\Controllers\CommunityProjectController::class, 'rejectApplication'])
        ->name('community-projects.applications.reject');
    
    // Admin: Project Status Management
    Route::post('/community-projects/{project}/status', [\App\Http\Controllers\CommunityProjectController::class, 'updateStatus'])
        ->name('community-projects.status');

    // Admin: Project News Management
    Route::post('/community-projects/{project}/update-news', [\App\Http\Controllers\CommunityProjectController::class, 'updateNews'])
        ->name('community-projects.update-news');
    Route::delete('/community-projects/{project}/news/{newsId}', [\App\Http\Controllers\CommunityProjectController::class, 'deleteNews'])
        ->name('community-projects.delete-news');

    // Admin Rewards Routes
    Route::get('/rewards', [App\Http\Controllers\AdminRewardController::class, 'index'])
        ->name('rewards');
    
    Route::get('/rewards/create', [App\Http\Controllers\AdminRewardController::class, 'create'])
        ->name('rewards.create');
    
    Route::post('/rewards', [App\Http\Controllers\AdminRewardController::class, 'store'])
        ->name('rewards.store');
    
    Route::get('/rewards/{id}/edit', [App\Http\Controllers\AdminRewardController::class, 'edit'])
        ->name('rewards.edit');
    
    Route::put('/rewards/{id}', [App\Http\Controllers\AdminRewardController::class, 'update'])
        ->name('rewards.update');
    
    Route::delete('/rewards/{id}', [App\Http\Controllers\AdminRewardController::class, 'destroy'])
        ->name('rewards.destroy');
    
    Route::get('/rewards/categories', [App\Http\Controllers\AdminRewardController::class, 'categories'])
        ->name('rewards.categories');
    
    Route::post('/rewards/categories', [App\Http\Controllers\AdminRewardController::class, 'storeCategory'])
        ->name('rewards.categories.store');
    
    Route::put('/rewards/categories/{id}', [App\Http\Controllers\AdminRewardController::class, 'updateCategory'])
        ->name('rewards.categories.update');
    
    Route::delete('/rewards/categories/{id}', [App\Http\Controllers\AdminRewardController::class, 'destroyCategory'])
        ->name('rewards.categories.destroy');
    
    Route::get('/rewards/stats', [App\Http\Controllers\AdminRewardController::class, 'stats'])
        ->name('rewards.stats');

    // Admin: manage volunteer applications
    Route::get('/volunteers', [\App\Http\Controllers\VolunteerApplicationController::class, 'adminIndex'])
        ->name('volunteers');
    Route::post('/volunteers/{application}/status', [\App\Http\Controllers\VolunteerApplicationController::class, 'updateStatus'])
        ->name('volunteers.status');

    Route::get('/users', function () {
        $users = User::orderBy('created_at', 'desc')->limit(50)->get(['id','name','email','role','created_at','updated_at']);
        return Inertia::render('Admin/ManageUsers', [ 'users' => $users ]);
    })->name('users');

    // Test route for applications
    Route::get('/test-applications', function () {
        return Inertia::render('Admin/TestApplications');
    })->name('test.applications');

    // Debug route for authentication
    Route::get('/debug-auth', function () {
        return Inertia::render('Admin/DebugAuth');
    })->name('debug.auth');

    Route::post('/users/{user}/role', function (\Illuminate\Http\Request $request, User $user) {
        $request->validate(['role' => 'required|in:user,volunteer,admin']);
        $oldRole = $user->role;
        $user->role = $request->role;
        $user->save();

        // Log the role change
        \App\Models\SystemLog::log(
            'user_role_changed',
            "User role changed from {$oldRole} to {$request->role}",
            ['user_id' => $user->id, 'user_email' => $user->email, 'old_role' => $oldRole, 'new_role' => $request->role]
        );

        return back();
    })->name('users.role');

    Route::delete('/users/{user}', function (User $user) {
        // prevent self-delete of the current admin
        if (auth()->id() === $user->id) {
            return back()->with('error', 'You cannot delete your own account.');
        }

        // Log the user deletion
        \App\Models\SystemLog::log(
            'user_deleted',
            "User account deleted: {$user->email}",
            ['deleted_user_id' => $user->id, 'deleted_user_email' => $user->email, 'deleted_user_role' => $user->role]
        );

        $user->delete();
        return back();
    })->name('users.delete');
    
    Route::get('/logs', [App\Http\Controllers\SystemLogController::class, 'index'])
        ->name('logs');
});

// Community Project Details (for both admin and volunteers) - outside admin group
Route::get('/community-projects/{project}', [\App\Http\Controllers\CommunityProjectController::class, 'show'])
    ->middleware(['auth'])
    ->name('community-projects.show');

// User Rewards Routes
Route::get('/rewards', [App\Http\Controllers\RewardController::class, 'index'])
    ->middleware(['auth'])
    ->name('rewards.index');

Route::get('/rewards/my-vouchers', [App\Http\Controllers\RewardController::class, 'myVouchers'])
    ->middleware(['auth'])
    ->name('rewards.my-vouchers');

Route::get('/rewards/{id}', [App\Http\Controllers\RewardController::class, 'show'])
    ->middleware(['auth'])
    ->name('rewards.show');

Route::post('/rewards/{id}/redeem', [App\Http\Controllers\RewardController::class, 'redeem'])
    ->middleware(['auth'])
    ->name('rewards.redeem');

Route::get('/rewards/points/history', [App\Http\Controllers\RewardController::class, 'pointsHistory'])
    ->middleware(['auth'])
    ->name('rewards.points.history');

Route::post('/social-share', [App\Http\Controllers\RewardController::class, 'recordSocialShare'])
    ->middleware(['auth'])
    ->name('social.share');

Route::get('/vouchers/{id}/qr-code', [App\Http\Controllers\RewardController::class, 'getVoucherQrCode'])
    ->middleware(['auth'])
    ->name('vouchers.qr-code');

Route::post('/forgot-password', function (Illuminate\Http\Request $request) {
    $request->validate(['email' => 'required|email']);

    $status = Password::sendResetLink(
        $request->only('email')
    );

    return $status === Password::RESET_LINK_SENT
        ? back()->with(['status' => __($status)])
        : back()->withErrors(['email' => __($status)]);
})->name('password.email');

Route::get('/missing-persons/{id}', [MissingReportController::class, 'show'])->name('missing-persons.show');

Route::get('/missing-persons/{id}/preview-poster', function ($id) {
    $report = \App\Models\MissingReport::findOrFail($id);
    return Inertia::render('MissingPersons/PreviewPoster', ['report' => $report]);
})->name('missing-person.preview-poster');

Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
    ->middleware('log.activity')
    ->name('logout');

Route::get('/missing-persons/{id}/download-poster', [PosterController::class, 'downloadPoster'])
    ->name('missing-person.download-poster');

// Sighting reports tied to a specific missing report
Route::get('/missing-persons/{id}/report-sighting', [SightingReportController::class, 'create'])
    ->name('sightings.create');
Route::post('/missing-persons/{id}/sightings', [SightingReportController::class, 'store'])
    ->name('sightings.store');

    // Missing Person Reports API
    Route::get('/api/missing-persons', [App\Http\Controllers\MissingReportController::class, 'index']);
    Route::get('/api/user/missing-reports', [App\Http\Controllers\MissingReportController::class, 'userReports'])
        ->middleware(['auth']);


require __DIR__ . '/auth.php';
