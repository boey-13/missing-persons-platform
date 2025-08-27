<?php

namespace App\Http\Controllers;

use App\Models\CommunityProject;
use App\Models\ProjectApplication;
use App\Services\NotificationService;
use App\Services\PointsService;
use App\Models\SystemLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class CommunityProjectController extends Controller
{
    protected $pointsService;

    public function __construct(PointsService $pointsService)
    {
        $this->pointsService = $pointsService;
    }

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

        // Send notifications
        NotificationService::projectCreated($project);

        SystemLog::log(
            'project_created',
            'Created new community project: ' . $project->title,
            auth()->user()->id
        );

        return redirect()->route('admin.community-projects')
            ->with('success', 'Project created successfully!');
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
            'latest_news' => 'nullable|string',
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
            'latest_news' => $request->latest_news,
            'photo_paths' => $photoPaths
        ]);

        SystemLog::log(
            'project_updated',
            'Updated community project: ' . $project->title,
            auth()->user()->id
        );

        return redirect()->back()->with('success', 'Project updated successfully!');
    }

    /**
     * Update project status
     */
    public function updateStatus(Request $request, CommunityProject $project)
    {
        $request->validate([
            'status' => 'required|in:active,upcoming,completed,cancelled'
        ]);

        $oldStatus = $project->status;
        $project->update(['status' => $request->status]);

        // If project is completed, award points to approved volunteers
        if ($request->status === 'completed' && $oldStatus !== 'completed') {
            $approvedApplications = $project->approvedApplications;
            foreach ($approvedApplications as $application) {
                $user = $application->user;
                
                // Use PointsService instead of direct increment
                $this->pointsService->awardCommunityProjectPoints(
                    $user,
                    $project->id,
                    $project->title,
                    $project->points_reward
                );
                
                // Create notification for user
                \App\Models\Notification::create([
                    'user_id' => $user->id,
                    'title' => 'Project Completed - Points Awarded!',
                    'message' => "Congratulations! You have earned {$project->points_reward} points for completing the project: {$project->title}",
                    'type' => 'project_completed',
                    'read' => false
                ]);
            }
        }
        
        // If project status is changed from completed to something else, rollback points
        if ($oldStatus === 'completed' && $request->status !== 'completed') {
            $approvedApplications = $project->approvedApplications;
            foreach ($approvedApplications as $application) {
                $user = $application->user;
                
                // Deduct points using PointsService
                $this->pointsService->deductPoints(
                    $user,
                    $project->points_reward,
                    'project_status_reverted',
                    "Project status reverted from completed to {$request->status}"
                );
                
                // Create notification for user about points deduction
                \App\Models\Notification::create([
                    'user_id' => $user->id,
                    'title' => 'Project Status Changed - Points Deducted',
                    'message' => "Project '{$project->title}' status was changed from completed to {$request->status}. {$project->points_reward} points have been deducted from your account.",
                    'type' => 'project_status_reverted',
                    'read' => false
                ]);
            }
        }

        SystemLog::log(
            'project_status_updated',
            "Updated project status from {$oldStatus} to {$request->status}: " . $project->title,
            auth()->user()->id
        );

        return back()->with('success', 'Project status updated successfully!');
    }

    /**
     * Update project latest news and files
     */
    public function updateNews(Request $request, CommunityProject $project)
    {
        $request->validate([
            'latest_news' => 'nullable|string|max:5000',
            'news_files.*' => 'nullable|file|mimes:jpeg,png,jpg,gif,pdf,doc,docx,txt|max:5120' // 5MB max
        ]);

        // Create new news entry instead of updating
        $newsData = [
            'community_project_id' => $project->id,
            'content' => $request->latest_news,
            'created_by' => auth()->user()->id
        ];

        // Handle file uploads
        if ($request->hasFile('news_files')) {
            $newsFilePaths = [];
            foreach ($request->file('news_files') as $file) {
                $path = $file->store('project-news', 'public');
                $newsFilePaths[] = [
                    'path' => $path,
                    'original_name' => $file->getClientOriginalName(),
                    'size' => $file->getSize(),
                    'mime_type' => $file->getMimeType()
                ];
            }
            $newsData['files'] = $newsFilePaths;
        }

        // Create new news entry
        \App\Models\ProjectNews::create($newsData);

        // Also update the latest_news field for backward compatibility
        $project->update(['latest_news' => $request->latest_news]);

        SystemLog::log(
            'project_news_updated',
            'Added new news for project: ' . $project->title,
            auth()->user()->id
        );

        // Return back with success message and refresh the page
        return back()->with('success', 'News added successfully!');
    }

    /**
     * Delete a news entry
     */
    public function deleteNews(Request $request, CommunityProject $project, $newsId)
    {
        $news = \App\Models\ProjectNews::where('id', $newsId)
            ->where('community_project_id', $project->id)
            ->first();

        if (!$news) {
            return response()->json(['success' => false, 'message' => 'News not found!'], 404);
        }

        // Check if user can delete this news (only creator or admin)
        if ($news->created_by !== auth()->user()->id && auth()->user()->role !== 'admin') {
            return response()->json(['success' => false, 'message' => 'You can only delete your own news!'], 403);
        }

        // Delete associated files
        if ($news->files) {
            foreach ($news->files as $file) {
                Storage::disk('public')->delete($file['path']);
            }
        }

        $news->delete();

        SystemLog::log(
            'project_news_deleted',
            'Deleted news from project: ' . $project->title,
            auth()->user()->id
        );

        return response()->json(['success' => true, 'message' => 'News deleted successfully!']);
    }

    /**
     * Show project details page
     */
    public function show(CommunityProject $project)
    {
        $user = auth()->user();
        $userApplication = null;
        $canView = false;

        // Check if user can view this project
        if ($user->role === 'admin') {
            $canView = true;
        } else {
            // Check if user has applied to this project
            $userApplication = ProjectApplication::where('user_id', $user->id)
                ->where('community_project_id', $project->id)
                ->first();
            
            if ($userApplication) {
                $canView = true;
            }
        }

        if (!$canView) {
            // Instead of abort(403), show a friendly error page
            return Inertia::render('CommunityProject/AccessDenied', [
                'project' => [
                    'id' => $project->id,
                    'title' => $project->title,
                    'category' => $project->category,
                    'status' => $project->status,
                ],
                'userRole' => $user->role,
                'hasVolunteerApplication' => \App\Models\VolunteerApplication::where('user_id', $user->id)
                    ->where('status', 'Approved')
                    ->exists()
            ]);
        }

        $projectData = [
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
            'latest_news' => $project->latest_news,
            'news_files' => $project->news_files,
            'news_history' => $project->news()->with('creator')->get()->map(function ($news) {
                return [
                    'id' => $news->id,
                    'content' => $news->content,
                    'files' => $news->files,
                    'created_at' => $news->created_at,
                    'creator_name' => $news->creator->name
                ];
            }),
            'created_at' => $project->created_at,
            'updated_at' => $project->updated_at,
        ];

        return Inertia::render('CommunityProject/Show', [
            'project' => $projectData,
            'userApplication' => $userApplication,
            'isAdmin' => $user->role === 'admin'
        ]);
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
            ->filter(function ($application) {
                // Only include applications with valid user and project
                return $application->user && $application->project;
            })
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

    /**
     * Show create project form with pre-filled data from missing report
     */
    public function createFromMissingReport($missingReportId)
    {
        $missingReport = \App\Models\MissingReport::findOrFail($missingReportId);
        
        // Pre-fill data based on missing report
        $prefilledData = [
            'title' => "Search Operation for {$missingReport->full_name}",
            'description' => "Community search operation to help locate {$missingReport->full_name}. " . 
                           ($missingReport->physical_description ? "Physical description: {$missingReport->physical_description}. " : "") .
                           ($missingReport->additional_notes ? "Additional notes: {$missingReport->additional_notes}" : ""),
            'location' => $missingReport->last_seen_location,
            'category' => 'search',
            'status' => 'active',
            'volunteers_needed' => 10,
            'points_reward' => 50,
            'date' => now()->addDays(3)->format('Y-m-d'), // Default to 3 days from now
            'time' => '09:00',
            'duration' => '4 hours'
        ];
        
        return Inertia::render('Admin/CreateProjectFromMissingReport', [
            'missingReport' => [
                'id' => $missingReport->id,
                'full_name' => $missingReport->full_name,
                'age' => $missingReport->age,
                'gender' => $missingReport->gender,
                'last_seen_location' => $missingReport->last_seen_location,
                'last_seen_date' => $missingReport->last_seen_date,
                'physical_description' => $missingReport->physical_description,
                'additional_notes' => $missingReport->additional_notes,
                'photo_paths' => $missingReport->photo_paths,
            ],
            'prefilledData' => $prefilledData
        ]);
    }
}
