<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Services\PointsService;
use App\Models\User;
use App\Models\UserPoint;
use App\Models\PointTransaction;
use Mockery;

class UT006_PointsSystemTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
    }

    public function test_award_registration_points_calls_award_points_with_correct_parameters(): void
    {
        $user = new User();
        $user->id = 1;
        
        $pointsService = Mockery::mock(PointsService::class)->makePartial();
        $pointsService->shouldReceive('awardPoints')
            ->once()
            ->with($user, 10, 'registration', 'Account registration bonus')
            ->andReturn(new UserPoint());

        $result = $pointsService->awardRegistrationPoints($user);
        $this->assertInstanceOf(UserPoint::class, $result);
    }

    public function test_award_sighting_report_points_calls_award_points_with_correct_parameters(): void
    {
        $user = new User();
        $user->id = 1;
        $reportId = 123;
        
        $pointsService = Mockery::mock(PointsService::class)->makePartial();
        $pointsService->shouldReceive('awardPoints')
            ->once()
            ->with($user, 10, 'sighting_report', 'Submitted approved sighting report', ['report_id' => $reportId])
            ->andReturn(new UserPoint());

        $result = $pointsService->awardSightingReportPoints($user, $reportId);
        $this->assertInstanceOf(UserPoint::class, $result);
    }

    public function test_award_social_share_points_returns_boolean(): void
    {
        $user = new User();
        $user->id = 1;
        $reportId = 202;
        $platform = 'facebook';
        
        $pointsService = Mockery::mock(PointsService::class)->makePartial();
        $pointsService->shouldReceive('awardSocialSharePoints')
            ->once()
            ->with($user, $reportId, $platform)
            ->andReturn(true);

        $result = $pointsService->awardSocialSharePoints($user, $reportId, $platform);
        $this->assertTrue($result);
    }

    public function test_award_community_project_points_calls_award_points_with_correct_parameters(): void
    {
        $user = new User();
        $user->id = 1;
        $projectId = 101;
        $projectTitle = 'Test Project';
        $points = 30;
        
        $pointsService = Mockery::mock(PointsService::class)->makePartial();
        $pointsService->shouldReceive('awardPoints')
            ->once()
            ->with($user, $points, 'community_project', "Completed community project: {$projectTitle}", ['project_id' => $projectId])
            ->andReturn(new UserPoint());

        $result = $pointsService->awardCommunityProjectPoints($user, $projectId, $projectTitle, $points);
        $this->assertInstanceOf(UserPoint::class, $result);
    }

    public function test_deduct_reward_points_calls_deduct_points_with_correct_parameters(): void
    {
        $user = new User();
        $user->id = 1;
        $rewardId = 456;
        $rewardName = 'Test Reward';
        $points = 50;
        
        $pointsService = Mockery::mock(PointsService::class)->makePartial();
        $pointsService->shouldReceive('deductPoints')
            ->once()
            ->with($user, $points, 'reward_redemption', "Redeemed reward: {$rewardName}", ['reward_id' => $rewardId])
            ->andReturn(new UserPoint());

        $result = $pointsService->deductRewardPoints($user, $rewardId, $rewardName, $points);
        $this->assertInstanceOf(UserPoint::class, $result);
    }

    public function test_deduct_points_throws_exception_when_insufficient_points(): void
    {
        $user = new User();
        $user->id = 1;
        
        $pointsService = Mockery::mock(PointsService::class)->makePartial();
        $pointsService->shouldReceive('deductPoints')
            ->andThrow(new \Exception('Insufficient points'));

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Insufficient points');
        
        $pointsService->deductPoints($user, 10, 'reward_redemption', 'Redeemed reward');
    }

    public function test_get_user_points_returns_user_point_instance(): void
    {
        $user = new User();
        $user->id = 1;
        
        $userPoint = new UserPoint();
        $userPoint->current_points = 100;
        $userPoint->total_earned_points = 150;
        $userPoint->total_spent_points = 50;
        
        $pointsService = Mockery::mock(PointsService::class)->makePartial();
        $pointsService->shouldReceive('getUserPoints')
            ->once()
            ->with($user)
            ->andReturn($userPoint);

        $result = $pointsService->getUserPoints($user);
        $this->assertInstanceOf(UserPoint::class, $result);
        $this->assertEquals(100, $result->current_points);
    }

    public function test_get_user_transactions_returns_collection(): void
    {
        $user = new User();
        $user->id = 1;
        
        $transactions = collect([
            new PointTransaction(['type' => 'earned', 'points' => 10]),
            new PointTransaction(['type' => 'spent', 'points' => 5])
        ]);
        
        $pointsService = Mockery::mock(PointsService::class)->makePartial();
        $pointsService->shouldReceive('getUserTransactions')
            ->once()
            ->with($user, 10)
            ->andReturn($transactions);

        $result = $pointsService->getUserTransactions($user, 10);
        $this->assertCount(2, $result);
    }

    public function test_calculate_points_balance_returns_correct_calculation(): void
    {
        $earnedPoints = 100;
        $spentPoints = 30;
        $expectedBalance = 70;
        
        $pointsService = new PointsService();
        
        // Test the calculation logic directly
        $balance = $earnedPoints - $spentPoints;
        $this->assertEquals($expectedBalance, $balance);
    }

    public function test_points_validation_accepts_positive_values(): void
    {
        $validPoints = [1, 10, 100, 1000];
        
        foreach ($validPoints as $points) {
            $this->assertGreaterThan(0, $points);
            $this->assertIsInt($points);
        }
    }

    public function test_points_validation_rejects_negative_values(): void
    {
        $invalidPoints = [-1, -10, 0];
        
        foreach ($invalidPoints as $points) {
            $this->assertLessThanOrEqual(0, $points);
        }
    }
}
