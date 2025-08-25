<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;
use App\Models\MissingReport;
use App\Models\SightingReport;
use App\Models\CommunityProject;
use App\Models\ProjectApplication;
use App\Models\SystemLog;
use App\Services\PointsService;

class ProfileController extends Controller
{
    protected $pointsService;

    public function __construct(PointsService $pointsService)
    {
        $this->pointsService = $pointsService;
    }

    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): Response
    {
        $user = $request->user();
        
        // Get user's sighting reports
        $sightingReports = SightingReport::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();

        // Get user's community projects (if volunteer)
        $communityProjects = collect();
        if ($user->role === 'volunteer') {
            $projectApplications = ProjectApplication::where('user_id', $user->id)
                ->with('project')
                ->get();
            
            $communityProjects = $projectApplications->map(function ($application) {
                return $application->project;
            })->filter();
        }

        // Calculate total points using the points service
        $totalPoints = $this->pointsService->getCurrentPoints($user);

        // Get points history using the points service
        $pointsHistory = $this->pointsService->getPointsHistory($user);

        return Inertia::render('Profile/Index', [
            'user' => $user,
            'sightingReports' => $sightingReports,
            'communityProjects' => $communityProjects,
            'totalPoints' => $totalPoints,
            'pointsHistory' => $pointsHistory,
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();
        
        // Debug: Log the incoming request data
        \Log::info('Profile update request data:', [
            'all_data' => $request->all(),
            'has_file' => $request->hasFile('avatar'),
            'name' => $request->input('name'),
            'email' => $request->input('email'),
            'phone' => $request->input('phone'),
            'method' => $request->method(),
            'url' => $request->url()
        ]);
        
        // Validate the request
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'phone' => 'nullable|string|max:20',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        // Handle avatar upload
        if ($request->hasFile('avatar')) {
            // Delete old avatar if exists
            if ($user->avatar_url && file_exists(public_path($user->avatar_url))) {
                unlink(public_path($user->avatar_url));
            }
            
            // Store new avatar
            $avatarPath = $request->file('avatar')->store('avatars', 'public');
            $user->avatar_url = '/storage/' . $avatarPath;
        }

        // Update user data
        $user->fill([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? $user->phone
        ]);

        // Handle email change
        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        // Log the profile update
        \Log::info('Profile updated for user: ' . $user->id);

        return redirect()->back()->with('status', 'Profile updated successfully.');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }


}
