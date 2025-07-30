<?php

namespace App\Http\Controllers;
use App\Models\MissingReport;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class PosterController extends Controller
{
    public function downloadPoster($id)
{
    $report = MissingReport::findOrFail($id);

    $photo_paths = $report->photo_paths ?? [];

    $pdf = PDF::loadView('pdf.missing_poster', [
        'report' => $report,
        'photo_paths' => $photo_paths,
    ]);

    $filename = 'missing_poster_case_' . $report->id . '.pdf';
    return $pdf->download($filename);
}

}
