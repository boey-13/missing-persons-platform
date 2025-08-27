<?php

namespace App\Http\Controllers;

use App\Models\MissingReport;
use App\Models\SystemLog;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminController extends Controller
{
    /**
     * Display admin dashboard
     */
    public function dashboard(): Response
    {
        $stats = [
            'totalMissingCases' => MissingReport::count(),
            'pendingMissingCases' => MissingReport::where('case_status', 'Pending')->count(),
            'pendingSightings' => \App\Models\SightingReport::where('status', 'Pending')->count(),
            'totalUsers' => \App\Models\User::count(),
        ];
        
        return Inertia::render('Admin/Dashboard', ['stats' => $stats]);
    }

    /**
     * Display all missing reports for admin management
     */
    public function missingReports(Request $request): Response
    {
        $query = MissingReport::with('user')
            ->orderBy('created_at', 'desc');

        // Filter by status
        if ($request->filled('status')) {
            $query->where('case_status', $request->status);
        }

        // Search by name
        if ($request->filled('search')) {
            $query->where('full_name', 'like', '%' . $request->search . '%');
        }

        $cases = $query->paginate(15);
        
        $data = $cases->getCollection()->transform(function ($item) {
            return [
                'id' => $item->id,
                'name' => $item->full_name,
                'full_name' => $item->full_name,
                'ic_number' => $item->ic_number,
                'nickname' => $item->nickname,
                'age' => $item->age,
                'gender' => $item->gender,
                'height_cm' => $item->height_cm,
                'weight_kg' => $item->weight_kg,
                'physical_description' => $item->physical_description,
                'last_seen_date' => $item->last_seen_date,
                'last_seen_location' => $item->last_seen_location,
                'last_seen_clothing' => $item->last_seen_clothing,
                'photo_paths' => $item->photo_paths,
                'police_report_path' => $item->police_report_path,
                'reporter_name' => $item->reporter_name,
                'reporter_ic_number' => $item->reporter_ic_number,
                'reporter_relationship' => $item->reporter_relationship,
                'reporter_phone' => $item->reporter_phone,
                'reporter_email' => $item->reporter_email,
                'additional_notes' => $item->additional_notes,
                'created_at' => $item->created_at?->toDateTimeString(),
                'last_seen' => $item->last_seen_location,
                'status' => $item->case_status,
            ];
        });

        return Inertia::render('Admin/ManageMissingReports', [
            'items' => $data,
            'pagination' => [
                'total' => $cases->total(),
                'current_page' => $cases->currentPage(),
                'per_page' => $cases->perPage(),
            ],
            'filters' => [
                'status' => $request->status,
                'search' => $request->search,
            ],
        ]);
    }

    /**
     * Show missing report details
     */
    public function showMissingReport($id)
    {
        $report = MissingReport::with('user')->findOrFail($id);
        
        // Convert photo_paths to full URLs
        if ($report->photo_paths && is_array($report->photo_paths)) {
            $report->photo_paths = array_map(function ($path) {
                return '/storage/' . $path;
            }, $report->photo_paths);
        }

        // Ensure all fields are included in the response
        $reportData = [
            'id' => $report->id,
            'full_name' => $report->full_name,
            'ic_number' => $report->ic_number,
            'nickname' => $report->nickname,
            'age' => $report->age,
            'gender' => $report->gender,
            'height_cm' => $report->height_cm,
            'weight_kg' => $report->weight_kg,
            'physical_description' => $report->physical_description,
            'last_seen_date' => $report->last_seen_date,
            'last_seen_location' => $report->last_seen_location,
            'last_seen_clothing' => $report->last_seen_clothing,
            'photo_paths' => $report->photo_paths,
            'police_report_path' => $report->police_report_path,
            'reporter_name' => $report->reporter_name,
            'reporter_ic_number' => $report->reporter_ic_number,
            'reporter_relationship' => $report->reporter_relationship,
            'reporter_phone' => $report->reporter_phone,
            'reporter_email' => $report->reporter_email,
            'additional_notes' => $report->additional_notes,
            'case_status' => $report->case_status,
            'created_at' => $report->created_at,
            'updated_at' => $report->updated_at,
        ];

        return response()->json($reportData);
    }

    /**
     * Update missing report status (Approve/Reject)
     */
    public function updateMissingReportStatus(Request $request, $id)
    {
        \Log::info('Status update request received', [
            'id' => $id,
            'status' => $request->status,
            'user_id' => auth()->id()
        ]);

        $request->validate([
            'status' => 'required|in:Pending,Approved,Rejected,Missing,Found,Closed',
            'rejection_reason' => 'required_if:status,Rejected|string|max:500',
        ]);

        $report = MissingReport::findOrFail($id);
        $oldStatus = $report->case_status;
        $report->case_status = $request->status;
        
        if ($request->status === 'Rejected') {
            $report->rejection_reason = $request->rejection_reason;
        } else {
            $report->rejection_reason = null;
        }
        
        $report->save();

        \Log::info('Status updated successfully', [
            'report_id' => $report->id,
            'old_status' => $oldStatus,
            'new_status' => $request->status
        ]);

        // Log the status change
        SystemLog::create([
            'user_id' => auth()->id(),
            'action' => 'missing_report_status_updated',
            'description' => "Updated missing report #{$report->id} status from {$oldStatus} to {$request->status}",
            'data' => [
                'report_id' => $report->id,
                'old_status' => $oldStatus,
                'new_status' => $request->status,
                'rejection_reason' => $request->rejection_reason ?? null
            ]
        ]);

        // Send notifications based on status
        if ($request->status === 'Approved') {
            NotificationService::missingReportApproved($report);
        } elseif ($request->status === 'Rejected') {
            NotificationService::missingReportRejected($report, $request->rejection_reason);
        }

        return back()->with('success', 'Status updated successfully');
    }

    /**
     * Update missing report information
     */
    public function updateMissingReport(Request $request, $id)
    {
        $request->validate([
            'full_name' => 'required|string|max:255',
            'age' => 'required|integer|min:0|max:150',
            'gender' => 'required|in:Male,Female,Other',
            'last_seen_location' => 'required|string|max:255',
            'last_seen_date' => 'required|date',
            'physical_description' => 'nullable|string|max:1000',
            'additional_notes' => 'nullable|string|max:2000',
        ]);

        $report = MissingReport::findOrFail($id);
        $report->update($request->only([
            'full_name', 'age', 'gender', 'last_seen_location', 
            'last_seen_date', 'physical_description', 'additional_notes'
        ]));

        // Log the update
        SystemLog::log(
            'missing_report_updated',
            "Missing report updated by admin",
            [
                'report_id' => $report->id,
                'missing_person_name' => $report->full_name,
                'admin_id' => auth()->id(),
            ]
        );

        return back()->with('success', 'Report updated successfully.');
    }
}
