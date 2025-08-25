<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Reward;

class CheckRewardImages extends Command
{
    protected $signature = 'check:reward-images';
    protected $description = 'Check reward image paths and URLs';

    public function handle()
    {
        $this->info('Checking reward images...');
        
        $rewards = Reward::all();
        
        foreach ($rewards as $reward) {
            $this->line("Reward: {$reward->name}");
            $this->line("  Image Path: " . ($reward->image_path ?: 'No image'));
            $this->line("  Image URL: " . ($reward->image_url ?: 'No URL'));
            $this->line("  File exists: " . ($reward->image_path && file_exists(public_path('storage/' . $reward->image_path)) ? 'Yes' : 'No'));
            $this->line('');
        }
        
        return 0;
    }
}
