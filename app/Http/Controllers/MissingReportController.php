<?php

namespace App\Http\Controllers;

use App\Models\MissingReport;
use App\Services\PointsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\NotificationService;

class MissingReportController extends Controller
{
    protected $pointsService;

    public function __construct(PointsService $pointsService)
    {
        $this->pointsService = $pointsService;
    }


    public function store(Request $request)
    {
        $validated = $request->validate(
            [
                'full_name' => ['required', 'regex:/^[A-Za-z\s]+$/', 'max:255'],
                'nickname' => ['nullable', 'regex:/^[A-Za-z\s]*$/', 'max:255'],
                'ic_number' => ['required', 'digits:12'],
                'age' => ['required', 'integer', 'min:0', 'max:150'],
                'gender' => ['required', 'in:Male,Female,Other'],
                'height_cm' => ['nullable', 'numeric', 'min:0'],
                'weight_kg' => ['nullable', 'numeric', 'min:0'],
                'physical_description' => ['nullable', 'string', 'max:1000'],
                'last_seen_date' => ['required', 'date'],
                'last_seen_location' => ['required', 'string', 'max:255'],
                'last_seen_clothing' => ['nullable', 'string', 'max:255'],
                'photo_paths' => ['nullable'],
                'police_report_path' => ['nullable'],
                'reporter_relationship' => ['required', 'in:Parent,Child,Spouse,Sibling,Friend,Other'],
                'reporter_name' => ['required', 'regex:/^[A-Za-z\s]+$/', 'max:255'],
                'reporter_phone' => ['required', 'digits_between:10,11'],
                'reporter_email' => ['nullable', 'email', 'max:255'],
                'additional_notes' => ['nullable', 'string', 'max:2000'],
            ],
            [
                'full_name.regex' => 'Full name must only contain alphabets and spaces.',
                'nickname.regex' => 'Nickname must only contain alphabets and spaces.',
                'ic_number.digits' => 'IC Number must be exactly 12 digits.',
                'reporter_name.regex' => 'Reporter name must only contain alphabets and spaces.',
                'reporter_phone.digits_between' => 'Phone number must be 10 or 11 digits.',
                'reporter_relationship.in' => 'Please select a valid relationship.'
            ]
        );

        $validated['user_id'] = auth()->id();

        $photoPaths = [];
        if ($request->hasFile('photos')) {
            foreach ($request->file('photos') as $file) {
                $photoPaths[] = $file->store('photos', 'public');
            }
        }

        $validated['photo_paths'] = $photoPaths;

        if ($request->hasFile('police_report')) {
            $validated['police_report_path'] = $request->file('police_report')->store('police_reports', 'public');
        }

        $validated['user_id'] = Auth::id();
        $validated['case_status'] = 'Pending';

        $report = MissingReport::create($validated);

        // Send notifications
        NotificationService::missingReportSubmitted($report);
        NotificationService::newMissingReportForAdmin($report);

        // Log the missing report creation
        \App\Models\SystemLog::log(
            'report_created',
            "Missing person report created: {$validated['full_name']}",
            ['report_id' => $report->id, 'reporter_name' => $validated['reporter_name']]
        );

        return redirect()->route('home')
            ->with('success', 'Report submitted successfully!');
    }

    public function show($id)
    {
        $report = MissingReport::findOrFail($id);

        // Check if the report belongs to the authenticated user or if the user is an admin
        $report->photo_paths = is_array($report->photo_paths)
            ? $report->photo_paths
            : json_decode($report->photo_paths, true);

        return inertia('MissingPersons/Show', [
            'report' => $report
        ]);
    }

    public function index(Request $request)
    {
        $query = MissingReport::query();

        // Show approved, missing, and found cases to the public
        $query->whereIn('case_status', ['Approved', 'Missing', 'Found']);

        // Search by full_name
        if ($request->filled('search')) {
            $query->where('full_name', 'like', '%' . $request->search . '%');
        }
        // Age filter
        if ($request->filled('ageMin')) {
            $query->where('age', '>=', $request->ageMin);
        }
        if ($request->filled('ageMax')) {
            $query->where('age', '<=', $request->ageMax);
        }
        // Gender filter (multi-select)
        if ($request->filled('gender')) {
            $genders = is_array($request->gender) ? $request->gender : explode(',', $request->gender);
            $query->whereIn('gender', $genders);
        }
        // Location filter (multi-select) - use fuzzy match, as stored value is often full address
        if ($request->filled('location')) {
            $locations = is_array($request->location) ? $request->location : explode(',', $request->location);
            $query->where(function ($q) use ($locations) {
                foreach ($locations as $loc) {
                    $loc = trim($loc);
                    if ($loc !== '') {
                        $q->orWhere('last_seen_location', 'like', "%{$loc}%");
                    }
                }
            });
        }
        // Report time (multi-select) - mutually inclusive ranges
        if ($request->filled('reportTime')) {
            $reportTimes = is_array($request->reportTime) ? $request->reportTime : explode(',', $request->reportTime);
            $query->where(function ($q) use ($reportTimes) {
                foreach ($reportTimes as $rt) {
                    if ($rt === "7") {
                        $q->orWhere('created_at', '>=', now()->subDays(7));
                    } elseif ($rt === "30") {
                        $q->orWhere(function ($qq) {
                            $qq->where('created_at', '>=', now()->subDays(30));
                        });
                    } elseif ($rt === "more") {
                        $q->orWhere('created_at', '<', now()->subDays(30));
                    }
                }
            });
        }
        // Weight filter (字段应该是 weight_kg)
        if ($request->filled('weightMin')) {
            $query->where('weight_kg', '>=', $request->weightMin);
        }
        if ($request->filled('weightMax')) {
            $query->where('weight_kg', '<=', $request->weightMax);
        }
        // Height filter (字段应该是 height_cm)
        if ($request->filled('heightMin')) {
            $query->where('height_cm', '>=', $request->heightMin);
        }
        if ($request->filled('heightMax')) {
            $query->where('height_cm', '<=', $request->heightMax);
        }

        // Pagination
        $perPage = $request->input('per_page', 8);
        $cases = $query->orderBy('created_at', 'desc')->paginate($perPage);

        // Format and return
        $cases->getCollection()->transform(function ($item) {
            $photoUrl = null;
            if ($item->photo_paths) {
                $photos = is_array($item->photo_paths)
                    ? $item->photo_paths
                    : json_decode($item->photo_paths, true);

                if (is_array($photos) && count($photos) > 0) {
                    $photoUrl = asset('storage/' . $photos[0]);
                }
            }
            return [
                'id' => $item->id,
                'full_name' => $item->full_name,
                'age' => $item->age,
                'gender' => $item->gender,
                'last_seen_location' => $item->last_seen_location,
                'height_cm' => $item->height_cm,
                'weight_kg' => $item->weight_kg,
                'photo_url' => $photoUrl,
                'case_status' => $item->case_status,
                'created_at' => $item->created_at,
            ];
        });

        return response()->json([
            'data' => $cases->items(),
            'total' => $cases->total(),
            'current_page' => $cases->currentPage(),
            'per_page' => $cases->perPage(),
        ]);
    }

    /**
     * Get user's own missing reports (including pending ones)
     */
    public function userReports(Request $request)
    {
        $user = auth()->user();
        $query = MissingReport::where('user_id', $user->id);

        // Search by full_name
        if ($request->filled('search')) {
            $query->where('full_name', 'like', '%' . $request->search . '%');
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('case_status', $request->status);
        }

        // Pagination
        $perPage = $request->input('per_page', 10);
        $cases = $query->orderBy('created_at', 'desc')->paginate($perPage);

        // Format and return
        $cases->getCollection()->transform(function ($item) {
            $photoUrl = null;
            if ($item->photo_paths) {
                $photos = is_array($item->photo_paths)
                    ? $item->photo_paths
                    : json_decode($item->photo_paths, true);

                if (is_array($photos) && count($photos) > 0) {
                    $photoUrl = asset('storage/' . $photos[0]);
                }
            }
            return [
                'id' => $item->id,
                'full_name' => $item->full_name,
                'age' => $item->age,
                'gender' => $item->gender,
                'last_seen_location' => $item->last_seen_location,
                'height_cm' => $item->height_cm,
                'weight_kg' => $item->weight_kg,
                'photo_url' => $photoUrl,
                'case_status' => $item->case_status,
                'rejection_reason' => $item->rejection_reason,
                'created_at' => $item->created_at,
            ];
        });

        return response()->json([
            'data' => $cases->items(),
            'total' => $cases->total(),
            'current_page' => $cases->currentPage(),
            'per_page' => $cases->perPage(),
        ]);
    }


}
