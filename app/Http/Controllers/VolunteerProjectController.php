<?php

namespace App\Http\Controllers;

use App\Models\CommunityProject;
use App\Models\ProjectApplication;
use App\Models\SystemLog;
use Illuminate\Http\Request;
use Inertia\Inertia;

class VolunteerProjectController extends Controller
{
    public function index()
    {
        $projects = CommunityProject::where('status', '!=', 'cancelled')
            ->orderBy('date', 'asc')
            ->get()
            ->map(function ($project) {
                $userApplication = ProjectApplication::where('user_id', auth()->id())
                    ->where('community_project_id', $project->id)
                    ->first();
                
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
                    'user_application_status' => $userApplication ? $userApplication->status : null,
                    'user_application' => $userApplication,
                    'created_at' => $project->created_at,
                    'updated_at' => $project->updated_at,
                ];
            });

        return Inertia::render('Volunteer/Projects', [
            'projects' => $projects
        ]);
    }

    public function apply(Request $request, CommunityProject $project)
    {
        $request->validate([
            'experience' => 'required|string|min:10',
            'motivation' => 'required|string|min:10'
        ]);

        // Check if user already applied to this project
        $existingApplication = ProjectApplication::where('user_id', auth()->id())
            ->where('community_project_id', $project->id)
            ->first();

        if ($existingApplication) {
            if ($existingApplication->status === 'pending') {
                return back()->with('error', 'You have already applied to this project and your application is pending review.');
            } elseif ($existingApplication->status === 'approved') {
                return back()->with('error', 'You have already been approved for this project.');
            } elseif ($existingApplication->status === 'rejected') {
                // Allow re-application if previously rejected
                $existingApplication->update([
                    'experience' => $request->experience,
                    'motivation' => $request->motivation,
                    'status' => 'pending',
                    'rejection_reason' => null,
                    'rejected_at' => null
                ]);

                SystemLog::log(
                    'project_application_resubmitted',
                    'Re-applied to project: ' . $project->title,
                    auth()->user()->id
                );

                return back()->with('success', 'Application resubmitted successfully!');
            }
        }

        // Create new application
        $application = ProjectApplication::create([
            'user_id' => auth()->id(),
            'community_project_id' => $project->id,
            'experience' => $request->experience,
            'motivation' => $request->motivation,
            'status' => 'pending'
        ]);

        SystemLog::log(
            'project_application_submitted',
            'Applied to project: ' . $project->title,
            auth()->user()->id
        );

        return back()->with('success', 'Application submitted successfully!');
    }

    public function myApplications()
    {
        $applications = ProjectApplication::with('project')
            ->where('user_id', auth()->id())
            ->orderBy('created_at', 'desc')
            ->get();

        return Inertia::render('Volunteer/MyApplications', [
            'applications' => $applications
        ]);
    }
}
