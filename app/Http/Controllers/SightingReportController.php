<?php

namespace App\Http\Controllers;

use App\Models\MissingReport;
use App\Models\SightingReport;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SightingReportController extends Controller
{
    public function adminIndex()
    {
        $items = SightingReport::orderBy('created_at', 'desc')->paginate(15);
        $data = $items->getCollection()->transform(function ($r) {
            return [
                'id' => $r->id,
                'location' => $r->location,
                'sighted_at' => optional($r->sighted_at)->toDateTimeString(),
                'reporter' => $r->reporter_name,
                'status' => $r->status,
            ];
        });

        return Inertia::render('Admin/ManageSightingReports', [
            'items' => $data,
            'pagination' => [
                'total' => $items->total(),
                'current_page' => $items->currentPage(),
                'per_page' => $items->PerPage(),
            ],
        ]);
    }

    public function updateStatus(SightingReport $sighting, Request $request)
    {
        $request->validate(['status' => 'required|in:Pending,Approved,Rejected']);
        $oldStatus = $sighting->status;
        $sighting->status = $request->status;
        $sighting->save();

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
        ]);
    }
    public function create($id)
    {
        $report = MissingReport::findOrFail($id);
        return Inertia::render('SightingReports/ReportSighting', [
            'report' => $report->only(['id','full_name','last_seen_location']),
        ]);
    }

    public function store(Request $request, $id)
    {
        $report = MissingReport::findOrFail($id);
        $validated = $request->validate([
            'location' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'sighted_at' => ['nullable', 'date'],
            'reporter_name' => ['required', 'string', 'max:255'],
            'reporter_phone' => ['required', 'string', 'max:30'],
            'reporter_email' => ['nullable', 'email', 'max:255'],
            'photos.*' => ['image', 'max:2048'],
        ]);

        $validated['user_id'] = auth()->id();
        $validated['missing_report_id'] = $report->id;

        $photoPaths = [];
        if ($request->hasFile('photos')) {
            foreach ($request->file('photos') as $file) {
                $photoPaths[] = $file->store('sightings', 'public');
            }
        }
        $validated['photo_paths'] = $photoPaths;

        $sighting = SightingReport::create($validated);

        // Log the sighting submission
        \App\Models\SystemLog::log(
            'sighting_submitted',
            "Sighting report submitted for missing person: {$report->full_name}",
            ['sighting_id' => $sighting->id, 'missing_report_id' => $report->id]
        );

        return redirect()->route('missing-persons.show', $report->id)->with('status', 'Sighting submitted.');
    }
}


