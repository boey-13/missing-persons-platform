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
                'reporter' => $item->reporter_name,
                'reporter_phone' => $item->reporter_phone,
                'reporter_email' => $item->reporter_email,
                'created_at' => $item->created_at?->toDateTimeString(),
                'last_seen' => $item->last_seen_location,
                'status' => $item->case_status,
                'age' => $item->age,
                'gender' => $item->gender,
                'last_seen_date' => $item->last_seen_date,
                'photo_paths' => $item->photo_paths,
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

        return response()->json($report);
    }

    /**
     * Update missing report status (Approve/Reject)
     */
    public function updateMissingReportStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:Approved,Rejected,Pending',
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

        return response()->json(['success' => true, 'message' => 'Status updated successfully']);
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
