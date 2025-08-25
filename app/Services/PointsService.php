<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserPoint;
use App\Models\PointTransaction;
use App\Models\SocialShare;
use Illuminate\Support\Facades\DB;

class PointsService
{
    /**
     * Award points to a user
     */
    public function awardPoints(User $user, int $points, string $action, string $description, array $metadata = [])
    {
        return DB::transaction(function () use ($user, $points, $action, $description, $metadata) {
            // Create or update user points record
            $userPoint = UserPoint::firstOrCreate(
                ['user_id' => $user->id],
                [
                    'current_points' => 0,
                    'total_earned_points' => 0,
                    'total_spent_points' => 0,
                ]
            );

            // Update points
            $userPoint->current_points += $points;
            $userPoint->total_earned_points += $points;
            $userPoint->save();

            // Create transaction record
            PointTransaction::create([
                'user_id' => $user->id,
                'type' => 'earned',
                'points' => $points,
                'action' => $action,
                'description' => $description,
                'metadata' => $metadata,
            ]);

            return $userPoint;
        });
    }

    /**
     * Deduct points from a user
     */
    public function deductPoints(User $user, int $points, string $action, string $description, array $metadata = [])
    {
        return DB::transaction(function () use ($user, $points, $action, $description, $metadata) {
            $userPoint = UserPoint::where('user_id', $user->id)->firstOrFail();

            if ($userPoint->current_points < $points) {
                throw new \Exception('Insufficient points');
            }

            // Update points
            $userPoint->current_points -= $points;
            $userPoint->total_spent_points += $points;
            $userPoint->save();

            // Create transaction record
            PointTransaction::create([
                'user_id' => $user->id,
                'type' => 'spent',
                'points' => $points,
                'action' => $action,
                'description' => $description,
                'metadata' => $metadata,
            ]);

            return $userPoint;
        });
    }

    /**
     * Award points for registration
     */
    public function awardRegistrationPoints(User $user)
    {
        return $this->awardPoints(
            $user,
            10,
            'registration',
            'Account registration bonus'
        );
    }

    /**
     * Award points for missing report
     */
    public function awardMissingReportPoints(User $user, $reportId, $reportName)
    {
        return $this->awardPoints(
            $user,
            5,
            'missing_report',
            "Submitted missing person report for {$reportName}",
            ['report_id' => $reportId]
        );
    }

    /**
     * Award points for approved sighting report
     */
    public function awardSightingReportPoints(User $user, $reportId)
    {
        return $this->awardPoints(
            $user,
            10,
            'sighting_report',
            'Submitted approved sighting report',
            ['report_id' => $reportId]
        );
    }

    /**
     * Award points for social share
     */
    public function awardSocialSharePoints(User $user, $reportId, $platform)
    {
        // Check if user already shared this report on this platform
        $existingShare = SocialShare::where([
            'user_id' => $user->id,
            'missing_report_id' => $reportId,
            'platform' => $platform,
        ])->first();

        if ($existingShare && $existingShare->points_awarded) {
            return false; // Already awarded points for this share
        }

        // Create or update social share record
        $socialShare = SocialShare::updateOrCreate(
            [
                'user_id' => $user->id,
                'missing_report_id' => $reportId,
                'platform' => $platform,
            ],
            [
                'points_awarded' => true,
            ]
        );

        // Award points
        $this->awardPoints(
            $user,
            1,
            'social_share',
            "Shared missing person case on {$platform}",
            [
                'report_id' => $reportId,
                'platform' => $platform,
                'share_id' => $socialShare->id,
            ]
        );

        return true;
    }

    /**
     * Award points for community project completion
     */
    public function awardCommunityProjectPoints(User $user, $projectId, $projectTitle, $points)
    {
        return $this->awardPoints(
            $user,
            $points,
            'community_project',
            "Completed community project: {$projectTitle}",
            ['project_id' => $projectId]
        );
    }

    /**
     * Deduct points for reward redemption
     */
    public function deductRewardPoints(User $user, $rewardId, $rewardName, $points)
    {
        return $this->deductPoints(
            $user,
            $points,
            'reward_redemption',
            "Redeemed reward: {$rewardName}",
            ['reward_id' => $rewardId]
        );
    }

    /**
     * Get user's current points
     */
    public function getCurrentPoints(User $user): int
    {
        $userPoint = UserPoint::where('user_id', $user->id)->first();
        return $userPoint ? $userPoint->current_points : 0;
    }

    /**
     * Check if user has enough points
     */
    public function hasEnoughPoints(User $user, int $requiredPoints): bool
    {
        return $this->getCurrentPoints($user) >= $requiredPoints;
    }

    /**
     * Get user's points history
     */
    public function getPointsHistory(User $user, int $limit = 50)
    {
        return PointTransaction::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Recalculate user's total points (for data consistency)
     */
    public function recalculateUserPoints(User $user)
    {
        return DB::transaction(function () use ($user) {
            $transactions = PointTransaction::where('user_id', $user->id)->get();
            
            $totalEarned = $transactions->where('type', 'earned')->sum('points');
            $totalSpent = $transactions->where('type', 'spent')->sum('points');
            $currentPoints = $totalEarned - $totalSpent;

            $userPoint = UserPoint::firstOrCreate(
                ['user_id' => $user->id],
                [
                    'current_points' => 0,
                    'total_earned_points' => 0,
                    'total_spent_points' => 0,
                ]
            );

            $userPoint->update([
                'current_points' => $currentPoints,
                'total_earned_points' => $totalEarned,
                'total_spent_points' => $totalSpent,
            ]);

            return $userPoint;
        });
    }
}
