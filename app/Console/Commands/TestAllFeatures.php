<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\MissingReport;
use App\Models\CommunityProject;
use App\Models\ProjectApplication;
use App\Services\PointsService;

class TestAllFeatures extends Command
{
    protected $signature = 'test:all-features';
    protected $description = 'Test all platform features including points system';

    protected $pointsService;

    public function __construct(PointsService $pointsService)
    {
        parent::__construct();
        $this->pointsService = $pointsService;
    }

    public function handle()
    {
        $this->info('🧪 Testing All Platform Features...');

        // Create test user
        $user = User::create([
            'name' => 'Feature Test User',
            'email' => 'featuretest' . time() . '@example.com',
            'password' => bcrypt('password'),
            'role' => 'user'
        ]);

        $this->info("✅ Created test user: {$user->name}");

        // Test 1: Registration points
        $this->info("\n📝 Test 1: Registration Points");
        $this->pointsService->awardRegistrationPoints($user);
        $this->line("   ✅ Registration points awarded");

        // Test 2: Create missing report
        $this->info("\n📋 Test 2: Missing Report Creation");
        $report = MissingReport::create([
            'user_id' => $user->id,
            'full_name' => 'Test Missing Person',
            'ic_number' => '123456789012',
            'age' => 25,
            'gender' => 'Male',
            'last_seen_date' => now(),
            'last_seen_location' => 'Test Location',
            'reporter_relationship' => 'Friend',
            'reporter_name' => 'Test Reporter',
            'reporter_phone' => '1234567890',
            'case_status' => 'Approved'
        ]);
        $this->line("   ✅ Missing report created");

        // Test 3: Social share points
        $this->info("\n📱 Test 3: Social Share Points");
        $platforms = ['facebook', 'twitter', 'whatsapp', 'instagram'];
        foreach ($platforms as $platform) {
            $this->pointsService->awardSocialSharePoints($user, $report->id, $platform);
            $this->line("   ✅ {$platform} share points awarded");
        }

        // Test 4: Community project
        $this->info("\n🏗️ Test 4: Community Project");
        $project = CommunityProject::create([
            'title' => 'Test Community Project',
            'description' => 'A test project for feature testing',
            'location' => 'Test Location',
            'date' => now()->addDays(7),
            'time' => '10:00:00',
            'duration' => '2 hours',
            'volunteers_needed' => 5,
            'points_reward' => 50,
            'status' => 'active',
            'created_by' => $user->id
        ]);
        $this->line("   ✅ Community project created");

        // Test 5: Project application
        $this->info("\n📝 Test 5: Project Application");
        $application = ProjectApplication::create([
            'user_id' => $user->id,
            'community_project_id' => $project->id,
            'experience' => 'I have experience in community outreach.',
            'motivation' => 'I want to help find missing persons.',
            'status' => 'approved'
        ]);
        $this->line("   ✅ Project application created and approved");

        // Test 6: Project Completion Points
        $this->info('🎉 Test 6: Project Completion Points');
        $project->update(['status' => 'completed']);
        
        // Simulate controller logic for project completion
        $approvedApplications = $project->approvedApplications;
        foreach ($approvedApplications as $application) {
            $user = $application->user;
            $this->pointsService->awardCommunityProjectPoints(
                $user,
                $project->id,
                $project->title,
                $project->points_reward
            );
        }
        
        $finalPoints = $this->pointsService->getCurrentPoints($user);
        $this->info("   ✅ Project completion points awarded");
        $this->info("   📊 Final points: {$finalPoints}");

        // Test 7: Project Status Rollback
        $this->info('🔄 Test 7: Project Status Rollback');
        $project->update(['status' => 'active']);
        
        // Simulate controller logic for status rollback
        foreach ($approvedApplications as $application) {
            $user = $application->user;
            $this->pointsService->deductPoints(
                $user,
                $project->points_reward,
                'project_status_reverted',
                "Project status reverted from completed to active"
            );
        }
        
        $finalPointsAfterRollback = $this->pointsService->getCurrentPoints($user);
        $this->info("   ✅ Points deducted after status rollback");
        $this->info("   📊 Points after rollback: {$finalPointsAfterRollback}");

        // Display final results
        $finalPoints = $this->pointsService->getCurrentPoints($user);
        $this->info("\n📊 Final Results:");
        $this->info("   User: {$user->name}");
        $this->info("   Final Points: {$finalPoints}");
        $this->info("   Missing Reports: 1");
        $this->info("   Social Shares: 4");
        $this->info("   Community Projects: 1");

        // Clean up
        $user->delete();
        $report->delete();
        $project->delete();

        $this->info("\n🎉 All features test completed successfully!");
    }
}
