<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\CommunityProject;
use App\Models\ProjectApplication;
use App\Services\PointsService;

class TestProjectStatusRollback extends Command
{
    protected $signature = 'test:project-status-rollback';
    protected $description = 'Test project status rollback and points deduction';

    public function handle()
    {
        $this->info('🧪 Testing Project Status Rollback...');

        // Create test user if needed
        $user = User::where('email', 'test@example.com')->first();
        if (!$user) {
            $this->warn('⚠️  Creating test user...');
            $user = User::create([
                'name' => 'Test User',
                'email' => 'test@example.com',
                'password' => bcrypt('password'),
                'role' => 'user'
            ]);
        }

        // Initialize points for user
        $pointsService = app(PointsService::class);
        $userPoints = $pointsService->getCurrentPoints($user);
        $this->info("📊 User current points: {$userPoints}");

        // Create test project
        $this->info('🏗️  Creating test project...');
        $project = CommunityProject::create([
            'title' => 'Test Project for Rollback',
            'description' => 'Test project to verify points rollback',
            'location' => 'Test Location',
            'date' => now()->addDays(7),
            'time' => '09:00',
            'duration' => '4 hours',
            'volunteers_needed' => 5,
            'points_reward' => 25,
            'category' => 'search',
            'status' => 'active',
            'created_by' => $user->id
        ]);

        // Create approved application
        $this->info('📝 Creating approved application...');
        $application = ProjectApplication::create([
            'user_id' => $user->id,
            'community_project_id' => $project->id,
            'experience' => 'Test experience',
            'motivation' => 'Test motivation',
            'status' => 'approved'
        ]);

        // Award some initial points
        $this->info('🎁 Awarding initial points...');
        $pointsService->awardPoints($user, 50, 'test', 'Initial test points');
        $userPoints = $pointsService->getCurrentPoints($user);
        $this->info("📊 User points after initial award: {$userPoints}");

        // Test 1: Complete the project (should award points)
        $this->info('✅ Test 1: Completing project...');
        $project->update(['status' => 'completed']);
        
        // Simulate the controller logic
        $approvedApplications = $project->approvedApplications;
        foreach ($approvedApplications as $app) {
            $user = $app->user;
            $pointsService->awardCommunityProjectPoints(
                $user,
                $project->id,
                $project->title,
                $project->points_reward
            );
        }
        
        $userPoints = $pointsService->getCurrentPoints($user);
        $this->info("📊 User points after project completion: {$userPoints}");

        // Test 2: Revert to active (should deduct points)
        $this->info('🔄 Test 2: Reverting project status...');
        $project->update(['status' => 'active']);
        
        // Simulate the controller rollback logic
        foreach ($approvedApplications as $app) {
            $user = $app->user;
            $pointsService->deductPoints(
                $user,
                $project->points_reward,
                'project_status_reverted',
                "Project status reverted from completed to active"
            );
        }
        
        $userPoints = $pointsService->getCurrentPoints($user);
        $this->info("📊 User points after status reversion: {$userPoints}");

        // Test 3: Complete again (should award points again)
        $this->info('✅ Test 3: Completing project again...');
        $project->update(['status' => 'completed']);
        
        foreach ($approvedApplications as $app) {
            $user = $app->user;
            $pointsService->awardCommunityProjectPoints(
                $user,
                $project->id,
                $project->title,
                $project->points_reward
            );
        }
        
        $userPoints = $pointsService->getCurrentPoints($user);
        $this->info("📊 User points after second completion: {$userPoints}");

        // Cleanup
        $this->info('🧹 Cleaning up test data...');
        $application->delete();
        $project->delete();

        $this->info('🎉 Project status rollback test completed!');
        return 0;
    }
}
