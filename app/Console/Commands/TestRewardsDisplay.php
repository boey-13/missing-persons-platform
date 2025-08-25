<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Reward;
use App\Models\User;
use App\Services\RewardService;
use App\Services\PointsService;

class TestRewardsDisplay extends Command
{
    protected $signature = 'test:rewards-display';
    protected $description = 'Test the new rewards display functionality';

    public function handle(RewardService $rewardService, PointsService $pointsService)
    {
        $this->info('Testing new rewards display functionality...');
        
        // Get a user
        $user = User::first();
        if (!$user) {
            $this->error('No users found. Please create a user first.');
            return 1;
        }
        
        $this->info("Testing with user: {$user->name}");
        $this->info("User points: " . $pointsService->getCurrentPoints($user));
        
        // Test getAvailableRewards (should show all active rewards now)
        $this->info("\n1. Testing getAvailableRewards (should show all active rewards)...");
        $availableRewards = $rewardService->getAvailableRewards($user);
        
        $this->info("Total rewards shown: " . $availableRewards->count());
        
        foreach ($availableRewards as $reward) {
            $canRedeem = $pointsService->hasEnoughPoints($user, $reward->points_required);
            $this->line("  - {$reward->name} ({$reward->points_required} points):");
            $this->line("    Can redeem: " . ($canRedeem ? 'Yes' : 'No'));
            $this->line("    can_redeem property: " . ($reward->can_redeem ? 'Yes' : 'No'));
        }
        
        // Count rewards by redeemability
        $redeemableCount = $availableRewards->where('can_redeem', true)->count();
        $nonRedeemableCount = $availableRewards->where('can_redeem', false)->count();
        
        $this->info("\nSummary:");
        $this->info("  - Total rewards: " . $availableRewards->count());
        $this->info("  - Redeemable: " . $redeemableCount);
        $this->info("  - Non-redeemable (insufficient points): " . $nonRedeemableCount);
        
        $this->info("\n✅ Rewards display test completed!");
        
        return 0;
    }
}
