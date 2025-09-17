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

        // Get user's community projects (for admin and volunteer only)
        $communityProjects = collect();
        if ($user->role !== 'user') {
            // Only get active applications (pending and approved)
            $projectApplications = ProjectApplication::where('user_id', $user->id)
                ->whereIn('status', ['pending', 'approved'])
                ->with('project')
                ->get();
            
            $communityProjects = $projectApplications->map(function ($application) {
                $project = $application->project;
                if ($project) {
                    return [
                        'id' => $project->id,
                        'title' => $project->title,
                        'description' => $project->description,
                        'location' => $project->location,
                        'date' => $project->date,
                        'time' => $project->time,
                        'duration' => $project->duration,
                        'volunteers_needed' => $project->volunteers_needed,
                        'volunteers_joined' => $project->volunteers_joined,
                        'points_reward' => $project->points_reward,
                        'category' => $project->category,
                        'status' => $project->status,
                        'photo_paths' => $project->photo_paths,
                        'photo_url' => $project->photo_url,
                        'created_at' => $project->created_at,
                        'updated_at' => $project->updated_at,

                        'application_id' => $application->id,
                        'application_status' => $application->status,
                        'application_created_at' => $application->created_at,
                        'application_updated_at' => $application->updated_at,
                        'experience' => $application->experience,
                        'motivation' => $application->motivation,
                    ];
                }
                return null;
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
            'region' => $request->input('region'),
            'method' => $request->method(),
            'url' => $request->url(),
            'user_id' => $user->id,
            'content_type' => $request->header('Content-Type'),
            'files' => $request->allFiles()
        ]);
        
        try {
            // Validate the request
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email,' . $user->id,
                'phone' => 'nullable|string|max:32',
                'region' => 'nullable|string|max:255',
                'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120'
            ]);

            \Log::info('Validation passed', $validated);

            if (!empty($validated['phone'])) {
                $validated['phone'] = preg_replace('/[^0-9+]/', '', $validated['phone']);
            }

            // Handle avatar upload
            if ($request->hasFile('avatar')) {
                \Log::info('Processing avatar upload');
                // Delete old avatar if exists
                if ($user->avatar_url && file_exists(public_path($user->avatar_url))) {
                    unlink(public_path($user->avatar_url));
                }
                
                // Store new avatar
                $avatarPath = $request->file('avatar')->store('avatars', 'public');
                $user->avatar_url = '/storage/' . $avatarPath;
                \Log::info('Avatar uploaded to: ' . $user->avatar_url);
            }

            // Update user data
            $user->fill([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'] ?? $user->phone,
                'region' => $validated['region'] ?? $user->region
            ]);


            $user->save();

            // record changes in SystemLog
            $changedFields = [];
            if ($user->wasChanged('name')) $changedFields[] = 'name';
            if ($user->wasChanged('email')) $changedFields[] = 'email';
            if ($user->wasChanged('phone')) $changedFields[] = 'phone';
            if ($user->wasChanged('region')) $changedFields[] = 'region';
            if ($user->wasChanged('avatar_url')) $changedFields[] = 'avatar';

            if (!empty($changedFields)) {
                SystemLog::log(
                    'profile_updated',
                    "User {$user->name} updated profile: " . implode(', ', $changedFields),
                    [
                        'user_id' => $user->id,
                        'user_role' => $user->role,
                        'changed_fields' => $changedFields,
                        'old_email' => $user->getOriginal('email'),
                        'new_email' => $user->email
                    ]
                );
            }

            \Log::info('Profile updated successfully for user: ' . $user->id, [
                'updated_fields' => [
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'region' => $user->region
                ]
            ]);

            return redirect()->back()->with('success', 'Profile updated successfully.');
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('Profile update validation failed: ' . $e->getMessage(), [
                'user_id' => $user->id,
                'errors' => $e->errors()
            ]);
            
            return redirect()->back()->withErrors($e->errors());
        } catch (\Exception $e) {
            \Log::error('Profile update failed: ' . $e->getMessage(), [
                'user_id' => $user->id,
                'exception' => $e
            ]);
            
            return redirect()->back()->withErrors(['error' => 'Profile update failed: ' . $e->getMessage()]);
        }
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
