<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Reward;
use App\Models\User;
use App\Services\RewardService;
use App\Services\PointsService;

class TestImageDisplay extends Command
{
    protected $signature = 'test:image-display';
    protected $description = 'Test image display functionality';

    public function handle(RewardService $rewardService, PointsService $pointsService)
    {
        $this->info('Testing image display functionality...');
        
        // Get a user
        $user = User::first();
        if (!$user) {
            $this->error('No users found. Please create a user first.');
            return 1;
        }
        
        $this->info("Testing with user: {$user->name}");
        
        // Test getAvailableRewards
        $this->info("\n1. Testing getAvailableRewards...");
        $availableRewards = $rewardService->getAvailableRewards($user);
        
        foreach ($availableRewards as $reward) {
            $this->line("  - {$reward->name}:");
            $this->line("    Image Path: " . ($reward->image_path ?: 'No image'));
            $this->line("    Image URL: " . ($reward->image_url ?: 'No URL'));
            $this->line("    Has image_url property: " . (isset($reward->image_url) ? 'Yes' : 'No'));
        }
        
        // Test getAllRewards (for admin)
        $this->info("\n2. Testing getAllRewards (admin view)...");
        $allRewards = $rewardService->getAllRewards();
        
        foreach ($allRewards as $reward) {
            $this->line("  - {$reward->name}:");
            $this->line("    Image Path: " . ($reward->image_path ?: 'No image'));
            $this->line("    Image URL: " . ($reward->image_url ?: 'No URL'));
            $this->line("    Has image_url property: " . (isset($reward->image_url) ? 'Yes' : 'No'));
        }
        
        $this->info("\n✅ Image display test completed!");
        
        return 0;
    }
}
