<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\CommunityProject;
use App\Models\ProjectApplication;

class TestProjectApplications extends Command
{
    protected $signature = 'test:project-applications';
    protected $description = 'Test project applications functionality';

    public function handle()
    {
        $this->info('🧪 Testing Project Applications...');

        // Check if we have any applications
        $applications = ProjectApplication::with(['user', 'project'])->get();
        $this->info("📊 Found {$applications->count()} project applications");

        if ($applications->count() > 0) {
            $this->info("\n📋 Application Details:");
            foreach ($applications as $app) {
                $this->line("   - ID: {$app->id}");
                $this->line("     Status: {$app->status}");
                $this->line("     User: " . ($app->user ? $app->user->name . " ({$app->user->email})" : "NULL"));
                $this->line("     Project: " . ($app->project ? $app->project->title : "NULL"));
                $this->line("     Created: {$app->created_at}");
                $this->line("");
            }
        }

        // Test the API endpoint
        $this->info('🔍 Testing API endpoint...');
        
        // Create a test admin user if needed
        $admin = User::where('role', 'admin')->first();
        if (!$admin) {
            $this->warn('⚠️  No admin user found. Creating test admin...');
            $admin = User::create([
                'name' => 'Test Admin',
                'email' => 'admin@test.com',
                'password' => bcrypt('password'),
                'role' => 'admin'
            ]);
        }

        // Simulate API call
        $this->info('📡 Simulating API call to /admin/community-projects/applications...');
        
        // Get the controller instance
        $controller = app(\App\Http\Controllers\CommunityProjectController::class);
        
        try {
            // Call the method directly
            $response = $controller->getApplications();
            $data = $response->getData();
            
            $this->info("✅ API call successful!");
            $this->info("📊 Returned " . (is_array($data) ? count($data) : 'unknown') . " applications");
            
            if (is_array($data) && count($data) > 0) {
                $this->info("\n📋 API Response Sample:");
                $sample = $data[0];
                $this->line("   - ID: {$sample->id}");
                $this->line("     Status: {$sample->status}");
                $this->line("     Volunteer: {$sample->volunteerName}");
                $this->line("     Project: {$sample->projectTitle}");
            }
            
        } catch (\Exception $e) {
            $this->error("❌ API call failed: " . $e->getMessage());
            return 1;
        }

        $this->info("\n🎉 Project applications test completed!");
        return 0;
    }
}
