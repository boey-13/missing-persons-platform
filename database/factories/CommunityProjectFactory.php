<?php

namespace Database\Factories;

use App\Models\CommunityProject;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CommunityProject>
 */
class CommunityProjectFactory extends Factory
{
    protected $model = CommunityProject::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => $this->faker->sentence(4),
            'description' => $this->faker->paragraphs(3, true),
            'location' => $this->faker->address(),
            'date' => $this->faker->dateTimeBetween('+1 week', '+2 months'),
            'time' => $this->faker->time('H:i'),
            'duration' => $this->faker->randomElement(['2 hours', '4 hours', '6 hours', '1 day']),
            'volunteers_needed' => $this->faker->numberBetween(5, 20),
            'volunteers_joined' => 0,
            'points_reward' => $this->faker->numberBetween(20, 100),
            'category' => $this->faker->randomElement(['search', 'awareness', 'training']),
            'status' => 'active',
            'photo_paths' => null,
            'latest_news' => null,
            'news_files' => null,
        ];
    }
}
