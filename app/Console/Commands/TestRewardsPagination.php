<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Reward;
use App\Models\User;
use App\Services\RewardService;
use App\Services\PointsService;

class TestRewardsPagination extends Command
{
    protected $signature = 'test:rewards-pagination';
    protected $description = 'Test the new rewards pagination and filtering functionality';

    public function handle(RewardService $rewardService, PointsService $pointsService)
    {
        $this->info('Testing rewards pagination and filtering functionality...');
        
        // Get a user
        $user = User::first();
        if (!$user) {
            $this->error('No users found. Please create a user first.');
            return 1;
        }
        
        $this->info("Testing with user: {$user->name}");
        $this->info("User points: " . $pointsService->getCurrentPoints($user));
        
        // Test 1: All rewards with pagination
        $this->info("\n1. Testing all rewards with pagination (page 1)...");
        $allRewardsData = $rewardService->getAvailableRewards($user, null, false, 1);
        
        $this->info("Total rewards: " . $allRewardsData['total']);
        $this->info("Rewards on page 1: " . $allRewardsData['rewards']->count());
        $this->info("Current page: " . $allRewardsData['current_page']);
        $this->info("Last page: " . $allRewardsData['last_page']);
        $this->info("Per page: " . $allRewardsData['per_page']);
        
        // Test 2: Redeemable only
        $this->info("\n2. Testing redeemable only filter...");
        $redeemableData = $rewardService->getAvailableRewards($user, null, true, 1);
        
        $this->info("Total redeemable rewards: " . $redeemableData['total']);
        $this->info("Redeemable rewards on page 1: " . $redeemableData['rewards']->count());
        $this->info("Last page: " . $redeemableData['last_page']);
        
        // Test 3: Category filter
        $this->info("\n3. Testing category filter...");
        $category = \App\Models\RewardCategory::first();
        if ($category) {
            $categoryData = $rewardService->getAvailableRewards($user, $category->id, false, 1);
            $this->info("Category: " . $category->name);
            $this->info("Total rewards in category: " . $categoryData['total']);
            $this->info("Rewards on page 1: " . $categoryData['rewards']->count());
        }
        
        // Test 4: Page 2
        if ($allRewardsData['last_page'] > 1) {
            $this->info("\n4. Testing page 2...");
            $page2Data = $rewardService->getAvailableRewards($user, null, false, 2);
            $this->info("Rewards on page 2: " . $page2Data['rewards']->count());
            $this->info("Current page: " . $page2Data['current_page']);
        }
        
        $this->info("\n✅ Rewards pagination test completed!");
        
        return 0;
    }
}
