<?php

namespace Database\Factories;

use App\Models\SightingReport;
use App\Models\User;
use App\Models\MissingReport;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SightingReport>
 */
class SightingReportFactory extends Factory
{
    protected $model = SightingReport::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'missing_report_id' => MissingReport::factory(),
            'location' => $this->faker->address(),
            'description' => $this->faker->paragraph(),
            'sighted_at' => $this->faker->dateTimeBetween('-30 days', 'now'),
            'photo_paths' => null,
            'reporter_name' => $this->faker->name(),
            'reporter_phone' => $this->faker->numerify('01########'),
            'reporter_email' => $this->faker->email(),
            'status' => 'Pending',
        ];
    }
}
