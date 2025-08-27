<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ProjectApplication;

class DeleteApplication extends Command
{
    protected $signature = 'delete:application {id}';
    protected $description = 'Delete a specific project application';

    public function handle()
    {
        $id = $this->argument('id');
        $this->info("🗑️  Deleting application ID: {$id}");
        
        $app = ProjectApplication::find($id);
        if (!$app) {
            $this->error("Application ID {$id} not found");
            return 1;
        }
        
        $app->delete();
        $this->info("✅ Application ID {$id} deleted successfully");
        
        return 0;
    }
}
