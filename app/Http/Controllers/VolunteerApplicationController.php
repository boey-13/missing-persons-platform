<?php

namespace App\Http\Controllers;

use App\Models\VolunteerApplication;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class VolunteerApplicationController extends Controller
{
    public function create()
    {
        $user = auth()->user();

        // Check if user is already an approved volunteer
        if ($user && $user->isApprovedVolunteer()) {
            return redirect()->route('volunteer.projects')->with('success', 'You are already an approved volunteer.');
        }

        // If there is a pending application, show info page
        $existing = VolunteerApplication::where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->first();

        if ($existing && $existing->status === 'Pending') {
            return Inertia::render('Volunteer/ApplicationPending', [
                'application' => $existing,
            ]);
        }

        // If there is a rejected application, allow them to apply again
        if ($existing && $existing->status === 'Rejected') {
            return Inertia::render('Volunteer/BecomeVolunteer', [
                'profile' => [
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone ?? null,
                    'region' => $user->region ?? null,
                ],
                'previousApplication' => $existing,
            ]);
        }

        return Inertia::render('Volunteer/BecomeVolunteer', [
            'profile' => [
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone ?? null,
                'region' => $user->region ?? null,
            ],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'motivation' => ['required', 'string'],
            'skills' => ['nullable', 'array'],
            'skills.*' => ['string'],
            'languages' => ['nullable', 'array'],
            'languages.*' => ['string'],
            'availability' => ['nullable', 'array'],
            'preferred_roles' => ['nullable', 'array'],
            'areas' => ['nullable', 'string'],
            'transport_mode' => ['nullable', 'string'],
            'emergency_contact_name' => ['required', 'string'],
            'emergency_contact_phone' => ['required', 'string'],
            'prior_experience' => ['nullable', 'string'],
            'supporting_documents' => ['nullable', 'array'],
        ]);

        $user = auth()->user();

        // Prevent duplicate submission if a pending application exists
        $pending = VolunteerApplication::where('user_id', $user->id)
            ->where('status', 'Pending')
            ->first();
        if ($pending) {
            return Inertia::render('Volunteer/ApplicationPending', [
                'application' => $pending,
            ])->with('success', 'You already have a pending application.');
        }

        // File uploads (optional multiple)
        $paths = [];
        if ($request->hasFile('supporting_documents')) {
            foreach ($request->file('supporting_documents') as $file) {
                $paths[] = $file->store('supporting_documents', 'public');
            }
        }

        // Add user_id to validated data
        $validated['user_id'] = $user->id;
        $validated['supporting_documents'] = $paths;
        $validated['status'] = 'Pending';

        $application = VolunteerApplication::create($validated);

        // Send notifications
        NotificationService::volunteerApplicationSubmitted($application);
        NotificationService::newVolunteerApplicationForAdmin($application);

        return redirect()->route('volunteer.application-pending')
            ->with('success', 'Application submitted.');
    }

    public function applicationPending()
    {
        $user = auth()->user();
        $application = VolunteerApplication::where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->first();

        if (!$application) {
            return redirect()->route('volunteer.apply')->with('status', 'No volunteer application found.');
        }

        return Inertia::render('Volunteer/ApplicationPending', [
            'application' => $application,
        ]);
    }

    public function home()
    {
        $user = auth()->user();
        $hasApproved = \App\Models\VolunteerApplication::where('user_id', $user->id)
            ->where('status', 'Approved')
            ->exists();
        if (!$hasApproved) {
            return redirect()->route('volunteer.apply')->with('status', 'Only approved volunteers can access this page.');
        }
        return Inertia::render('Volunteer/Home');
    }

    // Admin index
    public function adminIndex(Request $request)
    {
        $query = VolunteerApplication::with(['user:id,name,email,role'])
            ->orderByDesc('created_at');
        
        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        
        // Search by user name or email
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }
        
        // Filter by skills
        if ($request->filled('skills')) {
            $skills = $request->skills;
            $query->whereJsonContains('skills', $skills);
        }
        
        // Filter by languages
        if ($request->filled('languages')) {
            $languages = $request->languages;
            $query->whereJsonContains('languages', $languages);
        }

        $apps = $query->paginate(15);

        // Get project applications for each volunteer
        $apps->getCollection()->transform(function ($app) {
            if ($app->user) {
                $projectApplications = \App\Models\ProjectApplication::with('project:id,title,location,date,status,category')
                    ->where('user_id', $app->user->id)
                    ->orderByDesc('created_at')
                    ->get()
                    ->filter(function ($projectApp) {
                        return $projectApp->project !== null;
                    })
                    ->map(function ($projectApp) {
                        return [
                            'id' => $projectApp->id,
                            'project_title' => $projectApp->project->title,
                            'project_location' => $projectApp->project->location,
                            'project_date' => $projectApp->project->date,
                            'project_status' => $projectApp->project->status,
                            'project_category' => $projectApp->project->category,
                            'application_status' => $projectApp->status,
                            'applied_at' => $projectApp->created_at->format('Y-m-d H:i'),
                        ];
                    });
                
                $app->project_applications = $projectApplications;
            }
            return $app;
        });

        return Inertia::render('Admin/ManageVolunteers', [
            'applications' => $apps,
            'filters' => [
                'status' => $request->status,
                'search' => $request->search,
                'skills' => $request->skills,
                'languages' => $request->languages,
            ],
        ]);
    }

    public function updateStatus(Request $request, VolunteerApplication $application)
    {
        $data = $request->validate([
            'status' => ['required', 'in:Approved,Rejected'],
            'reason' => ['nullable', 'string'],
        ]);

        $application->status = $data['status'];
        $application->status_reason = $data['reason'] ?? null;
        $application->save();

        $user = $application->user;
        if ($user) {
            if ($data['status'] === 'Approved') {
                // Update user role to volunteer
                $user->role = 'volunteer';
                $user->save();
                
                // Notify approval
                \App\Models\Notification::create([
                    'user_id' => $user->id,
                    'type' => 'volunteer_application',
                    'title' => 'Volunteer Application Approved',
                    'message' => 'Congratulations! Your volunteer application has been approved.',
                    'data' => ['application_id' => $application->id, 'action' => 'open_projects'],
                ]);
            } elseif ($data['status'] === 'Rejected') {
                // Ensure user is not a volunteer
                if ($user->role === 'volunteer') {
                    $user->role = 'user';
                    $user->save();
                }
                
                // Notify rejection
                \App\Models\Notification::create([
                    'user_id' => $user->id,
                    'type' => 'volunteer_application',
                    'title' => 'Volunteer Application Rejected',
                    'message' => ($data['reason'] ? ('Rejected: ' . $data['reason'] . '. ') : 'Your volunteer application has been rejected. ') . 'Click to re-apply.',
                    'data' => [
                        'application_id' => $application->id,
                        'reason' => $data['reason'] ?? null,
                        'action' => 'reapply'
                    ],
                ]);
            }
        }

        \App\Models\SystemLog::log('volunteer_application_'.$data['status'], 'Volunteer application status updated', [
            'application_id' => $application->id,
            'status' => $data['status'],
            'user_role_updated' => $user ? $user->role : null,
        ]);

        return back()->with('success', "Volunteer application {$data['status']} successfully!");
    }
}