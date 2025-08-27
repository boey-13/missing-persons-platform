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

        // Redirect if already a volunteer
        if ($user && $user->role === 'volunteer') {
            return redirect()->route('volunteer.home')->with('status', 'You are already a volunteer.');
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
            ])->with('status', 'You already have a pending application.');
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
            ->with('status', 'Application submitted.');
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
        $apps = VolunteerApplication::with('user:id,name,email,role')
            ->orderByDesc('created_at')
            ->paginate(20);

        return Inertia::render('Admin/ManageVolunteers', [
            'applications' => $apps,
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
                // Promote to volunteer on approval
                $user->role = 'volunteer';
                $user->save();
                // notify
                \App\Models\Notification::create([
                    'user_id' => $user->id,
                    'type' => 'volunteer_application',
                    'title' => 'Volunteer Application Approved',
                    'message' => 'Congratulations! Your volunteer application has been approved.',
                    'data' => ['application_id' => $application->id, 'action' => 'open_projects'],
                ]);
            } elseif ($data['status'] === 'Rejected') {
                // If there is no other approved application for this user, demote back to user
                $hasOtherApproved = VolunteerApplication::where('user_id', $user->id)
                    ->where('id', '!=', $application->id)
                    ->where('status', 'Approved')
                    ->exists();
                if (!$hasOtherApproved && $user->role === 'volunteer') {
                    $user->role = 'user';
                    $user->save();
                }
                // notify rejection
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
        ]);

        return back();
    }
}


