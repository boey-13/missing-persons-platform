<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Reward;
use App\Models\RewardCategory;

class AddCheapReward extends Command
{
    protected $signature = 'add:cheap-reward';
    protected $description = 'Add a cheap reward for testing';

    public function handle()
    {
        $this->info('Adding a cheap reward for testing...');
        
        // Get the first category
        $category = RewardCategory::first();
        
        if (!$category) {
            $this->error('No categories found. Please run the RewardSeeder first.');
            return 1;
        }
        
        // Create a cheap reward
        $reward = Reward::create([
            'category_id' => $category->id,
            'name' => 'RM5 Test Voucher',
            'description' => 'A cheap test voucher for testing the rewards system',
            'points_required' => 10,
            'stock_quantity' => 100,
            'redeemed_count' => 0,
            'voucher_code_prefix' => 'TEST',
            'validity_days' => 30,
            'status' => 'active',
        ]);
        
        $this->info("✅ Created cheap reward: {$reward->name} (requires {$reward->points_required} points)");
        
        return 0;
    }
}
