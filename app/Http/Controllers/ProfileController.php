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

class ProfileController extends Controller
{
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

        // Calculate total points (this would need to be implemented based on your points system)
        $totalPoints = $this->calculateUserPoints($user);

        // Get points history (this would need to be implemented based on your points system)
        $pointsHistory = $this->getUserPointsHistory($user);

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

    /**
     * Calculate user's total points
     */
    private function calculateUserPoints($user): int
    {
        $points = 0;

        // Points for missing reports (5 points each)
        $missingReportsCount = MissingReport::where('user_id', $user->id)->count();
        $points += $missingReportsCount * 5;

        // Points for approved sighting reports (10 points each)
        $approvedSightingReportsCount = SightingReport::where('user_id', $user->id)
            ->where('status', 'approved')
            ->count();
        $points += $approvedSightingReportsCount * 10;

        // Points for completed community projects
        if ($user->role === 'volunteer') {
            $completedProjects = ProjectApplication::where('user_id', $user->id)
                ->where('status', 'approved')
                ->with('project')
                ->get();

            foreach ($completedProjects as $application) {
                if ($application->project) {
                    $points += $application->project->points_reward ?? 0;
                }
            }
        }

        // Bonus points for registration (10 points)
        $points += 10;

        return $points;
    }

    /**
     * Get user's points history
     */
    private function getUserPointsHistory($user): array
    {
        $history = [];

        // Add registration bonus
        $history[] = [
            'id' => 'reg_' . $user->id,
            'description' => 'Account registration bonus',
            'points' => 10,
            'created_at' => $user->created_at
        ];

        // Add missing reports
        $missingReports = MissingReport::where('user_id', $user->id)->get();
        foreach ($missingReports as $report) {
            $history[] = [
                'id' => 'missing_' . $report->id,
                'description' => "Submitted missing person report for {$report->full_name}",
                'points' => 5,
                'created_at' => $report->created_at
            ];
        }

        // Add approved sighting reports
        $sightingReports = SightingReport::where('user_id', $user->id)
            ->where('status', 'approved')
            ->get();
        foreach ($sightingReports as $report) {
            $history[] = [
                'id' => 'sighting_' . $report->id,
                'description' => 'Submitted approved sighting report',
                'points' => 10,
                'created_at' => $report->created_at
            ];
        }

        // Add community project points
        if ($user->role === 'volunteer') {
            $projectApplications = ProjectApplication::where('user_id', $user->id)
                ->where('status', 'approved')
                ->with('project')
                ->get();

            foreach ($projectApplications as $application) {
                if ($application->project) {
                    $history[] = [
                        'id' => 'project_' . $application->id,
                        'description' => "Completed community project: {$application->project->title}",
                        'points' => $application->project->points_reward ?? 0,
                        'created_at' => $application->updated_at
                    ];
                }
            }
        }

        // Sort by date (newest first)
        usort($history, function ($a, $b) {
            return strtotime($b['created_at']) - strtotime($a['created_at']);
        });

        return $history;
    }
}
