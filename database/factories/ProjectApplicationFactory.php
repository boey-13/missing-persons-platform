<?php

namespace Database\Factories;

use App\Models\ProjectApplication;
use App\Models\User;
use App\Models\CommunityProject;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProjectApplication>
 */
class ProjectApplicationFactory extends Factory
{
    protected $model = ProjectApplication::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'community_project_id' => CommunityProject::factory(),
            'experience' => $this->faker->paragraphs(2, true),
            'motivation' => $this->faker->paragraph(),
            'status' => 'pending',
            'rejection_reason' => null,
            'approved_at' => null,
            'rejected_at' => null,
        ];
    }
}
