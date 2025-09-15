<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Models\UserPoint;
use App\Models\PointTransaction;
use App\Models\SocialShare;
use App\Models\MissingReport;
use App\Models\SightingReport;
use App\Models\CommunityProject;
use App\Models\Reward;
use App\Services\PointsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

class UT006_PointsSystemTest extends TestCase
{
    use RefreshDatabase;

    protected $pointsService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pointsService = new PointsService();
    }

    /**
     * Test Case: Award points for user registration
     * 
     * Test Steps:
     * 1. User completes registration process
     * 2. System automatically awards points
     * 3. Check user points balance
     * 
     * Expected Result: The system awards 10 points and updates user balance to 10
     */
    public function test_award_points_for_user_registration()
    {
        $user = User::factory()->create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => Hash::make('password123')
        ]);

        // Award registration points
        $userPoint = $this->pointsService->awardRegistrationPoints($user);

        // Verify points were awarded
        $this->assertEquals(10, $userPoint->current_points);
        $this->assertEquals(10, $userPoint->total_earned_points);
        $this->assertEquals(0, $userPoint->total_spent_points);

        // Verify transaction was created
        $this->assertDatabaseHas('point_transactions', [
            'user_id' => $user->id,
            'type' => 'earned',
            'points' => 10,
            'action' => 'registration',
            'description' => 'Account registration bonus'
        ]);
    }

    /**
     * Test Case: Award points for approved sighting report
     * 
     * Test Steps:
     * 1. User submits sighting report
     * 2. Admin approves the report
     * 3. System automatically awards points
     * 4. Check user points balance
     * 
     * Expected Result: The system awards 10 points for approved sighting report
     */
    public function test_award_points_for_approved_sighting_report()
    {
        $user = User::factory()->create([
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'password' => Hash::make('password123')
        ]);

        $missingReport = MissingReport::factory()->create([
            'user_id' => $user->id,
            'case_status' => 'Approved'
        ]);

        $sightingReport = SightingReport::factory()->create([
            'user_id' => $user->id,
            'missing_report_id' => $missingReport->id,
            'status' => 'Approved'
        ]);

        // Award sighting report points
        $userPoint = $this->pointsService->awardSightingReportPoints($user, $sightingReport->id);

        // Verify points were awarded
        $this->assertEquals(10, $userPoint->current_points);
        $this->assertEquals(10, $userPoint->total_earned_points);

        // Verify transaction was created
        $this->assertDatabaseHas('point_transactions', [
            'user_id' => $user->id,
            'type' => 'earned',
            'points' => 10,
            'action' => 'sighting_report',
            'description' => 'Submitted approved sighting report'
        ]);
    }

    /**
     * Test Case: Award points for social media share
     * 
     * Test Steps:
     * 1. User shares missing person case on Facebook
     * 2. System detects social share
     * 3. System automatically awards points
     * 4. Check user points balance
     * 
     * Expected Result: The system awards 1 point for social media share
     */
    public function test_award_points_for_social_media_share()
    {
        $user = User::factory()->create([
            'name' => 'Social User',
            'email' => 'social@example.com',
            'password' => Hash::make('password123')
        ]);

        $missingReport = MissingReport::factory()->create([
            'user_id' => $user->id,
            'case_status' => 'Approved'
        ]);

        // Award social share points
        $result = $this->pointsService->awardSocialSharePoints($user, $missingReport->id, 'facebook');

        // Verify points were awarded
        $this->assertTrue($result);
        $this->assertEquals(1, $this->pointsService->getCurrentPoints($user));

        // Verify transaction was created
        $this->assertDatabaseHas('point_transactions', [
            'user_id' => $user->id,
            'type' => 'earned',
            'points' => 1,
            'action' => 'social_share',
            'description' => 'Shared missing person case on facebook'
        ]);

        // Verify social share record was created
        $this->assertDatabaseHas('social_shares', [
            'user_id' => $user->id,
            'missing_report_id' => $missingReport->id,
            'platform' => 'facebook',
            'points_awarded' => true
        ]);
    }

    /**
     * Test Case: Award points for community project completion
     * 
     * Test Steps:
     * 1. User participates in community project
     * 2. Project is marked as completed
     * 3. System automatically awards points
     * 4. Check user points balance
     * 
     * Expected Result: The system awards 50 points for project completion
     */
    public function test_award_points_for_community_project_completion()
    {
        $user = User::factory()->create([
            'name' => 'Project User',
            'email' => 'project@example.com',
            'password' => Hash::make('password123')
        ]);

        $communityProject = CommunityProject::factory()->create([
            'title' => 'Search Operation',
            'points_reward' => 50
        ]);

        // Award community project points
        $userPoint = $this->pointsService->awardCommunityProjectPoints(
            $user, 
            $communityProject->id, 
            $communityProject->title, 
            $communityProject->points_reward
        );

        // Verify points were awarded
        $this->assertEquals(50, $userPoint->current_points);
        $this->assertEquals(50, $userPoint->total_earned_points);

        // Verify transaction was created
        $this->assertDatabaseHas('point_transactions', [
            'user_id' => $user->id,
            'type' => 'earned',
            'points' => 50,
            'action' => 'community_project',
            'description' => 'Completed community project: Search Operation'
        ]);
    }

    /**
     * Test Case: Deduct points for reward redemption
     * 
     * Test Steps:
     * 1. User selects a reward worth 25 points
     * 2. User clicks "Redeem" button
     * 3. System deducts points
     * 4. Check updated balance
     * 
     * Expected Result: The system deducts 25 points and updates balance to 75
     */
    public function test_deduct_points_for_reward_redemption()
    {
        $user = User::factory()->create([
            'name' => 'Reward User',
            'email' => 'reward@example.com',
            'password' => Hash::make('password123')
        ]);

        // Give user 100 points first
        $this->pointsService->awardPoints($user, 100, 'test', 'Test points');
        
        $reward = Reward::factory()->create([
            'name' => 'Gift Card',
            'points_required' => 25
        ]);

        // Deduct reward points
        $userPoint = $this->pointsService->deductRewardPoints(
            $user, 
            $reward->id, 
            $reward->name, 
            $reward->points_required
        );

        // Verify points were deducted
        $this->assertEquals(75, $userPoint->current_points);
        $this->assertEquals(100, $userPoint->total_earned_points);
        $this->assertEquals(25, $userPoint->total_spent_points);

        // Verify transaction was created
        $this->assertDatabaseHas('point_transactions', [
            'user_id' => $user->id,
            'type' => 'spent',
            'points' => 25,
            'action' => 'reward_redemption',
            'description' => 'Redeemed reward: Gift Card'
        ]);
    }

    /**
     * Test Case: Attempt redemption with insufficient points
     * 
     * Test Steps:
     * 1. User selects a reward worth 25 points
     * 
     * Expected Result: The system will hide the button and turn it into an insufficient point.
     */
    public function test_attempt_redemption_with_insufficient_points()
    {
        $user = User::factory()->create([
            'name' => 'Poor User',
            'email' => 'poor@example.com',
            'password' => Hash::make('password123')
        ]);

        // Give user only 10 points
        $this->pointsService->awardPoints($user, 10, 'test', 'Test points');
        
        $reward = Reward::factory()->create([
            'name' => 'Expensive Gift Card',
            'points_required' => 25
        ]);

        // Check if user has enough points
        $hasEnoughPoints = $this->pointsService->hasEnoughPoints($user, $reward->points_required);

        // Verify user doesn't have enough points
        $this->assertFalse($hasEnoughPoints);
        $this->assertEquals(10, $this->pointsService->getCurrentPoints($user));
    }

    /**
     * Test Case: View points history
     * 
     * Test Steps:
     * 1. User navigates to points history page
     * 2. System displays transaction history
     * 3. User views detailed records
     * 
     * Expected Result: The system displays complete points transaction history
     */
    public function test_view_points_history()
    {
        $user = User::factory()->create([
            'name' => 'History User',
            'email' => 'history@example.com',
            'password' => Hash::make('password123')
        ]);

        // Create multiple transactions
        $this->pointsService->awardRegistrationPoints($user);
        $this->pointsService->awardPoints($user, 5, 'test_action', 'Test action');
        $this->pointsService->deductPoints($user, 2, 'test_deduction', 'Test deduction');

        // Get points history
        $history = $this->pointsService->getPointsHistory($user);

        // Verify history contains all transactions
        $this->assertCount(3, $history);
        $this->assertEquals('test_deduction', $history->first()->action); // Most recent first
        $this->assertEquals('registration', $history->last()->action); // Oldest last
    }

    /**
     * Test Case: Check current points balance
     * 
     * Test Steps:
     * 1. User navigates to profile page
     * 2. System displays current points balance
     * 3. User verifies balance
     * 
     * Expected Result: The system displays current points balance of 75
     */
    public function test_check_current_points_balance()
    {
        $user = User::factory()->create([
            'name' => 'Balance User',
            'email' => 'balance@example.com',
            'password' => Hash::make('password123')
        ]);

        // Award and deduct points
        $this->pointsService->awardPoints($user, 100, 'earned', 'Earned points');
        $this->pointsService->deductPoints($user, 25, 'spent', 'Spent points');

        // Check current balance
        $currentPoints = $this->pointsService->getCurrentPoints($user);

        // Verify balance is correct
        $this->assertEquals(75, $currentPoints);
    }

    /**
     * Test Case: Prevent duplicate points for same action
     * 
     * Test Steps:
     * 1. User shares same report on Twitter again
     * 2. System checks for duplicate action
     * 3. System prevents duplicate points
     * 
     * Expected Result: The system does not award duplicate points for same action
     */
    public function test_prevent_duplicate_points_for_same_action()
    {
        $user = User::factory()->create([
            'name' => 'Duplicate User',
            'email' => 'duplicate@example.com',
            'password' => Hash::make('password123')
        ]);

        $missingReport = MissingReport::factory()->create([
            'user_id' => $user->id,
            'case_status' => 'Approved'
        ]);

        // First share - should award points
        $result1 = $this->pointsService->awardSocialSharePoints($user, $missingReport->id, 'twitter');
        $this->assertTrue($result1);
        $this->assertEquals(1, $this->pointsService->getCurrentPoints($user));

        // Second share - should not award points
        $result2 = $this->pointsService->awardSocialSharePoints($user, $missingReport->id, 'twitter');
        $this->assertFalse($result2);
        $this->assertEquals(1, $this->pointsService->getCurrentPoints($user)); // Still 1 point

        // Verify only one transaction was created
        $this->assertDatabaseCount('point_transactions', 1);
    }

    /**
     * Test Case: Multiple point transactions accumulation
     */
    public function test_multiple_point_transactions_accumulation()
    {
        $user = User::factory()->create([
            'name' => 'Accumulation User',
            'email' => 'accumulation@example.com',
            'password' => Hash::make('password123')
        ]);

        // Award multiple points
        $this->pointsService->awardRegistrationPoints($user); // +10
        $this->pointsService->awardPoints($user, 5, 'bonus', 'Bonus points'); // +5
        $this->pointsService->awardPoints($user, 3, 'referral', 'Referral bonus'); // +3

        // Check total points
        $this->assertEquals(18, $this->pointsService->getCurrentPoints($user));

        // Verify user point record
        $userPoint = UserPoint::where('user_id', $user->id)->first();
        $this->assertEquals(18, $userPoint->current_points);
        $this->assertEquals(18, $userPoint->total_earned_points);
        $this->assertEquals(0, $userPoint->total_spent_points);
    }

    /**
     * Test Case: Points recalculation for data consistency
     */
    public function test_points_recalculation_for_data_consistency()
    {
        $user = User::factory()->create([
            'name' => 'Recalc User',
            'email' => 'recalc@example.com',
            'password' => Hash::make('password123')
        ]);

        // Create transactions directly (simulating data inconsistency)
        PointTransaction::create([
            'user_id' => $user->id,
            'type' => 'earned',
            'points' => 20,
            'action' => 'manual',
            'description' => 'Manual points'
        ]);

        PointTransaction::create([
            'user_id' => $user->id,
            'type' => 'spent',
            'points' => 5,
            'action' => 'manual',
            'description' => 'Manual deduction'
        ]);

        // Recalculate points
        $userPoint = $this->pointsService->recalculateUserPoints($user);

        // Verify recalculated points
        $this->assertEquals(15, $userPoint->current_points);
        $this->assertEquals(20, $userPoint->total_earned_points);
        $this->assertEquals(5, $userPoint->total_spent_points);
    }

    /**
     * Test Case: Social share on different platforms
     */
    public function test_social_share_on_different_platforms()
    {
        $user = User::factory()->create([
            'name' => 'Multi Platform User',
            'email' => 'multiplatform@example.com',
            'password' => Hash::make('password123')
        ]);

        $missingReport = MissingReport::factory()->create([
            'user_id' => $user->id,
            'case_status' => 'Approved'
        ]);

        // Share on different platforms
        $this->pointsService->awardSocialSharePoints($user, $missingReport->id, 'facebook');
        $this->pointsService->awardSocialSharePoints($user, $missingReport->id, 'twitter');
        $this->pointsService->awardSocialSharePoints($user, $missingReport->id, 'instagram');

        // Verify points for each platform
        $this->assertEquals(3, $this->pointsService->getCurrentPoints($user));

        // Verify separate social share records
        $this->assertDatabaseCount('social_shares', 3);
        $this->assertDatabaseHas('social_shares', ['platform' => 'facebook']);
        $this->assertDatabaseHas('social_shares', ['platform' => 'twitter']);
        $this->assertDatabaseHas('social_shares', ['platform' => 'instagram']);
    }

    /**
     * Test Case: Edge case - zero points transaction
     */
    public function test_zero_points_transaction()
    {
        $user = User::factory()->create([
            'name' => 'Zero User',
            'email' => 'zero@example.com',
            'password' => Hash::make('password123')
        ]);

        // Award zero points
        $userPoint = $this->pointsService->awardPoints($user, 0, 'zero', 'Zero points');

        // Verify no change in points
        $this->assertEquals(0, $userPoint->current_points);

        // Verify transaction was still created
        $this->assertDatabaseHas('point_transactions', [
            'user_id' => $user->id,
            'points' => 0,
            'action' => 'zero'
        ]);
    }

    /**
     * Test Case: Large points transaction
     */
    public function test_large_points_transaction()
    {
        $user = User::factory()->create([
            'name' => 'Large Points User',
            'email' => 'large@example.com',
            'password' => Hash::make('password123')
        ]);

        // Award large amount of points
        $largeAmount = 10000;
        $userPoint = $this->pointsService->awardPoints($user, $largeAmount, 'large_bonus', 'Large bonus');

        // Verify large points were awarded
        $this->assertEquals($largeAmount, $userPoint->current_points);
        $this->assertEquals($largeAmount, $userPoint->total_earned_points);
    }

    /**
     * Test Case: Points history with limit
     */
    public function test_points_history_with_limit()
    {
        $user = User::factory()->create([
            'name' => 'Limit User',
            'email' => 'limit@example.com',
            'password' => Hash::make('password123')
        ]);

        // Create more than 50 transactions
        for ($i = 0; $i < 60; $i++) {
            $this->pointsService->awardPoints($user, 1, "action_{$i}", "Action {$i}");
        }

        // Get history with default limit (50)
        $history = $this->pointsService->getPointsHistory($user);
        $this->assertCount(50, $history);

        // Get history with custom limit
        $limitedHistory = $this->pointsService->getPointsHistory($user, 10);
        $this->assertCount(10, $limitedHistory);
    }
}
