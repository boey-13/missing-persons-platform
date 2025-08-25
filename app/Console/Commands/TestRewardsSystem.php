<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Reward;
use App\Services\PointsService;
use App\Services\RewardService;

class TestRewardsSystem extends Command
{
    protected $signature = 'test:rewards-system';
    protected $description = 'Test the rewards system functionality';

    public function handle(PointsService $pointsService, RewardService $rewardService)
    {
        $this->info('Testing Rewards System...');

        // Test 1: Check if users exist
        $users = User::all();
        $this->info("Found {$users->count()} users");

        if ($users->isEmpty()) {
            $this->error('No users found. Please create some users first.');
            return 1;
        }

        $user = $users->first();
        $this->info("Testing with user: {$user->name} ({$user->email})");

        // Test 2: Check if rewards exist
        $rewards = Reward::all();
        $this->info("Found {$rewards->count()} rewards");

        if ($rewards->isEmpty()) {
            $this->error('No rewards found. Please run the RewardSeeder first.');
            return 1;
        }

        // Test 3: Check user points
        $currentPoints = $pointsService->getCurrentPoints($user);
        $this->info("User has {$currentPoints} points");

        // Test 4: Check available rewards
        $availableRewards = $rewardService->getAvailableRewards($user);
        $this->info("User can redeem {$availableRewards->count()} rewards");

        // Test 5: Try to redeem a reward if user has enough points
        $reward = $rewards->first();
        $this->info("Testing redemption of: {$reward->name} (requires {$reward->points_required} points)");

        if ($currentPoints >= $reward->points_required) {
            try {
                $userReward = $rewardService->redeemReward($user, $reward);
                $this->info("✅ Successfully redeemed reward! Voucher code: {$userReward->voucher_code}");
                
                // Check new points balance
                $newPoints = $pointsService->getCurrentPoints($user);
                $this->info("New points balance: {$newPoints}");
            } catch (\Exception $e) {
                $this->error("❌ Failed to redeem reward: {$e->getMessage()}");
            }
        } else {
            $this->warn("⚠️  User doesn't have enough points to redeem this reward");
        }

        // Test 6: Check user rewards
        $userRewards = $rewardService->getUserRewards($user);
        $this->info("User has {$userRewards->count()} redeemed rewards");

        $this->info('✅ Rewards system test completed!');
        return 0;
    }
}
