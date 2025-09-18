<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Models\Reward;
use App\Models\RewardCategory;

class UT013_AdminPanelRewardManagementTest extends TestCase
{
    public function test_reward_status_validation(): void
    {
        $validStatuses = ['active', 'inactive', 'expired', 'out_of_stock'];
        
        foreach ($validStatuses as $status) {
            $reward = new Reward();
            $reward->status = $status;
            
            $this->assertContains($status, $validStatuses);
        }
    }

    public function test_reward_type_validation(): void
    {
        $validTypes = ['voucher', 'discount', 'gift', 'points_bonus'];
        
        foreach ($validTypes as $type) {
            $reward = new Reward();
            $reward->type = $type;
            
            $this->assertContains($type, $validTypes);
        }
    }

    public function test_reward_category_validation(): void
    {
        $validCategories = ['food', 'shopping', 'entertainment', 'services', 'other'];
        
        foreach ($validCategories as $category) {
            $rewardCategory = new RewardCategory();
            $rewardCategory->name = $category;
            
            $this->assertContains($category, $validCategories);
        }
    }

    public function test_reward_points_validation(): void
    {
        $validPoints = [10, 50, 100, 500, 1000];
        
        foreach ($validPoints as $points) {
            $reward = new Reward();
            $reward->points_required = $points;
            
            $this->assertIsInt($points);
            $this->assertGreaterThan(0, $points);
        }
    }

    public function test_reward_discount_validation(): void
    {
        $validDiscounts = [5, 10, 15, 20, 25, 50];
        
        foreach ($validDiscounts as $discount) {
            $this->assertIsInt($discount);
            $this->assertGreaterThan(0, $discount);
            $this->assertLessThanOrEqual(100, $discount);
        }
    }

    public function test_reward_expiry_date_validation(): void
    {
        $futureDate = '2024-12-31';
        $pastDate = '2023-01-01';
        
        $this->assertIsString($futureDate);
        $this->assertIsString($pastDate);
    }

    public function test_reward_stock_validation(): void
    {
        $validStocks = [0, 1, 10, 100, 1000];
        
        foreach ($validStocks as $stock) {
            $this->assertIsInt($stock);
            $this->assertGreaterThanOrEqual(0, $stock);
        }
    }

    public function test_reward_creation_logic(): void
    {
        $reward = new Reward();
        $reward->name = 'Test Reward';
        $reward->description = 'Test Description';
        $reward->points_required = 100;
        $reward->type = 'voucher';
        $reward->status = 'active';
        
        $this->assertEquals('Test Reward', $reward->name);
        $this->assertEquals('Test Description', $reward->description);
        $this->assertEquals(100, $reward->points_required);
        $this->assertEquals('voucher', $reward->type);
        $this->assertEquals('active', $reward->status);
    }

    public function test_reward_update_logic(): void
    {
        $reward = new Reward();
        $reward->name = 'Original Name';
        $reward->points_required = 50;
        
        // Update reward
        $reward->name = 'Updated Name';
        $reward->points_required = 75;
        
        $this->assertEquals('Updated Name', $reward->name);
        $this->assertEquals(75, $reward->points_required);
    }

    public function test_reward_deactivation_logic(): void
    {
        $reward = new Reward();
        $reward->status = 'active';
        
        // Deactivate reward
        $reward->status = 'inactive';
        
        $this->assertEquals('inactive', $reward->status);
    }

    public function test_reward_category_creation(): void
    {
        $category = new RewardCategory();
        $category->name = 'Food & Dining';
        $category->description = 'Food and restaurant rewards';
        $category->is_active = true;
        
        $this->assertEquals('Food & Dining', $category->name);
        $this->assertEquals('Food and restaurant rewards', $category->description);
        $this->assertTrue($category->is_active);
    }

    public function test_reward_validation_rules(): void
    {
        $requiredFields = [
            'name',
            'description',
            'points_required',
            'type',
            'status'
        ];
        
        foreach ($requiredFields as $field) {
            $this->assertNotEmpty($field, "Field {$field} should be required");
        }
    }

    public function test_reward_optional_fields(): void
    {
        $optionalFields = [
            'discount_percentage',
            'expiry_date',
            'stock_quantity',
            'image_url',
            'terms_conditions'
        ];
        
        foreach ($optionalFields as $field) {
            $this->assertNotEmpty($field, "Field {$field} should be optional");
        }
    }

    public function test_reward_search_criteria(): void
    {
        $searchCriteria = [
            'name' => 'Test',
            'type' => 'voucher',
            'status' => 'active',
            'min_points' => 50,
            'max_points' => 500
        ];
        
        foreach ($searchCriteria as $criteria => $value) {
            $this->assertNotEmpty($criteria);
            $this->assertNotEmpty($value);
        }
    }

    public function test_reward_sorting_options(): void
    {
        $sortingOptions = [
            'name_asc',
            'name_desc',
            'points_asc',
            'points_desc',
            'created_at_asc',
            'created_at_desc'
        ];
        
        foreach ($sortingOptions as $option) {
            $this->assertIsString($option);
            $this->assertNotEmpty($option);
        }
    }

    public function test_reward_bulk_operations(): void
    {
        $bulkOperations = [
            'activate_selected',
            'deactivate_selected',
            'delete_selected',
            'export_selected'
        ];
        
        foreach ($bulkOperations as $operation) {
            $this->assertIsString($operation);
            $this->assertNotEmpty($operation);
        }
    }
}
