<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Reward;
use App\Models\RewardCategory;
use App\Models\UserReward;
use App\Models\UserPoint;
use App\Models\PointTransaction;
use App\Services\PointsService;
use App\Services\RewardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

class IT006_PointsSystemToRewardRedemptionTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test the complete flow from earning points to redeeming rewards and managing vouchers
     * 
     * Test Procedure:
     * 1. User logs in to the system
     * 2. Navigate to "Rewards" page from user profile page
     * 3. View available rewards with filtering options
     * 4. Check current points balance display
     * 5. Filter rewards by category (e.g., "Food & Beverage")
     * 6. Toggle "Redeemable only" filter to show only affordable rewards
     * 7. Click on a reward to view details
     * 8. Verify reward details modal shows all information
     * 9. Click "Redeem now" button
     * 10. Confirm redemption in confirmation modal
     * 11. Verify points are deducted from user account
     * 12. Verify voucher is created with unique code
     * 13. Verify user is redirected to "My Vouchers" page
     * 14. Check that redeemed voucher appears in vouchers list
     * 15. Click on voucher to view details
     * 16. Verify voucher details show all information
     * 17. Test voucher status filtering (Active, Used, Expired)
     * 18. Verify points history shows the redemption transaction
     */
    public function test_complete_points_system_to_reward_redemption_flow()
    {
        // Test Data
        $rewardData = [
            'name' => 'Starbucks Coffee Voucher',
            'description' => 'Get a free coffee at any Starbucks outlet',
            'points_required' => 50,
            'validity_days' => 30,
            'stock_quantity' => 100,
            'status' => 'active'
        ];

        // Create user with points
        $user = User::factory()->create([
            'name' => 'Mary Hong',
            'email' => 'mary@gmail.com',
            'password' => Hash::make('Password123!'),
            'role' => 'user'
        ]);

        // Create user points record
        UserPoint::create([
            'user_id' => $user->id,
            'current_points' => 75,
            'total_earned_points' => 100,
            'total_spent_points' => 25
        ]);

        // Create reward category
        $category = RewardCategory::create([
            'name' => 'Food & Beverage',
            'description' => 'Food and beverage rewards',
            'icon' => '🍕'
        ]);

        // Create reward
        $reward = Reward::create(array_merge($rewardData, [
            'category_id' => $category->id,
            'voucher_code_prefix' => 'FINDME',
            'redeemed_count' => 0
        ]));

        // Step 1: User logs in to the system
        $this->actingAs($user);

        // Step 2: Navigate to "Rewards" page from user profile page
        $response = $this->get('/rewards');
        $response->assertStatus(200);
        $response->assertSee('Rewards/Index');

        // Step 3-4: View available rewards with filtering options and check current points balance display
        $responseData = $response->viewData('page')['props'];
        $this->assertEquals(75, $responseData['currentPoints']);
        $this->assertCount(1, $responseData['rewards']);

        // Step 5: Filter rewards by category (e.g., "Food & Beverage")
        $response = $this->get('/rewards?category=' . $category->id);
        $response->assertStatus(200);
        $responseData = $response->viewData('page')['props'];
        $this->assertEquals($category->id, $responseData['selectedCategory']);

        // Step 6: Toggle "Redeemable only" filter to show only affordable rewards
        $response = $this->get('/rewards?redeemable_only=true');
        $response->assertStatus(200);
        $responseData = $response->viewData('page')['props'];
        $this->assertTrue($responseData['showRedeemableOnly'] === true || $responseData['showRedeemableOnly'] === 'true');

        // Step 7-8: Click on a reward to view details and verify reward details modal shows all information
        $response = $this->get('/rewards/' . $reward->id);
        $response->assertStatus(200);
        $responseData = $response->json();
        $this->assertEquals('Starbucks Coffee Voucher', $responseData['reward']['name']);
        $this->assertEquals(50, $responseData['reward']['points_required']);
        $this->assertEquals(75, $responseData['currentPoints']);
        $this->assertTrue($responseData['canRedeem']);

        // Step 9-10: Click "Redeem now" button and confirm redemption in confirmation modal
        $response = $this->post('/rewards/' . $reward->id . '/redeem');

        // Step 11-12: Verify points are deducted from user account and voucher is created with unique code
        $response->assertStatus(200);
        $responseData = $response->json();
        $this->assertTrue($responseData['success']);
        $this->assertEquals('Reward redeemed successfully!', $responseData['message']);
        $this->assertEquals(25, $responseData['newPointsBalance']);

        // Verify points are deducted
        $user->refresh();
        $userPoint = UserPoint::where('user_id', $user->id)->first();
        $this->assertEquals(25, $userPoint->current_points);
        $this->assertEquals(75, $userPoint->total_spent_points);

        // Verify voucher is created
        $this->assertDatabaseHas('user_rewards', [
            'user_id' => $user->id,
            'reward_id' => $reward->id,
            'points_spent' => 50,
            'status' => 'active'
        ]);

        $userReward = UserReward::where('user_id', $user->id)->first();
        $this->assertNotNull($userReward);
        $this->assertStringStartsWith('FINDME', $userReward->voucher_code);
        $this->assertEquals(50, $userReward->points_spent);

        // Step 13: Verify user is redirected to "My Vouchers" page
        $response = $this->get('/rewards/my-vouchers');
        $response->assertStatus(200);
        $response->assertSee('Rewards/MyVouchers');

        // Step 14: Check that redeemed voucher appears in vouchers list
        $responseData = $response->viewData('page')['props'];
        $this->assertCount(1, $responseData['vouchers']);
        $this->assertEquals('Starbucks Coffee Voucher', $responseData['vouchers'][0]['reward']['name']);

        // Step 15-16: Click on voucher to view details and verify voucher details show all information
        $voucher = $responseData['vouchers'][0];
        $this->assertStringStartsWith('FINDME', $voucher['voucher_code']);
        $this->assertEquals(50, $voucher['points_spent']);
        $this->assertEquals('active', $voucher['status']);

        // Step 17: Test voucher status filtering (Active, Used, Expired)
        $response = $this->get('/rewards/my-vouchers?status=active');
        $response->assertStatus(200);
        $responseData = $response->viewData('page')['props'];
        $this->assertEquals('active', $responseData['selectedStatus']);

        // Step 18: Verify points history shows the redemption transaction
        $response = $this->get('/rewards/points/history');
        $response->assertStatus(200);
        $responseData = $response->json();
        $this->assertCount(1, $responseData['history']);
        $this->assertEquals('spent', $responseData['history'][0]['type']);
        $this->assertEquals('reward_redemption', $responseData['history'][0]['action']);
        $this->assertEquals('Redeemed reward: Starbucks Coffee Voucher', $responseData['history'][0]['description']);
        $this->assertEquals(50, $responseData['history'][0]['points']);
    }

    /**
     * Test insufficient points for redemption
     */
    public function test_insufficient_points_for_redemption()
    {
        $user = User::factory()->create([
            'name' => 'Mary Hong',
            'email' => 'mary@gmail.com',
            'password' => Hash::make('Password123!'),
            'role' => 'user'
        ]);

        // Create user points record with insufficient points
        UserPoint::create([
            'user_id' => $user->id,
            'current_points' => 25,
            'total_earned_points' => 50,
            'total_spent_points' => 25
        ]);

        $category = RewardCategory::create([
            'name' => 'Food & Beverage',
            'description' => 'Food and beverage rewards',
            'icon' => '🍕'
        ]);

        $reward = Reward::create([
            'category_id' => $category->id,
            'name' => 'Starbucks Coffee Voucher',
            'description' => 'Get a free coffee at any Starbucks outlet',
            'points_required' => 50,
            'stock_quantity' => 100,
            'redeemed_count' => 0,
            'voucher_code_prefix' => 'FINDME',
            'validity_days' => 30,
            'status' => 'active'
        ]);

        $this->actingAs($user);

        $response = $this->post('/rewards/' . $reward->id . '/redeem');

        $response->assertStatus(400);
        $responseData = $response->json();
        $this->assertFalse($responseData['success']);
        $this->assertEquals('Insufficient points', $responseData['message']);
    }

    /**
     * Test reward out of stock
     */
    public function test_reward_out_of_stock()
    {
        $user = User::factory()->create([
            'name' => 'Mary Hong',
            'email' => 'mary@gmail.com',
            'password' => Hash::make('Password123!'),
            'role' => 'user'
        ]);

        UserPoint::create([
            'user_id' => $user->id,
            'current_points' => 100,
            'total_earned_points' => 100,
            'total_spent_points' => 0
        ]);

        $category = RewardCategory::create([
            'name' => 'Food & Beverage',
            'description' => 'Food and beverage rewards',
            'icon' => '🍕'
        ]);

        $reward = Reward::create([
            'category_id' => $category->id,
            'name' => 'Starbucks Coffee Voucher',
            'description' => 'Get a free coffee at any Starbucks outlet',
            'points_required' => 50,
            'stock_quantity' => 1,
            'redeemed_count' => 1, // Out of stock
            'voucher_code_prefix' => 'FINDME',
            'validity_days' => 30,
            'status' => 'active'
        ]);

        $this->actingAs($user);

        $response = $this->post('/rewards/' . $reward->id . '/redeem');

        $response->assertStatus(400);
        $responseData = $response->json();
        $this->assertFalse($responseData['success']);
        $this->assertEquals('Reward is not available', $responseData['message']);
    }

    /**
     * Test inactive reward cannot be redeemed
     */
    public function test_inactive_reward_cannot_be_redeemed()
    {
        $user = User::factory()->create([
            'name' => 'Mary Hong',
            'email' => 'mary@gmail.com',
            'password' => Hash::make('Password123!'),
            'role' => 'user'
        ]);

        UserPoint::create([
            'user_id' => $user->id,
            'current_points' => 100,
            'total_earned_points' => 100,
            'total_spent_points' => 0
        ]);

        $category = RewardCategory::create([
            'name' => 'Food & Beverage',
            'description' => 'Food and beverage rewards',
            'icon' => '🍕'
        ]);

        $reward = Reward::create([
            'category_id' => $category->id,
            'name' => 'Starbucks Coffee Voucher',
            'description' => 'Get a free coffee at any Starbucks outlet',
            'points_required' => 50,
            'stock_quantity' => 100,
            'redeemed_count' => 0,
            'voucher_code_prefix' => 'FINDME',
            'validity_days' => 30,
            'status' => 'inactive' // Inactive reward
        ]);

        $this->actingAs($user);

        $response = $this->post('/rewards/' . $reward->id . '/redeem');

        $response->assertStatus(400);
        $responseData = $response->json();
        $this->assertFalse($responseData['success']);
        $this->assertEquals('Reward is not available', $responseData['message']);
    }

    /**
     * Test voucher status filtering
     */
    public function test_voucher_status_filtering()
    {
        $user = User::factory()->create([
            'name' => 'Mary Hong',
            'email' => 'mary@gmail.com',
            'password' => Hash::make('Password123!'),
            'role' => 'user'
        ]);

        $category = RewardCategory::create([
            'name' => 'Food & Beverage',
            'description' => 'Food and beverage rewards',
            'icon' => '🍕'
        ]);

        $reward = Reward::create([
            'category_id' => $category->id,
            'name' => 'Starbucks Coffee Voucher',
            'description' => 'Get a free coffee at any Starbucks outlet',
            'points_required' => 50,
            'stock_quantity' => 100,
            'redeemed_count' => 0,
            'voucher_code_prefix' => 'FINDME',
            'validity_days' => 30,
            'status' => 'active'
        ]);

        // Create vouchers with different statuses
        UserReward::create([
            'user_id' => $user->id,
            'reward_id' => $reward->id,
            'voucher_code' => 'FINDME20240120123456ABCDEF',
            'points_spent' => 50,
            'redeemed_at' => now()->subDays(10),
            'expires_at' => now()->addDays(20),
            'status' => 'active'
        ]);

        UserReward::create([
            'user_id' => $user->id,
            'reward_id' => $reward->id,
            'voucher_code' => 'FINDME20240120123456ABCDEG',
            'points_spent' => 50,
            'redeemed_at' => now()->subDays(20),
            'expires_at' => now()->subDays(5),
            'status' => 'expired'
        ]);

        UserReward::create([
            'user_id' => $user->id,
            'reward_id' => $reward->id,
            'voucher_code' => 'FINDME20240120123456ABCDEH',
            'points_spent' => 50,
            'redeemed_at' => now()->subDays(15),
            'expires_at' => now()->addDays(15),
            'status' => 'used',
            'used_at' => now()->subDays(5)
        ]);

        $this->actingAs($user);

        // Test active vouchers filter
        $response = $this->get('/rewards/my-vouchers?status=active');
        $response->assertStatus(200);
        $responseData = $response->viewData('page')['props'];
        $this->assertCount(1, $responseData['vouchers']);
        $this->assertEquals('active', $responseData['vouchers'][0]['status']);

        // Test expired vouchers filter
        $response = $this->get('/rewards/my-vouchers?status=expired');
        $response->assertStatus(200);
        $responseData = $response->viewData('page')['props'];
        $this->assertCount(1, $responseData['vouchers']);
        $this->assertEquals('expired', $responseData['vouchers'][0]['status']);

        // Test used vouchers filter
        $response = $this->get('/rewards/my-vouchers?status=used');
        $response->assertStatus(200);
        $responseData = $response->viewData('page')['props'];
        $this->assertCount(1, $responseData['vouchers']);
        $this->assertEquals('used', $responseData['vouchers'][0]['status']);
    }

    /**
     * Test points history functionality
     */
    public function test_points_history_functionality()
    {
        $user = User::factory()->create([
            'name' => 'Mary Hong',
            'email' => 'mary@gmail.com',
            'password' => Hash::make('Password123!'),
            'role' => 'user'
        ]);

        // Create user points record
        UserPoint::create([
            'user_id' => $user->id,
            'current_points' => 50,
            'total_earned_points' => 100,
            'total_spent_points' => 50
        ]);

        // Create some point transactions
        PointTransaction::create([
            'user_id' => $user->id,
            'type' => 'earned',
            'points' => 50,
            'action' => 'registration',
            'description' => 'Account registration bonus'
        ]);

        PointTransaction::create([
            'user_id' => $user->id,
            'type' => 'spent',
            'points' => 50,
            'action' => 'reward_redemption',
            'description' => 'Redeemed reward: Starbucks Coffee Voucher'
        ]);

        $this->actingAs($user);

        $response = $this->get('/rewards/points/history');
        $response->assertStatus(200);
        $responseData = $response->json();
        $this->assertEquals(50, $responseData['currentPoints']);
        $this->assertCount(2, $responseData['history']);

        // Check earned transaction
        $earnedTransaction = collect($responseData['history'])->firstWhere('type', 'earned');
        $this->assertEquals('registration', $earnedTransaction['action']);
        $this->assertEquals(50, $earnedTransaction['points']);

        // Check spent transaction
        $spentTransaction = collect($responseData['history'])->firstWhere('type', 'spent');
        $this->assertEquals('reward_redemption', $spentTransaction['action']);
        $this->assertEquals(50, $spentTransaction['points']);
    }

    /**
     * Test reward category filtering
     */
    public function test_reward_category_filtering()
    {
        $user = User::factory()->create([
            'name' => 'Mary Hong',
            'email' => 'mary@gmail.com',
            'password' => Hash::make('Password123!'),
            'role' => 'user'
        ]);

        UserPoint::create([
            'user_id' => $user->id,
            'current_points' => 100,
            'total_earned_points' => 100,
            'total_spent_points' => 0
        ]);

        // Create categories
        $foodCategory = RewardCategory::create([
            'name' => 'Food & Beverage',
            'description' => 'Food and beverage rewards',
            'icon' => '🍕'
        ]);

        $shoppingCategory = RewardCategory::create([
            'name' => 'Shopping',
            'description' => 'Shopping rewards',
            'icon' => '🛍️'
        ]);

        // Create rewards in different categories
        Reward::create([
            'category_id' => $foodCategory->id,
            'name' => 'Starbucks Coffee Voucher',
            'description' => 'Get a free coffee at any Starbucks outlet',
            'points_required' => 50,
            'stock_quantity' => 100,
            'redeemed_count' => 0,
            'voucher_code_prefix' => 'FINDME',
            'validity_days' => 30,
            'status' => 'active'
        ]);

        Reward::create([
            'category_id' => $shoppingCategory->id,
            'name' => 'Shopping Voucher',
            'description' => 'Get a discount at any store',
            'points_required' => 75,
            'stock_quantity' => 50,
            'redeemed_count' => 0,
            'voucher_code_prefix' => 'FINDME',
            'validity_days' => 60,
            'status' => 'active'
        ]);

        $this->actingAs($user);

        // Test filtering by food category
        $response = $this->get('/rewards?category=' . $foodCategory->id);
        $response->assertStatus(200);
        $responseData = $response->viewData('page')['props'];
        $this->assertEquals($foodCategory->id, $responseData['selectedCategory']);
        $this->assertCount(1, $responseData['rewards']);
        $this->assertEquals('Starbucks Coffee Voucher', $responseData['rewards'][0]['name']);

        // Test filtering by shopping category
        $response = $this->get('/rewards?category=' . $shoppingCategory->id);
        $response->assertStatus(200);
        $responseData = $response->viewData('page')['props'];
        $this->assertEquals($shoppingCategory->id, $responseData['selectedCategory']);
        $this->assertCount(1, $responseData['rewards']);
        $this->assertEquals('Shopping Voucher', $responseData['rewards'][0]['name']);
    }

    /**
     * Test redeemable only filter
     */
    public function test_redeemable_only_filter()
    {
        $user = User::factory()->create([
            'name' => 'Mary Hong',
            'email' => 'mary@gmail.com',
            'password' => Hash::make('Password123!'),
            'role' => 'user'
        ]);

        UserPoint::create([
            'user_id' => $user->id,
            'current_points' => 50,
            'total_earned_points' => 50,
            'total_spent_points' => 0
        ]);

        $category = RewardCategory::create([
            'name' => 'Food & Beverage',
            'description' => 'Food and beverage rewards',
            'icon' => '🍕'
        ]);

        // Create affordable reward
        Reward::create([
            'category_id' => $category->id,
            'name' => 'Cheap Coffee Voucher',
            'description' => 'Get a free coffee',
            'points_required' => 30,
            'stock_quantity' => 100,
            'redeemed_count' => 0,
            'voucher_code_prefix' => 'FINDME',
            'validity_days' => 30,
            'status' => 'active'
        ]);

        // Create expensive reward
        Reward::create([
            'category_id' => $category->id,
            'name' => 'Expensive Voucher',
            'description' => 'Get an expensive reward',
            'points_required' => 100,
            'stock_quantity' => 50,
            'redeemed_count' => 0,
            'voucher_code_prefix' => 'FINDME',
            'validity_days' => 60,
            'status' => 'active'
        ]);

        $this->actingAs($user);

        // Test redeemable only filter
        $response = $this->get('/rewards?redeemable_only=true');
        $response->assertStatus(200);
        $responseData = $response->viewData('page')['props'];
        $this->assertTrue($responseData['showRedeemableOnly'] === true || $responseData['showRedeemableOnly'] === 'true');
        $this->assertCount(1, $responseData['rewards']);
        $this->assertEquals('Cheap Coffee Voucher', $responseData['rewards'][0]['name']);
    }
}
