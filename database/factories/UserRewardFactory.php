<?php

namespace Database\Factories;

use App\Models\UserReward;
use App\Models\User;
use App\Models\Reward;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\UserReward>
 */
class UserRewardFactory extends Factory
{
    protected $model = UserReward::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $redeemedAt = $this->faker->dateTimeBetween('-30 days', 'now');
        $expiresAt = $this->faker->dateTimeBetween($redeemedAt, '+30 days');
        
        return [
            'user_id' => User::factory(),
            'reward_id' => Reward::factory(),
            'voucher_code' => 'FINDME' . $this->faker->date('Ymd') . $this->faker->unique()->numerify('######') . $this->faker->lexify('??????'),
            'points_spent' => $this->faker->numberBetween(10, 200),
            'redeemed_at' => $redeemedAt,
            'expires_at' => $expiresAt,
            'status' => $this->faker->randomElement(['active', 'used', 'expired']),
            'used_at' => null,
        ];
    }
}
