php artisan db:seed --class=RewardCategorySeeder<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\RewardCategory;
use App\Models\Reward;

class RewardSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create reward categories
        $categories = [
            [
                'name' => 'E-commerce',
                'description' => 'Online shopping vouchers and discounts',
                'icon' => '🛒',
            ],
            [
                'name' => 'Food & Beverage',
                'description' => 'Restaurant and cafe vouchers',
                'icon' => '🍽️',
            ],
            [
                'name' => 'Entertainment',
                'description' => 'Movie tickets and entertainment vouchers',
                'icon' => '🎬',
            ],
            [
                'name' => 'Transportation',
                'description' => 'Transport and ride-sharing vouchers',
                'icon' => '🚗',
            ],
        ];

        foreach ($categories as $category) {
            RewardCategory::create($category);
        }

        // Get category IDs
        $ecommerceCategory = RewardCategory::where('name', 'E-commerce')->first();
        $foodCategory = RewardCategory::where('name', 'Food & Beverage')->first();
        $entertainmentCategory = RewardCategory::where('name', 'Entertainment')->first();
        $transportCategory = RewardCategory::where('name', 'Transportation')->first();

        // Create rewards
        $rewards = [
            [
                'category_id' => $ecommerceCategory->id,
                'name' => 'RM10 Shopee Voucher',
                'description' => 'Get RM10 off your next purchase on Shopee. Valid for all categories.',
                'points_required' => 150,
                'stock_quantity' => 100,
                'voucher_code_prefix' => 'SHOPEE',
                'validity_days' => 30,
                'status' => 'active',
            ],
            [
                'category_id' => $ecommerceCategory->id,
                'name' => 'RM15 Lazada Voucher',
                'description' => 'Enjoy RM15 discount on Lazada. Minimum spend RM50.',
                'points_required' => 200,
                'stock_quantity' => 50,
                'voucher_code_prefix' => 'LAZADA',
                'validity_days' => 30,
                'status' => 'active',
            ],
            [
                'category_id' => $foodCategory->id,
                'name' => 'RM20 Starbucks Voucher',
                'description' => 'Treat yourself to a coffee or snack at Starbucks.',
                'points_required' => 250,
                'stock_quantity' => 75,
                'voucher_code_prefix' => 'STARBUCKS',
                'validity_days' => 60,
                'status' => 'active',
            ],
            [
                'category_id' => $foodCategory->id,
                'name' => 'RM25 McDonald\'s Voucher',
                'description' => 'Enjoy a meal at McDonald\'s with this voucher.',
                'points_required' => 300,
                'stock_quantity' => 100,
                'voucher_code_prefix' => 'MCD',
                'validity_days' => 45,
                'status' => 'active',
            ],
            [
                'category_id' => $entertainmentCategory->id,
                'name' => 'RM30 Cinema Voucher',
                'description' => 'Watch the latest movies at any major cinema chain.',
                'points_required' => 400,
                'stock_quantity' => 30,
                'voucher_code_prefix' => 'CINEMA',
                'validity_days' => 90,
                'status' => 'active',
            ],
            [
                'category_id' => $transportCategory->id,
                'name' => 'RM20 Grab Voucher',
                'description' => 'Use this voucher for Grab rides or food delivery.',
                'points_required' => 250,
                'stock_quantity' => 80,
                'voucher_code_prefix' => 'GRAB',
                'validity_days' => 30,
                'status' => 'active',
            ],
            [
                'category_id' => $ecommerceCategory->id,
                'name' => 'RM50 Amazon Voucher',
                'description' => 'Shop on Amazon with this generous voucher.',
                'points_required' => 600,
                'stock_quantity' => 20,
                'voucher_code_prefix' => 'AMAZON',
                'validity_days' => 120,
                'status' => 'active',
            ],
            [
                'category_id' => $foodCategory->id,
                'name' => 'RM40 KFC Voucher',
                'description' => 'Enjoy a family meal at KFC.',
                'points_required' => 450,
                'stock_quantity' => 60,
                'voucher_code_prefix' => 'KFC',
                'validity_days' => 45,
                'status' => 'active',
            ],
        ];

        foreach ($rewards as $reward) {
            Reward::create($reward);
        }
    }
}
