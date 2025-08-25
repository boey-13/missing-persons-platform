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
    $recent = \App\Models\MissingReport::orderByDesc('created_at')->limit(8)->get([
        'id','full_name','age','last_seen_location','photo_paths','created_at'
    ]);
    return Inertia::render('Home', [
        'recent' => $recent,
    ]);
});

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
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Volunteer application & home
    Route::get('/volunteer/apply', [VolunteerApplicationController::class, 'create'])->name('volunteer.apply');
    Route::post('/volunteer/apply', [VolunteerApplicationController::class, 'store'])->name('volunteer.apply.store');
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
        return app(\App\Http\Controllers\VolunteerProjectController::class)->index();
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
        if (!$user) return response()->json([]);
        $items = \App\Models\Notification::where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->limit(20)
            ->get(['id','title','message','data','created_at','read_at']);
        return response()->json($items);
    })->name('notifications.index');

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

    // Admin Dashboard
    Route::get('/admin/dashboard', [App\Http\Controllers\AdminController::class, 'dashboard'])
        ->middleware(['auth'])
        ->name('admin.dashboard');

    // Manage Missing Person Reports
    Route::get('/admin/missing-reports', [App\Http\Controllers\AdminController::class, 'missingReports'])
        ->middleware(['auth'])
        ->name('admin.missing-reports');
    
    Route::get('/admin/missing-reports/{id}', [App\Http\Controllers\AdminController::class, 'showMissingReport'])
        ->middleware(['auth'])
        ->name('admin.missing-reports.show');
    Route::get('/admin/missing-reports/{id}/sightings', function($id) {
        return redirect()->route('admin.sighting-reports', ['missing_report_id' => $id]);
    })->middleware(['auth'])->name('admin.missing-reports.sightings');
    
    Route::get('/admin/missing-reports/{id}/create-project', [App\Http\Controllers\CommunityProjectController::class, 'createFromMissingReport'])
        ->middleware(['auth'])
        ->name('admin.missing-reports.create-project');
    
    Route::post('/admin/missing-reports/{id}/status', [App\Http\Controllers\AdminController::class, 'updateMissingReportStatus'])
        ->middleware(['auth'])
        ->name('admin.missing-reports.status');
    
    Route::put('/admin/missing-reports/{id}', [App\Http\Controllers\AdminController::class, 'updateMissingReport'])
        ->middleware(['auth'])
        ->name('admin.missing-reports.update');

    // Placeholders for other admin pages
    Route::get('/admin/sighting-reports', [App\Http\Controllers\SightingReportController::class, 'adminIndex'])
        ->middleware(['auth'])
        ->name('admin.sighting-reports');
    Route::post('/admin/sighting-reports/{sighting}/status', [App\Http\Controllers\SightingReportController::class, 'updateStatus'])
        ->middleware(['auth'])
        ->name('admin.sighting-reports.status');
    Route::get('/admin/sighting-reports/{sighting}', [App\Http\Controllers\SightingReportController::class, 'show'])
        ->middleware(['auth'])
        ->name('admin.sighting-reports.show');
        
    // Admin: Community Projects Management
    Route::get('/admin/community-projects', [\App\Http\Controllers\CommunityProjectController::class, 'index'])
        ->middleware(['auth'])
        ->name('admin.community-projects');
    Route::post('/admin/community-projects', [\App\Http\Controllers\CommunityProjectController::class, 'store'])
        ->middleware(['auth'])
        ->name('admin.community-projects.store');
    Route::put('/admin/community-projects/{project}', [\App\Http\Controllers\CommunityProjectController::class, 'update'])
        ->middleware(['auth'])
        ->name('admin.community-projects.update');
    Route::delete('/admin/community-projects/{project}', [\App\Http\Controllers\CommunityProjectController::class, 'destroy'])
        ->middleware(['auth'])
        ->name('admin.community-projects.destroy');
    
    // Admin: Project Applications Management
    Route::get('/admin/community-projects/applications', [\App\Http\Controllers\CommunityProjectController::class, 'getApplications'])
        ->middleware(['auth'])
        ->name('admin.community-projects.applications');
    Route::post('/admin/community-projects/applications/{application}/approve', [\App\Http\Controllers\CommunityProjectController::class, 'approveApplication'])
        ->middleware(['auth'])
        ->name('admin.community-projects.applications.approve');
    Route::post('/admin/community-projects/applications/{application}/reject', [\App\Http\Controllers\CommunityProjectController::class, 'rejectApplication'])
        ->middleware(['auth'])
        ->name('admin.community-projects.applications.reject');

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

    // Admin Rewards Routes
    Route::get('/admin/rewards', [App\Http\Controllers\AdminRewardController::class, 'index'])
        ->middleware(['auth'])
        ->name('admin.rewards');
    
    Route::get('/admin/rewards/create', [App\Http\Controllers\AdminRewardController::class, 'create'])
        ->middleware(['auth'])
        ->name('admin.rewards.create');
    
    Route::post('/admin/rewards', [App\Http\Controllers\AdminRewardController::class, 'store'])
        ->middleware(['auth'])
        ->name('admin.rewards.store');
    
    Route::get('/admin/rewards/{id}/edit', [App\Http\Controllers\AdminRewardController::class, 'edit'])
        ->middleware(['auth'])
        ->name('admin.rewards.edit');
    
    Route::put('/admin/rewards/{id}', [App\Http\Controllers\AdminRewardController::class, 'update'])
        ->middleware(['auth'])
        ->name('admin.rewards.update');
    
    Route::delete('/admin/rewards/{id}', [App\Http\Controllers\AdminRewardController::class, 'destroy'])
        ->middleware(['auth'])
        ->name('admin.rewards.destroy');
    
    Route::get('/admin/rewards/categories', [App\Http\Controllers\AdminRewardController::class, 'categories'])
        ->middleware(['auth'])
        ->name('admin.rewards.categories');
    
    Route::post('/admin/rewards/categories', [App\Http\Controllers\AdminRewardController::class, 'storeCategory'])
        ->middleware(['auth'])
        ->name('admin.rewards.categories.store');
    
    Route::put('/admin/rewards/categories/{id}', [App\Http\Controllers\AdminRewardController::class, 'updateCategory'])
        ->middleware(['auth'])
        ->name('admin.rewards.categories.update');
    
    Route::delete('/admin/rewards/categories/{id}', [App\Http\Controllers\AdminRewardController::class, 'destroyCategory'])
        ->middleware(['auth'])
        ->name('admin.rewards.categories.destroy');
    
    Route::get('/admin/rewards/stats', [App\Http\Controllers\AdminRewardController::class, 'stats'])
        ->middleware(['auth'])
        ->name('admin.rewards.stats');

    // Admin: manage volunteer applications
    Route::get('/admin/volunteers', [\App\Http\Controllers\VolunteerApplicationController::class, 'adminIndex'])
        ->middleware(['auth'])
        ->name('admin.volunteers');
    Route::post('/admin/volunteers/{application}/status', [\App\Http\Controllers\VolunteerApplicationController::class, 'updateStatus'])
        ->middleware(['auth'])
        ->name('admin.volunteers.status');

    Route::get('/admin/users', function () {
        $users = User::orderBy('created_at', 'desc')->limit(50)->get(['id','name','email','role','created_at','updated_at']);
        return Inertia::render('Admin/ManageUsers', [ 'users' => $users ]);
    })->middleware(['auth'])->name('admin.users');

    Route::post('/admin/users/{user}/role', function (\Illuminate\Http\Request $request, User $user) {
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
    })->middleware(['auth'])->name('admin.users.role');

    Route::delete('/admin/users/{user}', function (User $user) {
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
    })->middleware(['auth'])->name('admin.users.delete');
    Route::get('/admin/logs', [App\Http\Controllers\SystemLogController::class, 'index'])
        ->middleware(['auth'])
        ->name('admin.logs');
// });

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
