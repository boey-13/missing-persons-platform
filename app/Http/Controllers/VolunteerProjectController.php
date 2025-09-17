<?php

namespace App\Http\Controllers;

use App\Models\CommunityProject;
use App\Models\ProjectApplication; // 你的申请模型；如命名不同请改
use App\Models\User;
use App\Models\SystemLog;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log;

class VolunteerProjectController extends Controller
{
    // project list (Inertia render)
    public function index(Request $request)
    {
        $user = auth()->user();
        
        $query = CommunityProject::query()
            ->where('status', '!=', 'cancelled');
        
    
        if ($request->filled('category') && $request->category !== 'all') {
            $query->where('category', $request->category);
        }
        
        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }
        
        if ($request->filled('location')) {
            $query->where('location', 'like', '%' . $request->location . '%');
        }
        
        $projects = $query->orderBy('date', 'asc')->paginate(8)
            ->through(function ($project) use ($user) {
                // add user's application status for each project
                if ($user) {
                    $application = ProjectApplication::where('user_id', $user->id)
                        ->where('community_project_id', $project->id)
                        ->first();
                    
                    $project->user_application_status = $application ? $application->status : null;
                    $project->user_application = $application;
                } else {
                    $project->user_application_status = null;
                    $project->user_application = null;
                }
                
                // ensure photo_url is set correctly
                $project->photo_url = $project->photo_url;
                
                // static set photo_url to ensure frontend receives it
                if ($project->photo_paths && count($project->photo_paths) > 0) {
                    $project->photo_url = asset('storage/' . $project->photo_paths[0]);
                } else {
                    $project->photo_url = null;
                }
                
                // add is_full, available_spots, progress_percentage
                $project->is_full = $project->isFull();
                $project->available_spots = $project->available_spots;
                $project->progress_percentage = $project->progress_percentage;
                
                return $project;
            });

        // return Inertia response
        return inertia('Volunteer/Projects', [
            'projects' => $projects,
        ]);
    }


    public function show(CommunityProject $project)
    {
        return inertia('ProjectShow', [
            'project' => $project,
        ]);
    }


    public function apply(Request $request, CommunityProject $project)
    {
        $user = $request->user();
        if (!$user) {
            return back()->with('error', 'Please sign in to apply.');
        }

        // Check if user is an approved volunteer
        if (!$user->isApprovedVolunteer()) {
            return back()->with('error', 'You must be an approved volunteer to apply for projects.');
        }


        $request->validate([
            'experience' => 'required|string|min:10|max:1000',
            'motivation' => 'required|string|min:10|max:500',
        ], [
            'experience.min' => 'Experience must be at least 10 characters long.',
            'experience.max' => 'Experience must not exceed 1000 characters.',
            'motivation.min' => 'Motivation must be at least 10 characters long.',
            'motivation.max' => 'Motivation must not exceed 500 characters.',
        ]);

        try {

            if ($project->isFull()) {
                return back()->with('error', 'This project is full. No more applications are being accepted.');
            }

 
            if (!$request->has('ignore_conflicts')) {
                $conflictingProjects = $this->checkScheduleConflicts($user, $project);
                if ($conflictingProjects->isNotEmpty()) {
                    $conflictMessage = "You have a schedule conflict with the following project(s):\n";
                    foreach ($conflictingProjects as $conflictProject) {
                        $conflictMessage .= "• {$conflictProject->title} on {$conflictProject->date} at {$conflictProject->time}\n";
                    }
                    $conflictMessage .= "\nDo you want to continue with your application?";
                    
                    return back()->withErrors(['warning' => $conflictMessage]);
                }
            }

            // check if user has already applied (pending or approved)
            $existingApplication = ProjectApplication::where('user_id', $user->id)
                ->where('community_project_id', $project->id)
                ->whereIn('status', ['pending','approved'])
                ->first();

            if ($existingApplication) {
                return back()->with('error', 'You have already applied to this project.');
            }

            // if user had a rejected or withdrawn application before, update it instead of creating new
            $oldApplication = ProjectApplication::where('user_id', $user->id)
                ->where('community_project_id', $project->id)
                ->whereIn('status', ['rejected','withdrawn'])
                ->first();

            $application = null;
            if ($oldApplication) {

                $oldApplication->update([
                    'experience' => $request->experience,
                    'motivation' => $request->motivation,
                    'status' => 'pending',
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
                $application = $oldApplication;
            } else {

                $application = ProjectApplication::create([
                    'user_id' => $user->id,
                    'community_project_id' => $project->id,
                    'experience' => $request->experience,
                    'motivation' => $request->motivation,
                    'status' => 'pending',
                ]);
            }

            // send notification to user
            NotificationService::projectApplicationSubmitted($application);
            
            //  send notification to admins
            $adminUsers = User::where('role', 'admin')->get();
            foreach ($adminUsers as $admin) {
                NotificationService::send(
                    $admin->id,
                    'project_application_received',
                    'New Project Application',
                    "{$user->name} has applied for the project '{$project->title}'",
                    [
                        'action' => 'review_application',
                        'application_id' => $application->id,
                        'project_id' => $project->id,
                        'project_title' => $project->title,
                        'applicant_name' => $user->name,
                        'applicant_id' => $user->id
                    ]
                );
            }

            // record in SystemLog
            SystemLog::log(
                'project_application_submitted',
                "Volunteer {$user->name} applied for project: {$project->title}",
                [
                    'user_id' => $user->id,
                    'project_id' => $project->id,
                    'application_id' => $application->id,
                    'user_role' => $user->role
                ]
            );

            return back()->with('success', 'Application submitted successfully!');
        } catch (\Throwable $e) {
            Log::error('Apply failed', ['pid' => $project->id, 'e' => $e->getMessage()]);
            return back()->with('error', 'Failed to submit application. Please try again.');
        }
    }

    // cancel application
    public function withdraw(Request $request, CommunityProject $project)
    {
        $user = $request->user();
        if (!$user) {
            return back()->with('error', 'Please sign in first.');
        }

        try {
            $app = ProjectApplication::where('user_id', $user->id)
                ->where('community_project_id', $project->id)
                ->whereIn('status', ['pending','approved'])
                ->first();

            if (!$app) {
                return back()->with('error', 'No active application found.');
            }

            // if approved, decrement volunteers_joined
            if ($app->status === 'approved') {
                $project->decrement('volunteers_joined');
            }

            // mark application as withdrawn
            $app->status = 'withdrawn';
            $app->save();

            // send notification to user
            NotificationService::send(
                $user->id,
                'project_application_withdrawn',
                'Application Withdrawn',
                "You have withdrawn your application for '{$project->title}'",
                [
                    'action' => 'view_projects',
                    'project_id' => $project->id,
                    'project_title' => $project->title
                ]
            );

            // send notification to admins
            $adminUsers = User::where('role', 'admin')->get();
            foreach ($adminUsers as $admin) {
                NotificationService::send(
                    $admin->id,
                    'project_application_withdrawn',
                    'Application Withdrawn',
                    "{$user->name} has withdrawn their application for '{$project->title}'",
                    [
                        'action' => 'view_applications',
                        'application_id' => $app->id,
                        'project_id' => $project->id,
                        'project_title' => $project->title,
                        'applicant_name' => $user->name,
                        'applicant_id' => $user->id
                    ]
                );
            }

            // record in SystemLog
            SystemLog::log(
                'project_application_withdrawn',
                "Volunteer {$user->name} withdrew application for project: {$project->title}",
                [
                    'user_id' => $user->id,
                    'project_id' => $project->id,
                    'application_id' => $app->id,
                    'user_role' => $user->role,
                    'previous_status' => $app->status
                ]
            );

            return back()->with('success', 'Application withdrawn.');
        } catch (\Throwable $e) {
            Log::error('Withdraw failed', ['pid' => $project->id, 'uid' => $user->id ?? null, 'e' => $e->getMessage()]);
            return back()->with('error', 'Failed to withdraw application.');
        }
    }

    // get user's applications
    public function myApplications()
    {
        $user = auth()->user();
        if (!$user) {
            return back()->with('error', 'Please sign in to view your applications.');
        }

        $applications = ProjectApplication::where('user_id', $user->id)
            ->with(['project' => function ($query) {
                $query->select('id', 'title', 'location', 'date', 'status');
            }])
            ->orderBy('created_at', 'desc')
            ->get();

        return inertia('Volunteer/MyApplications', [
            'applications' => $applications,
        ]);
    }

    /**
     * Check for schedule conflicts with user's approved projects
     */
    private function checkScheduleConflicts($user, $newProject)
    {
        // Get user's approved projects
        $approvedApplications = ProjectApplication::where('user_id', $user->id)
            ->where('status', 'approved')
            ->with('project')
            ->get();

        $conflictingProjects = collect();

        foreach ($approvedApplications as $application) {
            $approvedProject = $application->project;
            if ($approvedProject && $newProject->hasScheduleConflictWith($approvedProject)) {
                $conflictingProjects->push($approvedProject);
            }
        }

        return $conflictingProjects;
    }


    public function toggleBookmark(Request $request, CommunityProject $project)
    {
        try {
            $user = $request->user();
            if (!$user) {
                return response()->json(['success' => false, 'message' => 'Please sign in.'], 401);
            }


            $bookmarked = false; 
            $msg = $bookmarked ? 'Added to bookmarks.' : 'Removed from bookmarks.';

            return response()->json(['success' => true, 'message' => $msg]);
        } catch (\Throwable $e) {
            Log::error('Toggle bookmark failed', ['pid' => $project->id, 'e' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Failed to update bookmark.'], 422);
        }
    }
}
