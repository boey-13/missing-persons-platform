<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ProjectApplication;
use App\Models\User;
use App\Models\CommunityProject;

class DebugApplication extends Command
{
    protected $signature = 'debug:application {id}';
    protected $description = 'Debug a specific project application';

    public function handle()
    {
        $id = $this->argument('id');
        $this->info("🔍 Debugging application ID: {$id}");
        
        $app = ProjectApplication::find($id);
        if (!$app) {
            $this->error("Application ID {$id} not found");
            return 1;
        }
        
        $this->info("Application found:");
        $this->line("  - ID: {$app->id}");
        $this->line("  - User ID: {$app->user_id}");
        $this->line("  - Project ID: {$app->community_project_id}");
        $this->line("  - Status: {$app->status}");
        $this->line("  - Created: {$app->created_at}");
        
        // Check user
        $user = User::find($app->user_id);
        if ($user) {
            $this->info("  - User exists: {$user->name} ({$user->email})");
        } else {
            $this->error("  - User NOT found (ID: {$app->user_id})");
        }
        
        // Check project
        $project = CommunityProject::find($app->community_project_id);
        if ($project) {
            $this->info("  - Project exists: {$project->title}");
        } else {
            $this->error("  - Project NOT found (ID: {$app->community_project_id})");
        }
        
        // Check with relationships
        $this->info("\nChecking with relationships:");
        $appWithRelations = ProjectApplication::with(['user', 'project'])->find($id);
        
        if ($appWithRelations->user) {
            $this->info("  - User via relationship: {$appWithRelations->user->name}");
        } else {
            $this->error("  - User relationship failed");
        }
        
        if ($appWithRelations->project) {
            $this->info("  - Project via relationship: {$appWithRelations->project->title}");
        } else {
            $this->error("  - Project relationship failed");
        }
        
        return 0;
    }
}
