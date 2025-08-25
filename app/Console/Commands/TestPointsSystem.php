<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\MissingReport;
use App\Models\SightingReport;
use App\Models\CommunityProject;
use App\Models\ProjectApplication;
use App\Models\UserPoint;
use App\Models\PointTransaction;
use App\Services\PointsService;

class TestPointsSystem extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:points-system';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test the points system to ensure all point triggers work correctly';

    protected $pointsService;
    protected $testUser;
    protected $testResults = [];

    public function __construct(PointsService $pointsService)
    {
        parent::__construct();
        $this->pointsService = $pointsService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🎯 开始积分系统测试...');

        // 创建测试用户
        $this->createTestUser();

        // 测试各个积分触发点
        $this->testRegistrationPoints();
        $this->testSightingReportPoints();
        $this->testCommunityProjectPoints();
        $this->testSocialSharePoints();
        $this->testRewardRedemptionPoints();

        // 显示测试结果
        $this->displayResults();

        return 0;
    }

    /**
     * 创建测试用户
     */
    private function createTestUser()
    {
        $timestamp = time();
        $this->testUser = User::create([
            'name' => 'Test User',
            'email' => "test{$timestamp}@example.com",
            'password' => bcrypt('password'),
            'role' => 'user'
        ]);

        $this->info("✅ 创建测试用户: {$this->testUser->name}");
    }

    /**
     * 测试注册积分
     */
    private function testRegistrationPoints()
    {
        $this->info("\n📝 测试注册积分...");
        
        $initialPoints = $this->pointsService->getCurrentPoints($this->testUser);
        $this->pointsService->awardRegistrationPoints($this->testUser);
        $finalPoints = $this->pointsService->getCurrentPoints($this->testUser);
        
        $expected = 10;
        $actual = $finalPoints - $initialPoints;
        
        $this->testResults['registration'] = [
            'expected' => $expected,
            'actual' => $actual,
            'passed' => $expected === $actual
        ];

        $this->line("   预期: +{$expected} 积分");
        $this->line("   实际: +{$actual} 积分");
        $this->line("   结果: " . ($expected === $actual ? "✅ 通过" : "❌ 失败"));
    }

    /**
     * 测试目击报告积分
     */
    private function testSightingReportPoints()
    {
        $this->info("\n👁️ 测试目击报告积分...");
        
        // 创建测试目击报告
        $sighting = SightingReport::create([
            'user_id' => $this->testUser->id,
            'missing_report_id' => 1,
            'location' => 'Test Location',
            'reporter_name' => 'Test Reporter',
            'reporter_phone' => '1234567890',
            'status' => 'Pending'
        ]);

        $initialPoints = $this->pointsService->getCurrentPoints($this->testUser);
        $this->pointsService->awardSightingReportPoints($this->testUser, $sighting->id);
        $finalPoints = $this->pointsService->getCurrentPoints($this->testUser);
        
        $expected = 10;
        $actual = $finalPoints - $initialPoints;
        
        $this->testResults['sighting_report'] = [
            'expected' => $expected,
            'actual' => $actual,
            'passed' => $expected === $actual
        ];

        $this->line("   预期: +{$expected} 积分");
        $this->line("   实际: +{$actual} 积分");
        $this->line("   结果: " . ($expected === $actual ? "✅ 通过" : "❌ 失败"));
    }

    /**
     * 测试社区项目积分
     */
    private function testCommunityProjectPoints()
    {
        $this->info("\n🏗️ 测试社区项目积分...");
        
        // 创建测试项目
        $project = CommunityProject::create([
            'title' => 'Test Project',
            'description' => 'Test Description',
            'location' => 'Test Location',
            'date' => now(),
            'time' => '09:00',
            'duration' => '2 hours',
            'volunteers_needed' => 5,
            'points_reward' => 50,
            'category' => 'search',
            'status' => 'active'
        ]);

        $initialPoints = $this->pointsService->getCurrentPoints($this->testUser);
        $this->pointsService->awardCommunityProjectPoints($this->testUser, $project->id, $project->title, $project->points_reward);
        $finalPoints = $this->pointsService->getCurrentPoints($this->testUser);
        
        $expected = 50;
        $actual = $finalPoints - $initialPoints;
        
        $this->testResults['community_project'] = [
            'expected' => $expected,
            'actual' => $actual,
            'passed' => $expected === $actual
        ];

        $this->line("   预期: +{$expected} 积分");
        $this->line("   实际: +{$actual} 积分");
        $this->line("   结果: " . ($expected === $actual ? "✅ 通过" : "❌ 失败"));
    }

    /**
     * 测试社交分享积分
     */
    private function testSocialSharePoints()
    {
        $this->info("\n📱 测试社交分享积分...");
        
        $initialPoints = $this->pointsService->getCurrentPoints($this->testUser);
        $awarded = $this->pointsService->awardSocialSharePoints($this->testUser, 1, 'facebook');
        $finalPoints = $this->pointsService->getCurrentPoints($this->testUser);
        
        $expected = 1;
        $actual = $finalPoints - $initialPoints;
        
        $this->testResults['social_share'] = [
            'expected' => $expected,
            'actual' => $actual,
            'passed' => $expected === $actual && $awarded
        ];

        $this->line("   预期: +{$expected} 积分");
        $this->line("   实际: +{$actual} 积分");
        $this->line("   结果: " . ($expected === $actual && $awarded ? "✅ 通过" : "❌ 失败"));
    }

    /**
     * 测试奖励兑换积分扣除
     */
    private function testRewardRedemptionPoints()
    {
        $this->info("\n🎁 测试奖励兑换积分扣除...");
        
        $initialPoints = $this->pointsService->getCurrentPoints($this->testUser);
        
        try {
            $this->pointsService->deductRewardPoints($this->testUser, 1, 'Test Reward', 20);
            $finalPoints = $this->pointsService->getCurrentPoints($this->testUser);
            
            $expected = -20;
            $actual = $finalPoints - $initialPoints;
            
            $this->testResults['reward_redemption'] = [
                'expected' => $expected,
                'actual' => $actual,
                'passed' => $expected === $actual
            ];

            $this->line("   预期: {$expected} 积分");
            $this->line("   实际: {$actual} 积分");
            $this->line("   结果: " . ($expected === $actual ? "✅ 通过" : "❌ 失败"));
        } catch (\Exception $e) {
            $this->testResults['reward_redemption'] = [
                'expected' => -20,
                'actual' => 'Exception: ' . $e->getMessage(),
                'passed' => false
            ];
            $this->line("   结果: ❌ 失败 - " . $e->getMessage());
        }
    }

    /**
     * 显示测试结果
     */
    private function displayResults()
    {
        $this->info("\n" . str_repeat("=", 50));
        $this->info("📊 测试结果汇总");
        $this->info(str_repeat("=", 50));

        $totalTests = count($this->testResults);
        $passedTests = count(array_filter($this->testResults, fn($result) => $result['passed']));

        foreach ($this->testResults as $testName => $result) {
            $status = $result['passed'] ? "✅" : "❌";
            $this->line("{$status} {$testName}: ");
            if ($result['passed']) {
                $this->line("   通过 (预期: {$result['expected']}, 实际: {$result['actual']})");
            } else {
                $this->line("   失败 (预期: {$result['expected']}, 实际: {$result['actual']})");
            }
        }

        $this->info("\n📈 总体结果: {$passedTests}/{$totalTests} 测试通过");
        
        if ($passedTests === $totalTests) {
            $this->info("🎉 所有测试通过！积分系统工作正常。");
        } else {
            $this->warn("⚠️ 部分测试失败，请检查积分系统配置。");
        }

        // 显示最终积分余额
        $finalPoints = $this->pointsService->getCurrentPoints($this->testUser);
        $this->info("\n💰 测试用户最终积分余额: {$finalPoints} 积分");

        // 检查通知
        $this->checkNotifications();
    }

    /**
     * 检查通知
     */
    private function checkNotifications()
    {
        $notifications = \App\Models\Notification::where('user_id', $this->testUser->id)
            ->where('type', 'points_earned')
            ->orderBy('created_at', 'desc')
            ->get();

        $this->info("\n🔔 积分通知检查:");
        $this->line("   创建的通知数量: {$notifications->count()}");

        if ($notifications->count() > 0) {
            $this->line("   ✅ 积分通知功能正常工作");
            foreach ($notifications as $notification) {
                $this->line("   - {$notification->title}: {$notification->message}");
            }
        } else {
            $this->warn("   ⚠️ 未找到积分通知，请检查通知功能");
        }
    }
}
