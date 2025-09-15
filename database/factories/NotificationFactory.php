<?php

namespace Database\Factories;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Notification>
 */
class NotificationFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Notification::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'type' => $this->faker->randomElement([
                'welcome',
                'points_earned',
                'missing_report_submitted',
                'missing_report_approved',
                'missing_report_rejected',
                'sighting_report_submitted',
                'volunteer_application_approved',
                'volunteer_application_rejected',
                'project_application_approved',
                'project_application_rejected',
                'social_share_bonus',
                'new_missing_report',
                'new_sighting_report',
                'new_contact_message'
            ]),
            'title' => $this->faker->sentence(3),
            'message' => $this->faker->paragraph(2),
            'data' => [
                'action' => $this->faker->randomElement(['view_report', 'view_sighting', 'view_points']),
                'id' => $this->faker->numberBetween(1, 100)
            ],
            'read_at' => $this->faker->optional(0.3)->dateTimeBetween('-1 month', 'now'),
        ];
    }
}