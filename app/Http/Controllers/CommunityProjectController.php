<?php

namespace App\Http\Controllers;

use App\Models\CommunityProject;
use App\Models\ProjectApplication;
use App\Models\SystemLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class CommunityProjectController extends Controller
{
    public function index()
    {
        $projects = CommunityProject::withCount(['applications as total_applications'])
            ->withCount(['pendingApplications as pending_count'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($project) {
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
                    'total_applications' => $project->total_applications,
                    'pending_count' => $project->pending_count,
                    'created_at' => $project->created_at,
                    'updated_at' => $project->updated_at,
                ];
            });

        return Inertia::render('Admin/ManageCommunityProjects', [
            'projects' => $projects
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'location' => 'required|string|max:255',
            'date' => 'required|date',
            'time' => 'required',
            'duration' => 'required|string|max:100',
            'volunteers_needed' => 'required|integer|min:1',
            'points_reward' => 'required|integer|min:0',
            'category' => 'required|in:search,awareness,training',
            'status' => 'required|in:active,upcoming,completed,cancelled',
            'photos.*' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        $photoPaths = [];
        if ($request->hasFile('photos')) {
            foreach ($request->file('photos') as $photo) {
                $path = $photo->store('community-projects', 'public');
                $photoPaths[] = $path;
            }
        }

        $project = CommunityProject::create([
            'title' => $request->title,
            'description' => $request->description,
            'location' => $request->location,
            'date' => $request->date,
            'time' => $request->time,
            'duration' => $request->duration,
            'volunteers_needed' => $request->volunteers_needed,
            'points_reward' => $request->points_reward,
            'category' => $request->category,
            'status' => $request->status,
            'photo_paths' => $photoPaths
        ]);

        SystemLog::log(
            'project_created',
            'Created new community project: ' . $project->title,
            auth()->user()->id
        );

        return redirect()->back()->with('success', 'Project created successfully!');
    }

    public function update(Request $request, CommunityProject $project)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'location' => 'required|string|max:255',
            'date' => 'required|date',
            'time' => 'required',
            'duration' => 'required|string|max:100',
            'volunteers_needed' => 'required|integer|min:1',
            'points_reward' => 'required|integer|min:0',
            'category' => 'required|in:search,awareness,training',
            'status' => 'required|in:active,upcoming,completed,cancelled',
            'photos.*' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        $photoPaths = $project->photo_paths ?? [];
        if ($request->hasFile('photos')) {
            // Delete old photos
            if ($photoPaths) {
                foreach ($photoPaths as $path) {
                    Storage::disk('public')->delete($path);
                }
            }
            
            // Store new photos
            $photoPaths = [];
            foreach ($request->file('photos') as $photo) {
                $path = $photo->store('community-projects', 'public');
                $photoPaths[] = $path;
            }
        }

        $project->update([
            'title' => $request->title,
            'description' => $request->description,
            'location' => $request->location,
            'date' => $request->date,
            'time' => $request->time,
            'duration' => $request->duration,
            'volunteers_needed' => $request->volunteers_needed,
            'points_reward' => $request->points_reward,
            'category' => $request->category,
            'status' => $request->status,
            'photo_paths' => $photoPaths
        ]);

        SystemLog::log(
            'project_updated',
            'Updated community project: ' . $project->title,
            auth()->user()->id
        );

        return redirect()->back()->with('success', 'Project updated successfully!');
    }

    public function destroy(CommunityProject $project)
    {
        // Delete photos
        if ($project->photo_paths) {
            foreach ($project->photo_paths as $path) {
                Storage::disk('public')->delete($path);
            }
        }

        $projectTitle = $project->title;
        $project->delete();

        SystemLog::log(
            'project_deleted',
            'Deleted community project: ' . $projectTitle,
            auth()->user()->id
        );

        return redirect()->back()->with('success', 'Project deleted successfully!');
    }

    public function getApplications()
    {
        $applications = ProjectApplication::with(['user', 'project'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($application) {
                return [
                    'id' => $application->id,
                    'status' => $application->status,
                    'experience' => $application->experience,
                    'motivation' => $application->motivation,
                    'created_at' => $application->created_at,
                    'rejection_reason' => $application->rejection_reason,
                    'approved_at' => $application->approved_at,
                    'rejected_at' => $application->rejected_at,
                    
                    // User information
                    'volunteerName' => $application->user->name,
                    'email' => $application->user->email,
                    'phone' => $application->user->phone,
                    'user_id' => $application->user->id,
                    
                    // Project information
                    'projectTitle' => $application->project->title,
                    'projectLocation' => $application->project->location,
                    'projectDate' => $application->project->date,
                    'projectTime' => $application->project->time,
                    'projectDuration' => $application->project->duration,
                    'projectCategory' => $application->project->category,
                    'projectStatus' => $application->project->status,
                    'projectPoints' => $application->project->points_reward,
                    'projectVolunteersNeeded' => $application->project->volunteers_needed,
                    'projectVolunteersJoined' => $application->project->volunteers_joined,
                ];
            });

        return response()->json($applications);
    }

    public function approveApplication(ProjectApplication $application)
    {
        $application->update([
            'status' => 'approved',
            'approved_at' => now()
        ]);

        // Update project volunteer count
        $project = $application->project;
        $project->increment('volunteers_joined');

        SystemLog::log(
            'application_approved',
            'Approved application for project: ' . $project->title . ' by ' . $application->user->name,
            auth()->user()->id
        );

        return response()->json(['success' => true]);
    }

    public function rejectApplication(Request $request, ProjectApplication $application)
    {
        $application->update([
            'status' => 'rejected',
            'rejected_at' => now(),
            'rejection_reason' => $request->rejection_reason
        ]);

        SystemLog::log(
            'application_rejected',
            'Rejected application for project: ' . $application->project->title . ' by ' . $application->user->name,
            auth()->user()->id
        );

        return response()->json(['success' => true]);
    }
}
