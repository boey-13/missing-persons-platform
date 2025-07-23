<?php

namespace App\Http\Controllers;

use App\Models\MissingReport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MissingReportController extends Controller
{


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
        if ($request->hasFile('photo')) {
            foreach ($request->file('photo') as $file) {
                $photoPaths[] = $file->store('photos', 'public');
            }
        }
        $validated['photo_paths'] = $photoPaths;

        if ($request->hasFile('police_report')) {
            $validated['police_report_path'] = $request->file('police_report')->store('police_reports', 'public');
        }

        $validated['user_id'] = Auth::id();
        $validated['case_status'] = 'Missing';

        MissingReport::create($validated);

        return redirect()->back()->with('success', 'Report submitted!');
    }

}
