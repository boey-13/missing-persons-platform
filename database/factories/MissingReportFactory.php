<?php

namespace Database\Factories;

use App\Models\MissingReport;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\MissingReport>
 */
class MissingReportFactory extends Factory
{
    protected $model = MissingReport::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'full_name' => $this->faker->name(),
            'ic_number' => $this->faker->numerify('##########'),
            'nickname' => $this->faker->firstName(),
            'age' => $this->faker->numberBetween(18, 80),
            'gender' => $this->faker->randomElement(['Male', 'Female', 'Other']),
            'height_cm' => $this->faker->numberBetween(150, 200),
            'weight_kg' => $this->faker->numberBetween(40, 120),
            'physical_description' => $this->faker->sentence(),
            'last_seen_date' => $this->faker->date(),
            'last_seen_location' => $this->faker->address(),
            'last_seen_clothing' => $this->faker->sentence(),
            'photo_paths' => null,
            'police_report_path' => null,
            'reporter_relationship' => $this->faker->randomElement(['Parent', 'Sibling', 'Spouse', 'Relative', 'Friend', 'Employer', 'Colleague', 'Neighbor', 'Other']),
            'reporter_relationship_other' => null,
            'reporter_name' => $this->faker->name(),
            'reporter_ic_number' => $this->faker->numerify('##########'),
            'reporter_phone' => $this->faker->phoneNumber(),
            'reporter_email' => $this->faker->email(),
            'additional_notes' => $this->faker->paragraph(),
            'case_status' => 'Missing',
            'rejection_reason' => null,
        ];
    }
}
