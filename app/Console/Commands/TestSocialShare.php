<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\MissingReport;
use App\Services\PointsService;

class TestSocialShare extends Command
{
    protected $signature = 'test:social-share';
    protected $description = 'Test social share functionality';

    protected $pointsService;

    public function __construct(PointsService $pointsService)
    {
        parent::__construct();
        $this->pointsService = $pointsService;
    }

    public function handle()
    {
        $this->info('🧪 Testing Social Share Functionality...');

        // Create test user
        $user = User::create([
            'name' => 'Social Share Test User',
            'email' => 'socialshare' . time() . '@example.com',
            'password' => bcrypt('password'),
            'role' => 'user'
        ]);

        // Create test missing report
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

        $this->info("✅ Created test user: {$user->name}");
        $this->info("✅ Created test report: {$report->full_name}");

        // Test social share points
        $this->info("\n📱 Testing social share points...");

        $platforms = ['facebook', 'twitter', 'whatsapp', 'instagram'];
        $totalPoints = 0;

        foreach ($platforms as $platform) {
            $initialPoints = $this->pointsService->getCurrentPoints($user);
            
            $awarded = $this->pointsService->awardSocialSharePoints($user, $report->id, $platform);
            $finalPoints = $this->pointsService->getCurrentPoints($user);
            
            if ($awarded) {
                $pointsEarned = $finalPoints - $initialPoints;
                $totalPoints += $pointsEarned;
                $this->line("   ✅ {$platform}: +{$pointsEarned} points");
            } else {
                $this->line("   ⚠️ {$platform}: Already shared (no points)");
            }
        }

        // Test duplicate share (should not award points)
        $this->info("\n🔄 Testing duplicate share...");
        $initialPoints = $this->pointsService->getCurrentPoints($user);
        $awarded = $this->pointsService->awardSocialSharePoints($user, $report->id, 'facebook');
        $finalPoints = $this->pointsService->getCurrentPoints($user);
        
        if (!$awarded) {
            $this->line("   ✅ Duplicate share correctly prevented");
        } else {
            $this->line("   ❌ Duplicate share incorrectly awarded points");
        }

        $this->info("\n📊 Results:");
        $this->info("   Total points earned: {$totalPoints}");
        $this->info("   Final balance: {$this->pointsService->getCurrentPoints($user)}");

        // Clean up
        $user->delete();
        $report->delete();

        $this->info("\n🎉 Social share test completed!");
    }
}
