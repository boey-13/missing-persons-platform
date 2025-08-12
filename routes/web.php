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

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
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
        if (!$user || $user->role !== 'volunteer') {
            return redirect()->route('volunteer.apply')->with('status', 'Only approved volunteers can access Community Projects.');
        }
        return Inertia::render('Volunteer/Projects');
    })->name('volunteer.projects');
});

    // Admin Dashboard
    Route::get('/admin/dashboard', function () {
        // minimal example stats; adjust as needed
        $stats = [
            'totalMissingCases' => \App\Models\MissingReport::count(),
            'pendingSightings' => \App\Models\SightingReport::where('status', 'Pending')->count(),
            'totalUsers' => \App\Models\User::count(),
        ];
        return Inertia::render('Admin/Dashboard', [ 'stats' => $stats ]);
    })->middleware(['auth'])->name('admin.dashboard');

    // Manage Missing Person Reports
    Route::get('/admin/missing-reports', function () {
        $cases = MissingReport::orderBy('created_at', 'desc')->paginate(15);
        $data = $cases->getCollection()->transform(function ($item) {
            return [
                'id' => $item->id,
                'name' => $item->full_name,
                'reporter' => $item->reporter_name,
                'created_at' => $item->created_at?->toDateTimeString(),
                'last_seen' => $item->last_seen_location,
                'status' => $item->case_status,
            ];
        });
        return Inertia::render('Admin/ManageMissingReports', [
            'items' => $data,
            'pagination' => [
                'total' => $cases->total(),
                'current_page' => $cases->currentPage(),
                'per_page' => $cases->perPage(),
            ],
        ]);
    })->name('admin.missing-reports');

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
        
    Route::get('/admin/community-projects', fn() => Inertia::render('Admin/ManageCommunityProjects'))
        ->middleware(['auth'])
        ->name('admin.community-projects');

    Route::get('/admin/rewards', fn() => Inertia::render('Admin/ManageRewards'))
        ->middleware(['auth'])
        ->name('admin.rewards');

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




require __DIR__ . '/auth.php';
