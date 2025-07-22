<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class MissingReportController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'full_name' => 'required|string|max:255',
            'nickname' => 'nullable|string|max:255',
            'age' => 'nullable|integer',
            'gender' => 'nullable|string|max:20',
            'height_cm' => 'nullable|numeric',
            'weight_kg' => 'nullable|numeric',
            'physical_description' => 'nullable|string|max:1000',
            'last_seen_date' => 'required|date',
            'last_seen_location' => 'required|string|max:255',
            'last_seen_clothing' => 'nullable|string|max:255',
            'photo' => 'nullable|image|mimes:jpg,jpeg,png|max:4096',
            'police_report' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:4096',
            'reporter_name' => 'required|string|max:255',
            'reporter_relationship' => 'required|string|max:255',
            'reporter_phone' => 'required|string|max:20',
            'reporter_email' => 'required|email|max:255',
            'additional_notes' => 'nullable|string|max:2000',
        ]);

        // Upload files if they exist
        if ($request->hasFile('photo')) {
            $validated['photo_path'] = $request->file('photo')->store('photos', 'public');
        }
        if ($request->hasFile('police_report')) {
            $validated['police_report_path'] = $request->file('police_report')->store('police_reports', 'public');
        }

        //save to database

        \App\Models\MissingReport::create($validated);

        // Redirect and show success message
        return redirect()->back()->with('success', 'Missing person report submitted successfully!');
    }

}
