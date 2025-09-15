<?php

namespace Database\Factories;

use App\Models\Reward;
use App\Models\RewardCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Reward>
 */
class RewardFactory extends Factory
{
    protected $model = Reward::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'category_id' => RewardCategory::factory(),
            'name' => $this->faker->sentence(3),
            'description' => $this->faker->paragraph(),
            'points_required' => $this->faker->numberBetween(10, 200),
            'stock_quantity' => $this->faker->numberBetween(0, 100),
            'redeemed_count' => 0,
            'image_path' => null,
            'voucher_code_prefix' => 'FINDME',
            'validity_days' => $this->faker->numberBetween(7, 90),
            'status' => 'active',
        ];
    }
}
