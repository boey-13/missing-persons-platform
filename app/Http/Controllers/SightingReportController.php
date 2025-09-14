<?php

namespace App\Http\Controllers;

use App\Models\MissingReport;
use App\Models\SightingReport;
use App\Services\PointsService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Services\NotificationService;

class SightingReportController extends Controller
{
    protected $pointsService;

    public function __construct(PointsService $pointsService)
    {
        $this->pointsService = $pointsService;
    }

    public function adminIndex(Request $request)
    {
        $query = SightingReport::with('missingReport')->orderBy('created_at', 'desc');
        
        // Filter by missing report ID if provided
        if ($request->filled('missing_report_id')) {
            $query->where('missing_report_id', $request->missing_report_id);
        }
        
        // Filter by status if provided
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        
        // Search by location if provided
        if ($request->filled('search')) {
            $query->where('location', 'like', '%' . $request->search . '%');
        }

        $items = $query->paginate(15);
        $data = $items->getCollection()->transform(function ($r) {
            return [
                'id' => $r->id,
                'location' => $r->location,
                'sighted_at' => optional($r->sighted_at)->toDateTimeString(),
                'reporter' => $r->reporter_name,
                'status' => $r->status,
                'missing_report_id' => $r->missing_report_id,
                'missing_person_name' => $r->missingReport ? $r->missingReport->full_name : 'Unknown',
            ];
        });

        // Get all missing reports for the filter dropdown
        $missingReports = MissingReport::select('id', 'full_name', 'case_status')
            ->whereIn('case_status', ['Approved', 'Missing', 'Found'])
            ->orderBy('full_name')
            ->get();

        return Inertia::render('Admin/ManageSightingReports', [
            'items' => $data,
            'pagination' => [
                'total' => $items->total(),
                'current_page' => $items->currentPage(),
                'per_page' => $items->perPage(),
            ],
            'filters' => [
                'missing_report_id' => $request->missing_report_id,
                'status' => $request->status,
                'search' => $request->search,
            ],
            'missingReports' => $missingReports,
        ]);
    }

    public function updateStatus(SightingReport $sighting, Request $request)
    {
        $request->validate(['status' => 'required|in:Pending,Approved,Rejected']);
        $oldStatus = $sighting->status;
        $sighting->status = $request->status;
        $sighting->save();

        // Award points if sighting report is approved
        if ($request->status === 'Approved' && $oldStatus !== 'Approved' && $sighting->user_id) {
            $this->pointsService->awardSightingReportPoints($sighting->user, $sighting->id);
        }

        // Log the status change
        \App\Models\SystemLog::log(
            'sighting_' . strtolower($request->status),
            "Sighting report {$request->status} for missing person: {$sighting->missingReport->full_name}",
            ['sighting_id' => $sighting->id, 'missing_report_id' => $sighting->missing_report_id, 'old_status' => $oldStatus, 'new_status' => $request->status]
        );

        return back();
    }

    public function show(SightingReport $sighting)
    {
        return response()->json([
            'id' => $sighting->id,
            'location' => $sighting->location,
            'description' => $sighting->description,
            'sighted_at' => optional($sighting->sighted_at)->toDateTimeString(),
            'reporter_name' => $sighting->reporter_name,
            'reporter_phone' => $sighting->reporter_phone,
            'reporter_email' => $sighting->reporter_email,
            'status' => $sighting->status,
            'photos' => $sighting->photo_paths ?? [],
            'missing_person' => [
                'full_name' => $sighting->missingReport->full_name,
                'age' => $sighting->missingReport->age,
                'gender' => $sighting->missingReport->gender,
                'last_seen_location' => $sighting->missingReport->last_seen_location,
                'last_seen_date' => optional($sighting->missingReport->last_seen_date)->toDateTimeString(),
                'physical_description' => $sighting->missingReport->physical_description,
                'additional_notes' => $sighting->missingReport->additional_notes,
            ]
        ]);
    }
    public function create($id)
    {
        $report = MissingReport::findOrFail($id);
        
        // Check if the report status allows sighting submissions
        if (!in_array($report->case_status, ['Approved', 'Missing'])) {
            return redirect()->back()->with('error', 'Sighting submissions are not available for this case status.');
        }
        
        return Inertia::render('SightingReports/ReportSighting', [
            'report' => $report->only(['id','full_name','last_seen_location','last_seen_date']),
        ]);
    }

    public function store(Request $request, $id)
    {
        try {
            \Log::info('Sighting report submission started', [
                'missing_report_id' => $id,
                'user_id' => auth()->id(),
                'request_data' => $request->except(['photos'])
            ]);

            $report = MissingReport::findOrFail($id);
            
            // Check if the report status allows sighting submissions
            if (!in_array($report->case_status, ['Approved', 'Missing'])) {
                return redirect()->back()->with('error', 'Sighting submissions are not available for this case status.');
            }
            $validated = $request->validate([
                'location' => ['required', 'string', 'max:255'],
                'description' => ['required', 'string', 'max:2000'],
                'sighted_at' => ['required', 'date', 'after:' . $report->last_seen_date],
                'reporter_name' => ['required', 'string', 'max:255'],
                'reporter_phone' => ['required', 'string', 'regex:/^01\d{8,9}$/'],
                'reporter_email' => ['required', 'email', 'max:255'],
                'photos' => ['required', 'array', 'min:1'],
                'photos.*' => ['image', 'max:5120'],
            ], [
                'location.required' => 'Location is required.',
                'description.required' => 'Description is required.',
                'sighted_at.required' => 'Sighted at date/time is required.',
                'sighted_at.after' => 'Sighting date must be after the last seen date.',
                'reporter_name.required' => 'Reporter name is required.',
                'reporter_phone.required' => 'Reporter phone is required.',
                'reporter_phone.regex' => 'Phone number must be 10-11 digits starting with 01.',
                'reporter_email.required' => 'Reporter email is required.',
                'reporter_email.email' => 'Please enter a valid email address.',
                'photos.required' => 'At least one photo is required.',
                'photos.min' => 'At least one photo is required.',
                'photos.*.image' => 'Photos must be image files.',
                'photos.*.max' => 'Each photo must be smaller than 5MB.',
            ]);

            $validated['user_id'] = auth()->id(); // This will be null if user is not logged in
            $validated['missing_report_id'] = $report->id;
            $validated['status'] = 'Pending';

            $photoPaths = [];
            if ($request->hasFile('photos')) {
                foreach ($request->file('photos') as $file) {
                    $photoPaths[] = $file->store('sightings', 'public');
                }
            }
            $validated['photo_paths'] = $photoPaths;

            \Log::info('Creating sighting report', ['validated_data' => $validated]);

            $sighting = SightingReport::create($validated);

            \Log::info('Sighting report created successfully', ['sighting_id' => $sighting->id]);

            // Send notifications
            try {
                NotificationService::sightingReportSubmitted($sighting);
                NotificationService::newSightingReportForAdmin($sighting);
            } catch (\Exception $e) {
                \Log::error('Failed to send notifications', [
                    'sighting_id' => $sighting->id,
                    'error' => $e->getMessage()
                ]);
            }

            \Log::info('Sighting report submission completed successfully', ['sighting_id' => $sighting->id]);

            // Redirect to the missing person details page with success message
            return redirect()->to("/missing-persons/{$report->id}")->with('success', 'Sighting report submitted successfully!');
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Re-throw validation exceptions so they can be handled properly by Inertia
            throw $e;
        } catch (\Exception $e) {
            \Log::error('Sighting report submission failed', [
                'missing_report_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->back()->withErrors(['error' => 'Failed to submit sighting report. Please try again.']);
        }
    }

    /**
     * Delete a sighting report (Admin only)
     */
    public function adminDelete($id)
    {
        $sighting = SightingReport::findOrFail($id);
        $missingPersonName = $sighting->missingReport ? $sighting->missingReport->full_name : 'Unknown';
        
        // Log the deletion
        \App\Models\SystemLog::log(
            'sighting_report_deleted',
            "Deleted sighting report for: {$missingPersonName}",
            [
                'sighting_id' => $sighting->id,
                'missing_person_name' => $missingPersonName,
                'reporter_id' => $sighting->user_id,
                'deleted_by' => auth()->id()
            ]
        );
        
        $sighting->delete();
        
        return redirect()->back()->with('success', 'Sighting report deleted successfully');
    }
}


