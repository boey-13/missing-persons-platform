<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\VolunteerApplication;

class TestVolunteerApplication extends Command
{
    protected $signature = 'test:volunteer-application';
    protected $description = 'Test volunteer application functionality';

    public function handle()
    {
        $this->info('🧪 Testing Volunteer Application Functionality...');

        // Create test user
        $user = User::create([
            'name' => 'Volunteer Test User',
            'email' => 'volunteer' . time() . '@example.com',
            'password' => bcrypt('password'),
            'role' => 'user'
        ]);

        $this->info("✅ Created test user: {$user->name}");

        // Test volunteer application creation
        $this->info("\n📝 Testing volunteer application creation...");

        $application = VolunteerApplication::create([
            'user_id' => $user->id,
            'motivation' => 'I want to help find missing persons in my community.',
            'skills' => ['沟通', '海报分发'],
            'languages' => ['English', '中文'],
            'availability' => ['Mon', 'Wed', 'Fri'],
            'preferred_roles' => ['海报分发', '社区活动'],
            'areas' => 'KL, PJ',
            'transport_mode' => '有车',
            'emergency_contact_name' => 'John Doe',
            'emergency_contact_phone' => '1234567890',
            'prior_experience' => 'I have experience in community outreach.',
            'supporting_documents' => [],
            'status' => 'Pending'
        ]);

        $this->info("✅ Created volunteer application for user");

        // Test application status
        $this->info("\n📊 Application Details:");
        $this->info("   Status: {$application->status}");
        $this->info("   Motivation: " . substr($application->motivation, 0, 50) . "...");
        $this->info("   Skills: " . implode(', ', $application->skills));
        $this->info("   Languages: " . implode(', ', $application->languages));

        // Test duplicate application prevention
        $this->info("\n🔄 Testing duplicate application prevention...");
        
        try {
            $duplicateApplication = VolunteerApplication::create([
                'user_id' => $user->id,
                'motivation' => 'Another application',
                'emergency_contact_name' => 'Jane Doe',
                'emergency_contact_phone' => '0987654321',
                'status' => 'Pending'
            ]);
            $this->line("   ❌ Duplicate application was created (should be prevented)");
        } catch (\Exception $e) {
            $this->line("   ✅ Duplicate application correctly prevented");
        }

        // Clean up
        $user->delete();

        $this->info("\n🎉 Volunteer application test completed!");
    }
}
