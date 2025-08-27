<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SocialShare;
use App\Models\MissingReport;
use App\Services\PointsService;

class SocialShareController extends Controller
{
    protected $pointsService;

    public function __construct(PointsService $pointsService)
    {
        $this->pointsService = $pointsService;
    }

    public function store(Request $request)
    {
        $request->validate([
            'report_id' => 'required|exists:missing_reports,id',
            'platform' => 'required|in:facebook,twitter,whatsapp,instagram'
        ]);

        $user = auth()->user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'You must be logged in to share.'
            ], 401);
        }

        $report = MissingReport::findOrFail($request->report_id);
        
        // Check if user has already shared this report on this platform
        $existingShare = SocialShare::where('user_id', $user->id)
            ->where('missing_report_id', $request->report_id)
            ->where('platform', $request->platform)
            ->first();

        if ($existingShare) {
            return response()->json([
                'success' => false,
                'message' => 'You have already shared this case on ' . ucfirst($request->platform) . '.'
            ]);
        }

        // Create new share record
        SocialShare::create([
            'user_id' => $user->id,
            'missing_report_id' => $request->report_id,
            'platform' => $request->platform,
            'share_url' => $request->get('share_url'),
            'points_awarded' => false
        ]);

        // Award points for sharing
        $pointsAwarded = $this->pointsService->awardPoints($user->id, 'social_share', [
            'platform' => $request->platform,
            'report_id' => $request->report_id
        ]);

        // Update the share record to mark points as awarded
        SocialShare::where('user_id', $user->id)
            ->where('missing_report_id', $request->report_id)
            ->where('platform', $request->platform)
            ->update(['points_awarded' => true]);

        return response()->json([
            'success' => true,
            'message' => 'Shared successfully!',
            'pointsAwarded' => $pointsAwarded
        ]);
    }
}
