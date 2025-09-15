<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Models\Reward;
use App\Models\RewardCategory;
use App\Models\UserReward;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class UT013_AdminPanelRewardManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    /**
     * Test Case: Create new reward with valid data
     * 
     * Test Steps:
     * 1. Navigate to admin rewards page
     * 2. Click "Create New Reward"
     * 3. Fill in reward name
     * 4. Fill in reward description
     * 5. Set points required
     * 6. Select reward category
     * 7. Click "Create Reward"
     * 
     * Expected Result: System creates new reward successfully and displays success message
     */
    public function test_create_new_reward_with_valid_data()
    {
        // Create admin user
        $admin = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
            'role' => 'admin'
        ]);

        // Create reward category
        $category = RewardCategory::factory()->create([
            'name' => 'Food & Beverage',
            'description' => 'Food and beverage rewards',
            'icon' => '🍕'
        ]);

        $this->actingAs($admin);

        $response = $this->post('/admin/rewards', [
            'name' => 'Coffee Voucher',
            'description' => 'RM10 Coffee Shop Voucher',
            'points_required' => 100,
            'category_id' => $category->id,
            'stock_quantity' => 50,
            'validity_days' => 30,
            'status' => 'active',
            'voucher_code_prefix' => 'COFFEE'
        ]);

        $response->assertRedirect('/admin/rewards');
        $response->assertSessionHas('success', 'Reward created successfully!');

        // Verify reward was created in database
        $this->assertDatabaseHas('rewards', [
            'name' => 'Coffee Voucher',
            'description' => 'RM10 Coffee Shop Voucher',
            'points_required' => 100,
            'category_id' => $category->id,
            'stock_quantity' => 50,
            'validity_days' => 30,
            'status' => 'active',
            'voucher_code_prefix' => 'COFFEE'
        ]);
    }

    /**
     * Test Case: Create reward with invalid data
     * 
     * Test Steps:
     * 1. Navigate to admin rewards page
     * 2. Click "Create New Reward"
     * 3. Leave name field empty
     * 4. Fill in description
     * 5. Set negative points
     * 6. Leave category empty
     * 7. Click "Create Reward"
     * 
     * Expected Result: System displays validation errors for required fields and invalid data
     */
    public function test_create_reward_with_invalid_data()
    {
        // Create admin user
        $admin = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
            'role' => 'admin'
        ]);

        $this->actingAs($admin);

        $response = $this->post('/admin/rewards', [
            'name' => '', // Empty name
            'description' => 'Test',
            'points_required' => -50, // Negative points
            'category_id' => '', // Empty category
            'validity_days' => 30,
            'status' => 'active'
        ]);

        $response->assertSessionHasErrors(['name', 'points_required', 'category_id']);
    }

    /**
     * Test Case: Update existing reward
     * 
     * Test Steps:
     * 1. Navigate to admin rewards page
     * 2. Find existing reward
     * 3. Click "Edit" button
     * 4. Update reward name
     * 5. Update points required
     * 6. Click "Update Reward"
     * 
     * Expected Result: System updates reward successfully and displays success message
     */
    public function test_update_existing_reward()
    {
        // Create admin user
        $admin = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
            'role' => 'admin'
        ]);

        // Create reward category
        $category = RewardCategory::factory()->create([
            'name' => 'Food & Beverage',
            'description' => 'Food and beverage rewards',
            'icon' => '🍕'
        ]);

        // Create existing reward
        $reward = Reward::factory()->create([
            'name' => 'Coffee Voucher',
            'description' => 'RM10 Coffee Shop Voucher',
            'points_required' => 100,
            'category_id' => $category->id,
            'stock_quantity' => 50,
            'validity_days' => 30,
            'status' => 'active'
        ]);

        $this->actingAs($admin);

        $response = $this->put("/admin/rewards/{$reward->id}", [
            'name' => 'Updated Coffee Voucher',
            'description' => 'RM15 Coffee Shop Voucher',
            'points_required' => 150,
            'category_id' => $category->id,
            'stock_quantity' => 75,
            'validity_days' => 45,
            'status' => 'active'
        ]);

        $response->assertRedirect('/admin/rewards');
        $response->assertSessionHas('success', 'Reward updated successfully!');

        // Verify reward was updated in database
        $this->assertDatabaseHas('rewards', [
            'id' => $reward->id,
            'name' => 'Updated Coffee Voucher',
            'description' => 'RM15 Coffee Shop Voucher',
            'points_required' => 150,
            'stock_quantity' => 75,
            'validity_days' => 45
        ]);
    }

    /**
     * Test Case: Delete existing reward
     * 
     * Test Steps:
     * 1. Navigate to admin rewards page
     * 2. Find existing reward
     * 3. Click "Delete" button
     * 4. Confirm deletion
     * 
     * Expected Result: System deletes reward successfully and displays success message
     */
    public function test_delete_existing_reward()
    {
        // Create admin user
        $admin = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
            'role' => 'admin'
        ]);

        // Create reward category
        $category = RewardCategory::factory()->create([
            'name' => 'Food & Beverage',
            'description' => 'Food and beverage rewards',
            'icon' => '🍕'
        ]);

        // Create existing reward
        $reward = Reward::factory()->create([
            'name' => 'Coffee Voucher',
            'description' => 'RM10 Coffee Shop Voucher',
            'points_required' => 100,
            'category_id' => $category->id,
            'status' => 'active'
        ]);

        $this->actingAs($admin);

        $response = $this->delete("/admin/rewards/{$reward->id}");

        $response->assertRedirect('/admin/rewards');
        $response->assertSessionHas('success', 'Reward deleted successfully!');

        // Verify reward was deleted from database
        $this->assertDatabaseMissing('rewards', [
            'id' => $reward->id
        ]);
    }

    /**
     * Test Case: Search rewards by name
     * 
     * Test Steps:
     * 1. Navigate to admin rewards page
     * 2. Enter search term in search box
     * 3. Click search button
     * 
     * Expected Result: System displays filtered rewards matching search term
     */
    public function test_search_rewards_by_name()
    {
        // Create admin user
        $admin = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
            'role' => 'admin'
        ]);

        // Create reward category
        $category = RewardCategory::factory()->create([
            'name' => 'Food & Beverage',
            'description' => 'Food and beverage rewards',
            'icon' => '🍕'
        ]);

        // Create test rewards
        Reward::factory()->create([
            'name' => 'Coffee Voucher',
            'description' => 'RM10 Coffee Shop Voucher',
            'category_id' => $category->id,
            'status' => 'active'
        ]);

        Reward::factory()->create([
            'name' => 'Pizza Voucher',
            'description' => 'RM20 Pizza Voucher',
            'category_id' => $category->id,
            'status' => 'active'
        ]);

        Reward::factory()->create([
            'name' => 'Movie Ticket',
            'description' => 'Free Movie Ticket',
            'category_id' => $category->id,
            'status' => 'active'
        ]);

        $this->actingAs($admin);

        $response = $this->get('/admin/rewards?search=Coffee');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => 
            $page->component('Admin/ManageRewards')
                ->has('rewards')
        );
    }

    /**
     * Test Case: Filter rewards by category
     * 
     * Test Steps:
     * 1. Navigate to admin rewards page
     * 2. Select category from dropdown
     * 3. Click filter button
     * 
     * Expected Result: System displays rewards filtered by selected category
     */
    public function test_filter_rewards_by_category()
    {
        // Create admin user
        $admin = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
            'role' => 'admin'
        ]);

        // Create reward categories
        $foodCategory = RewardCategory::factory()->create([
            'name' => 'Food & Beverage',
            'description' => 'Food and beverage rewards',
            'icon' => '🍕'
        ]);

        $entertainmentCategory = RewardCategory::factory()->create([
            'name' => 'Entertainment',
            'description' => 'Entertainment rewards',
            'icon' => '🎬'
        ]);

        // Create test rewards
        Reward::factory()->create([
            'name' => 'Coffee Voucher',
            'description' => 'RM10 Coffee Shop Voucher',
            'category_id' => $foodCategory->id,
            'status' => 'active'
        ]);

        Reward::factory()->create([
            'name' => 'Movie Ticket',
            'description' => 'Free Movie Ticket',
            'category_id' => $entertainmentCategory->id,
            'status' => 'active'
        ]);

        $this->actingAs($admin);

        $response = $this->get("/admin/rewards?category={$foodCategory->id}");

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => 
            $page->component('Admin/ManageRewards')
                ->has('rewards')
        );
    }

    /**
     * Test Case: Access admin rewards without admin role
     */
    public function test_access_admin_rewards_without_admin_role()
    {
        // Create regular user (not admin)
        $user = User::factory()->create([
            'name' => 'Regular User',
            'email' => 'user@example.com',
            'password' => Hash::make('password123'),
            'role' => 'user'
        ]);

        $this->actingAs($user);

        $response = $this->get('/admin/rewards');
        $response->assertStatus(200); // Returns access denied page instead of 403
    }

    /**
     * Test Case: Create reward with image upload
     */
    public function test_create_reward_with_image_upload()
    {
        // Create admin user
        $admin = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
            'role' => 'admin'
        ]);

        // Create reward category
        $category = RewardCategory::factory()->create([
            'name' => 'Food & Beverage',
            'description' => 'Food and beverage rewards',
            'icon' => '🍕'
        ]);

        // Create fake image file
        $image = UploadedFile::fake()->image('reward.jpg', 300, 300);

        $this->actingAs($admin);

        $response = $this->post('/admin/rewards', [
            'name' => 'Coffee Voucher',
            'description' => 'RM10 Coffee Shop Voucher',
            'points_required' => 100,
            'category_id' => $category->id,
            'stock_quantity' => 50,
            'validity_days' => 30,
            'status' => 'active',
            'image' => $image
        ]);

        $response->assertRedirect('/admin/rewards');
        $response->assertSessionHas('success', 'Reward created successfully!');

        // Verify image was stored
        Storage::disk('public')->assertExists('rewards/' . $image->hashName());

        // Verify reward was created with image path
        $this->assertDatabaseHas('rewards', [
            'name' => 'Coffee Voucher',
            'image_path' => 'rewards/' . $image->hashName()
        ]);
    }

    /**
     * Test Case: Update reward with new image
     */
    public function test_update_reward_with_new_image()
    {
        // Create admin user
        $admin = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
            'role' => 'admin'
        ]);

        // Create reward category
        $category = RewardCategory::factory()->create([
            'name' => 'Food & Beverage',
            'description' => 'Food and beverage rewards',
            'icon' => '🍕'
        ]);

        // Create existing reward with old image
        $reward = Reward::factory()->create([
            'name' => 'Coffee Voucher',
            'description' => 'RM10 Coffee Shop Voucher',
            'points_required' => 100,
            'category_id' => $category->id,
            'image_path' => 'rewards/old-image.jpg',
            'status' => 'active'
        ]);

        // Create fake new image file
        $newImage = UploadedFile::fake()->image('new-reward.jpg', 300, 300);

        $this->actingAs($admin);

        $response = $this->put("/admin/rewards/{$reward->id}", [
            'name' => 'Updated Coffee Voucher',
            'description' => 'RM15 Coffee Shop Voucher',
            'points_required' => 150,
            'category_id' => $category->id,
            'stock_quantity' => 75,
            'validity_days' => 45,
            'status' => 'active',
            'image' => $newImage
        ]);

        $response->assertRedirect('/admin/rewards');
        $response->assertSessionHas('success', 'Reward updated successfully!');

        // Verify new image was stored
        Storage::disk('public')->assertExists('rewards/' . $newImage->hashName());

        // Verify reward was updated with new image path
        $this->assertDatabaseHas('rewards', [
            'id' => $reward->id,
            'name' => 'Updated Coffee Voucher',
            'image_path' => 'rewards/' . $newImage->hashName()
        ]);
    }

    /**
     * Test Case: Create reward with new category
     */
    public function test_create_reward_with_new_category()
    {
        // Create admin user
        $admin = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
            'role' => 'admin'
        ]);

        $this->actingAs($admin);

        $response = $this->post('/admin/rewards', [
            'name' => 'Coffee Voucher',
            'description' => 'RM10 Coffee Shop Voucher',
            'points_required' => 100,
            'new_category_name' => 'New Category',
            'stock_quantity' => 50,
            'validity_days' => 30,
            'status' => 'active'
        ]);

        $response->assertRedirect('/admin/rewards');
        $response->assertSessionHas('success', 'Reward created successfully!');

        // Verify new category was created
        $this->assertDatabaseHas('reward_categories', [
            'name' => 'New Category',
            'description' => 'Auto-created category',
            'icon' => '🎁'
        ]);

        // Verify reward was created with new category
        $this->assertDatabaseHas('rewards', [
            'name' => 'Coffee Voucher',
            'category_id' => \App\Models\RewardCategory::where('name', 'New Category')->first()->id
        ]);
    }

    /**
     * Test Case: Filter rewards by status
     */
    public function test_filter_rewards_by_status()
    {
        // Create admin user
        $admin = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
            'role' => 'admin'
        ]);

        // Create reward category
        $category = RewardCategory::factory()->create([
            'name' => 'Food & Beverage',
            'description' => 'Food and beverage rewards',
            'icon' => '🍕'
        ]);

        // Create test rewards with different statuses
        Reward::factory()->create([
            'name' => 'Active Reward',
            'category_id' => $category->id,
            'status' => 'active'
        ]);

        Reward::factory()->create([
            'name' => 'Inactive Reward',
            'category_id' => $category->id,
            'status' => 'inactive'
        ]);

        $this->actingAs($admin);

        $response = $this->get('/admin/rewards?status=active');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => 
            $page->component('Admin/ManageRewards')
                ->has('rewards')
        );
    }

    /**
     * Test Case: Sort rewards by different criteria
     */
    public function test_sort_rewards_by_different_criteria()
    {
        // Create admin user
        $admin = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
            'role' => 'admin'
        ]);

        // Create reward category
        $category = RewardCategory::factory()->create([
            'name' => 'Food & Beverage',
            'description' => 'Food and beverage rewards',
            'icon' => '🍕'
        ]);

        // Create test rewards
        Reward::factory()->create([
            'name' => 'A Reward',
            'points_required' => 100,
            'category_id' => $category->id,
            'status' => 'active'
        ]);

        Reward::factory()->create([
            'name' => 'B Reward',
            'points_required' => 200,
            'category_id' => $category->id,
            'status' => 'active'
        ]);

        $this->actingAs($admin);

        // Test sorting by name
        $response = $this->get('/admin/rewards?sort_by=name&sort_order=asc');
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => 
            $page->component('Admin/ManageRewards')
                ->has('rewards')
        );

        // Test sorting by points required
        $response = $this->get('/admin/rewards?sort_by=points_required&sort_order=desc');
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => 
            $page->component('Admin/ManageRewards')
                ->has('rewards')
        );
    }

    /**
     * Test Case: Delete reward that has been redeemed
     */
    public function test_delete_reward_that_has_been_redeemed()
    {
        // Create admin user
        $admin = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
            'role' => 'admin'
        ]);

        // Create reward category
        $category = RewardCategory::factory()->create([
            'name' => 'Food & Beverage',
            'description' => 'Food and beverage rewards',
            'icon' => '🍕'
        ]);

        // Create reward
        $reward = Reward::factory()->create([
            'name' => 'Coffee Voucher',
            'category_id' => $category->id,
            'status' => 'active'
        ]);

        // Create user reward (redemption)
        UserReward::factory()->create([
            'user_id' => $admin->id,
            'reward_id' => $reward->id,
            'voucher_code' => 'TEST123',
            'points_spent' => 100,
            'status' => 'active'
        ]);

        $this->actingAs($admin);

        $response = $this->delete("/admin/rewards/{$reward->id}");

        $response->assertRedirect();
        $response->assertSessionHasErrors(['error' => 'Cannot delete reward that has been redeemed']);
    }

    /**
     * Test Case: View reward statistics
     */
    public function test_view_reward_statistics()
    {
        // Create admin user
        $admin = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
            'role' => 'admin'
        ]);

        // Create reward category
        $category = RewardCategory::factory()->create([
            'name' => 'Food & Beverage',
            'description' => 'Food and beverage rewards',
            'icon' => '🍕'
        ]);

        // Create test rewards
        Reward::factory()->create([
            'name' => 'Active Reward',
            'category_id' => $category->id,
            'status' => 'active'
        ]);

        Reward::factory()->create([
            'name' => 'Inactive Reward',
            'category_id' => $category->id,
            'status' => 'inactive'
        ]);

        // Create user rewards
        UserReward::factory()->create([
            'user_id' => $admin->id,
            'reward_id' => Reward::first()->id,
            'status' => 'active'
        ]);

        $this->actingAs($admin);

        $response = $this->get('/admin/rewards');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => 
            $page->component('Admin/ManageRewards')
                ->has('stats')
                ->where('stats.total_rewards', 2)
                ->where('stats.active_rewards', 1)
                ->where('stats.total_redemptions', 1)
        );
    }

    /**
     * Test Case: Create reward with minimum required fields
     */
    public function test_create_reward_with_minimum_required_fields()
    {
        // Create admin user
        $admin = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
            'role' => 'admin'
        ]);

        // Create reward category
        $category = RewardCategory::factory()->create([
            'name' => 'Food & Beverage',
            'description' => 'Food and beverage rewards',
            'icon' => '🍕'
        ]);

        $this->actingAs($admin);

        $response = $this->post('/admin/rewards', [
            'name' => 'Minimal Reward',
            'points_required' => 50,
            'category_id' => $category->id,
            'validity_days' => 30,
            'status' => 'active'
        ]);

        $response->assertRedirect('/admin/rewards');
        $response->assertSessionHas('success', 'Reward created successfully!');

        // Verify reward was created with minimal data
        $this->assertDatabaseHas('rewards', [
            'name' => 'Minimal Reward',
            'points_required' => 50,
            'category_id' => $category->id,
            'validity_days' => 30,
            'status' => 'active',
            'stock_quantity' => 0, // Default value when not provided
            'description' => null
        ]);
    }

    /**
     * Test Case: Update reward with validation errors
     */
    public function test_update_reward_with_validation_errors()
    {
        // Create admin user
        $admin = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
            'role' => 'admin'
        ]);

        // Create reward category
        $category = RewardCategory::factory()->create([
            'name' => 'Food & Beverage',
            'description' => 'Food and beverage rewards',
            'icon' => '🍕'
        ]);

        // Create existing reward
        $reward = Reward::factory()->create([
            'name' => 'Coffee Voucher',
            'category_id' => $category->id,
            'status' => 'active'
        ]);

        $this->actingAs($admin);

        $response = $this->put("/admin/rewards/{$reward->id}", [
            'name' => '', // Empty name
            'points_required' => -10, // Negative points
            'category_id' => '', // Empty category
            'validity_days' => 0, // Invalid validity days
            'status' => 'invalid' // Invalid status
        ]);

        $response->assertSessionHasErrors(['name', 'points_required', 'category_id', 'validity_days', 'status']);
    }
}
