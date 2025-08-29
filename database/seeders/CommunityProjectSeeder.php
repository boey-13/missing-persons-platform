<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\CommunityProject;
use App\Models\ProjectApplication;
use App\Models\User;
use Carbon\Carbon;

class CommunityProjectSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create sample community projects
        $projects = [
            [
                'title' => 'Missing Person Search - Kuala Lumpur Central',
                'description' => 'Organize a coordinated search effort in KL Central area for a missing elderly person. We need volunteers to help distribute flyers, conduct door-to-door inquiries, and assist in search operations.',
                'location' => 'Kuala Lumpur Central, KLCC Area',
                'date' => Carbon::now()->addDays(7)->format('Y-m-d'),
                'time' => '08:00:00',
                'duration' => '6 hours',
                'volunteers_needed' => 15,
                'volunteers_joined' => 8,
                'points_reward' => 100,
                'category' => 'search',
                'status' => 'active',
                'photo_paths' => null, // Use default image
            ],
            [
                'title' => 'Missing Person Awareness Campaign - Petaling Jaya',
                'description' => 'Conduct an awareness campaign in Petaling Jaya to educate the public about missing person prevention and reporting procedures. Volunteers will distribute educational materials and engage with the community.',
                'location' => 'Petaling Jaya, SS15 Mall Area',
                'date' => Carbon::now()->addDays(14)->format('Y-m-d'),
                'time' => '10:00:00',
                'duration' => '4 hours',
                'volunteers_needed' => 10,
                'volunteers_joined' => 5,
                'points_reward' => 80,
                'category' => 'awareness',
                'status' => 'upcoming',
                'photo_paths' => null, // Use default image
            ],
            [
                'title' => 'Volunteer Training Workshop - Safety Protocols',
                'description' => 'Comprehensive training session for new volunteers covering safety protocols, search techniques, and emergency procedures. This workshop is essential for all volunteers participating in search operations.',
                'location' => 'Community Center, Subang Jaya',
                'date' => Carbon::now()->addDays(3)->format('Y-m-d'),
                'time' => '14:00:00',
                'duration' => '3 hours',
                'volunteers_needed' => 25,
                'volunteers_joined' => 20,
                'points_reward' => 50,
                'category' => 'training',
                'status' => 'active',
                'photo_paths' => ['projects/training-safety-1.jpg', 'projects/training-safety-2.jpg'],
            ],
            [
                'title' => 'Missing Person Search - Shah Alam',
                'description' => 'Search operation in Shah Alam for a missing teenager. Volunteers will be organized into teams to cover different areas systematically. Previous search experience preferred but not required.',
                'location' => 'Shah Alam, Section 7 & 8',
                'date' => Carbon::now()->addDays(10)->format('Y-m-d'),
                'time' => '07:00:00',
                'duration' => '8 hours',
                'volunteers_needed' => 20,
                'volunteers_joined' => 12,
                'points_reward' => 120,
                'category' => 'search',
                'status' => 'active',
                'photo_paths' => null, // Use default image
            ],
            [
                'title' => 'Community Safety Awareness - Cyber Safety',
                'description' => 'Educational session focusing on cyber safety and how it relates to missing person prevention. Topics include cyber safety, online privacy, and recognizing potential threats.',
                'location' => 'Digital Hub, Cyberjaya',
                'date' => Carbon::now()->addDays(21)->format('Y-m-d'),
                'time' => '15:00:00',
                'duration' => '2 hours',
                'volunteers_needed' => 8,
                'volunteers_joined' => 3,
                'points_reward' => 60,
                'category' => 'awareness',
                'status' => 'upcoming',
                'photo_paths' => [], // Empty array - will use default image
            ],
            [
                'title' => 'First Aid Training for Volunteers',
                'description' => 'Essential first aid training specifically designed for search and rescue volunteers. Learn emergency response, basic medical care, and how to handle various emergency situations.',
                'location' => 'Red Crescent Society, Kuala Lumpur',
                'date' => Carbon::now()->addDays(5)->format('Y-m-d'),
                'time' => '09:00:00',
                'duration' => '5 hours',
                'volunteers_needed' => 30,
                'volunteers_joined' => 25,
                'points_reward' => 75,
                'category' => 'training',
                'status' => 'active',
                'photo_paths' => ['projects/first-aid-1.jpg', 'projects/first-aid-2.jpg'],
            ],
            [
                'title' => 'Missing Person Search - Klang Valley',
                'description' => 'Large-scale search operation covering multiple areas in Klang Valley. This is a coordinated effort with multiple agencies. Volunteers will be assigned to specific zones.',
                'location' => 'Klang Valley, Multiple Locations',
                'date' => Carbon::now()->addDays(17)->format('Y-m-d'),
                'time' => '06:00:00',
                'duration' => '10 hours',
                'volunteers_needed' => 50,
                'volunteers_joined' => 35,
                'points_reward' => 150,
                'category' => 'search',
                'status' => 'upcoming',
                'photo_paths' => null, // Use default image
            ],
            [
                'title' => 'Volunteer Leadership Training',
                'description' => 'Advanced training for experienced volunteers who want to take on leadership roles in search operations. Learn team management, coordination skills, and advanced search techniques.',
                'location' => 'Leadership Institute, Putrajaya',
                'date' => Carbon::now()->addDays(28)->format('Y-m-d'),
                'time' => '13:00:00',
                'duration' => '6 hours',
                'volunteers_needed' => 15,
                'volunteers_joined' => 8,
                'points_reward' => 100,
                'category' => 'training',
                'status' => 'upcoming',
                'photo_paths' => [], // Empty array - will use default image
            ],
        ];

        foreach ($projects as $projectData) {
            $project = CommunityProject::create($projectData);
            
            // Create some sample applications for each project
            $this->createSampleApplications($project);
        }

        $this->command->info('Community projects seeded successfully!');
    }

    /**
     * Create sample applications for a project
     */
    private function createSampleApplications(CommunityProject $project): void
    {
        // Get some users to create applications (if any exist)
        $users = User::take(5)->get();
        
        if ($users->isEmpty()) {
            return; // No users to create applications for
        }

        $applicationTemplates = [
            [
                'experience' => 'I have participated in several community search operations and have basic first aid training. I am physically fit and can work long hours in various weather conditions.',
                'motivation' => 'I want to help families find their missing loved ones. Every person deserves to be found and every family deserves closure.',
                'status' => 'approved',
            ],
            [
                'experience' => 'I am a former scout leader with experience in organizing outdoor activities and coordinating groups. I have good communication skills and can work well in teams.',
                'motivation' => 'I believe in giving back to the community and helping those in need. This is a meaningful way to make a difference.',
                'status' => 'pending',
            ],
            [
                'experience' => 'I have no previous experience but I am eager to learn and help. I am physically active and can follow instructions well.',
                'motivation' => 'I want to contribute to the community and learn valuable skills that can help others in emergency situations.',
                'status' => 'pending',
            ],
        ];

        foreach ($users as $index => $user) {
            if ($index >= count($applicationTemplates)) {
                break;
            }

            $template = $applicationTemplates[$index];
            
            $application = ProjectApplication::create([
                'user_id' => $user->id,
                'community_project_id' => $project->id,
                'experience' => $template['experience'],
                'motivation' => $template['motivation'],
                'status' => $template['status'],
                'approved_at' => $template['status'] === 'approved' ? now() : null,
            ]);

            // Update project volunteers count for approved applications
            if ($template['status'] === 'approved') {
                $project->increment('volunteers_joined');
            }
        }
    }
}
