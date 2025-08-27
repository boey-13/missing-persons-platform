<?php

namespace App\Console\Commands;

use App\Models\ProjectApplication;
use Illuminate\Console\Command;

class CleanInvalidApplications extends Command
{
    protected $signature = 'clean:invalid-applications';
    protected $description = 'Clean invalid project applications with missing user or project';

    public function handle()
    {
        $this->info('🧹 Cleaning invalid project applications...');
        
        // Find applications with missing user or project
        $invalidApps = ProjectApplication::whereNull('user_id')
            ->orWhereNull('community_project_id')
            ->get();
            
        $this->info("Found {$invalidApps->count()} invalid applications");
        
        if ($invalidApps->count() > 0) {
            foreach ($invalidApps as $app) {
                $this->line("Deleting application ID: {$app->id}");
                $app->delete();
            }
            $this->info('✅ Cleanup completed');
        } else {
            $this->info('✅ No invalid applications found');
        }
        
        // Show remaining valid applications
        $validCount = ProjectApplication::count();
        $this->info("Remaining valid applications: {$validCount}");
        
        return 0;
    }
}
