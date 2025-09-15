<?php

namespace Database\Factories;

use App\Models\RewardCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\RewardCategory>
 */
class RewardCategoryFactory extends Factory
{
    protected $model = RewardCategory::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->randomElement(['Food & Beverage', 'Shopping', 'Entertainment', 'Services', 'Gift Cards']),
            'description' => $this->faker->sentence(),
            'icon' => $this->faker->randomElement(['🍕', '🛍️', '🎬', '🔧', '🎁']),
        ];
    }
}
