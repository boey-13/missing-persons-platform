<?php

namespace Database\Factories;

use App\Models\VolunteerApplication;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\VolunteerApplication>
 */
class VolunteerApplicationFactory extends Factory
{
    protected $model = VolunteerApplication::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'motivation' => $this->faker->paragraph(),
            'skills' => $this->faker->randomElements(['Communication', 'Leadership', 'First Aid', 'Search Operations', 'Awareness Campaigns'], 3),
            'languages' => $this->faker->randomElements(['English', 'Malay', 'Chinese', 'Tamil'], 2),
            'availability' => $this->faker->randomElements(['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'], 2),
            'preferred_roles' => $this->faker->randomElements(['Search Operations', 'Awareness Campaigns', 'Administration'], 2),
            'areas' => $this->faker->city(),
            'transport_mode' => $this->faker->randomElement(['Public Transport', 'Private Vehicle', 'Walking']),
            'emergency_contact_name' => $this->faker->name(),
            'emergency_contact_phone' => $this->faker->numerify('01########'),
            'prior_experience' => $this->faker->paragraph(),
            'supporting_documents' => null,
            'status' => 'Pending',
            'status_reason' => null,
        ];
    }
}
