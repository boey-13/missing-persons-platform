<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Reward;

class CheckRewards extends Command
{
    protected $signature = 'check:rewards';
    protected $description = 'Check all rewards and their points requirements';

    public function handle()
    {
        $this->info('Checking all rewards...');
        
        $rewards = Reward::orderBy('points_required')->get();
        
        $this->table(
            ['Name', 'Points Required', 'Status', 'Stock'],
            $rewards->map(function($reward) {
                return [
                    $reward->name,
                    $reward->points_required,
                    $reward->status,
                    $reward->stock_quantity > 0 ? $reward->stock_quantity : 'Unlimited'
                ];
            })
        );
        
        return 0;
    }
}
