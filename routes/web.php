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
});

Route::middleware(['auth', 'admin'])->group(function () {
    // Admin Dashboard Home
    Route::get('/admin/dashboard', function () {
        return Inertia::render('Admin/Dashboard', [
            'stats' => [
                'totalMissingCases' => MissingReport::count(),
                // Placeholder for pending sightings until the sightings feature is implemented
                'pendingSightings' => 0,
                'totalUsers' => User::count(),
            ],
        ]);
    })->name('admin.dashboard');

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
    Route::get('/admin/sighting-reports', fn() => Inertia::render('Admin/ManageSightingReports'))
        ->name('admin.sighting-reports');
    Route::get('/admin/community-projects', fn() => Inertia::render('Admin/ManageCommunityProjects'))
        ->name('admin.community-projects');
    Route::get('/admin/rewards', fn() => Inertia::render('Admin/ManageRewards'))
        ->name('admin.rewards');
    Route::get('/admin/users', function () {
        $users = User::orderBy('created_at', 'desc')->limit(50)->get(['id','name','email','role','created_at','updated_at']);
        return Inertia::render('Admin/ManageUsers', [ 'users' => $users ]);
    })->name('admin.users');
    Route::post('/admin/users/{user}/role', function (\Illuminate\Http\Request $request, User $user) {
        $request->validate(['role' => 'required|in:user,volunteer,admin']);
        $user->role = $request->role;
        $user->save();
        return back();
    })->name('admin.users.role');
    Route::delete('/admin/users/{user}', function (User $user) {
        // prevent self-delete of the current admin
        if (auth()->id() === $user->id) {
            return back()->with('error', 'You cannot delete your own account.');
        }
        $user->delete();
        return back();
    })->name('admin.users.delete');
    Route::get('/admin/logs', fn() => Inertia::render('Admin/SystemLogs'))
        ->name('admin.logs');
});

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
    ->name('logout');

Route::get('/missing-persons/{id}/download-poster', [PosterController::class, 'downloadPoster'])
    ->name('missing-person.download-poster');




require __DIR__ . '/auth.php';
