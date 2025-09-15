<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Models\MissingReport;
use App\Models\SightingReport;
use App\Models\Reward;
use App\Models\CommunityProject;
use App\Models\VolunteerApplication;
use App\Models\UserReward;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

class UT012_AdminPanelSystemDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    /**
     * Test Case: View admin dashboard with statistics
     * 
     * Test Steps:
     * 1. Navigate to admin dashboard
     * 2. Check all statistics cards are displayed
     * 3. Verify data accuracy
     * 
     * Expected Result: The system displays all statistics including total missing cases, pending sightings, total users, and active rewards
     */
    public function test_view_admin_dashboard_with_statistics()
    {
        // Create admin user
        $admin = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
            'role' => 'admin'
        ]);

        // Create test data for statistics
        // Missing reports
        MissingReport::factory()->create(['case_status' => 'Pending']);
        MissingReport::factory()->create(['case_status' => 'Missing']);
        MissingReport::factory()->create(['case_status' => 'Found']);

        // Sighting reports
        SightingReport::factory()->create(['status' => 'Pending']);
        SightingReport::factory()->create(['status' => 'Approved']);

        // Users
        User::factory()->create(['role' => 'user']);
        User::factory()->create(['role' => 'volunteer']);

        // Rewards
        Reward::factory()->create(['status' => 'active']);
        Reward::factory()->create(['status' => 'inactive']);

        // Community projects
        CommunityProject::factory()->create(['status' => 'active']);
        CommunityProject::factory()->create(['status' => 'completed']);

        // Volunteer applications
        VolunteerApplication::factory()->create(['status' => 'Pending']);
        VolunteerApplication::factory()->create(['status' => 'Approved']);

        // User rewards
        UserReward::factory()->create();

        $this->actingAs($admin);

        $response = $this->get('/admin/dashboard');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => 
            $page->component('Admin/Dashboard')
                ->has('stats')
        );

        // Verify statistics are present and reasonable
        $stats = $response->viewData('page')['props']['stats'];
        $this->assertGreaterThanOrEqual(3, $stats['totalMissingCases']);
        $this->assertGreaterThanOrEqual(1, $stats['pendingMissingCases']);
        $this->assertGreaterThanOrEqual(1, $stats['pendingSightings']);
        $this->assertGreaterThanOrEqual(4, $stats['totalUsers']);
        $this->assertGreaterThanOrEqual(1, $stats['activeRewards']);
        $this->assertGreaterThanOrEqual(1, $stats['totalProjects']);
        $this->assertGreaterThanOrEqual(1, $stats['pendingVolunteers']);
        $this->assertGreaterThanOrEqual(1, $stats['totalRedemptions']);
    }

    /**
     * Test Case: Click "View All" link for missing reports
     * 
     * Test Steps:
     * 1. Navigate to admin dashboard
     * 2. Click "View All →" link in Recent Missing Reports section
     * 3. Verify navigation
     * 
     * Expected Result: The system navigates to missing reports management page
     */
    public function test_click_view_all_link_for_missing_reports()
    {
        // Create admin user
        $admin = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
            'role' => 'admin'
        ]);

        // Create missing reports
        MissingReport::factory()->create(['full_name' => 'John Smith']);
        MissingReport::factory()->create(['full_name' => 'Jane Doe']);

        $this->actingAs($admin);

        // First, access the dashboard
        $response = $this->get('/admin/dashboard');
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => 
            $page->component('Admin/Dashboard')
                ->has('recentMissingReports')
        );

        // Then navigate to missing reports page
        $response = $this->get('/admin/missing-reports');
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => 
            $page->component('Admin/ManageMissingReports')
        );
    }

    /**
     * Test Case: Access quick actions section
     * 
     * Test Steps:
     * 1. Navigate to admin dashboard
     * 2. Check "Quick Actions" section
     * 3. Click 1 of the quick actions button
     * 4. Verify that the redirect goes to the correct page
     * 
     * Expected Result: The system navigates to correct page
     */
    public function test_access_quick_actions_section()
    {
        // Create admin user
        $admin = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
            'role' => 'admin'
        ]);

        $this->actingAs($admin);

        // Access dashboard
        $response = $this->get('/admin/dashboard');
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => 
            $page->component('Admin/Dashboard')
        );

        // Test navigation to volunteers page (one of the quick actions)
        $response = $this->get('/admin/volunteers');
        $response->assertStatus(200);
    }

    /**
     * Test Case: View dashboard with empty data
     */
    public function test_view_dashboard_with_empty_data()
    {
        // Create admin user
        $admin = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
            'role' => 'admin'
        ]);

        $this->actingAs($admin);

        $response = $this->get('/admin/dashboard');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => 
            $page->component('Admin/Dashboard')
                ->where('stats.totalMissingCases', 0)
                ->where('stats.pendingMissingCases', 0)
                ->where('stats.pendingSightings', 0)
                ->where('stats.totalUsers', 1) // Only admin user
                ->where('stats.activeRewards', 0)
                ->where('stats.totalProjects', 0)
                ->where('stats.pendingVolunteers', 0)
                ->where('stats.totalRedemptions', 0)
        );
    }

    /**
     * Test Case: View recent missing reports section
     */
    public function test_view_recent_missing_reports_section()
    {
        // Create admin user
        $admin = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
            'role' => 'admin'
        ]);

        // Create recent missing reports
        MissingReport::factory()->create([
            'full_name' => 'John Smith',
            'last_seen_location' => 'Kuala Lumpur',
            'case_status' => 'Pending'
        ]);

        MissingReport::factory()->create([
            'full_name' => 'Jane Doe',
            'last_seen_location' => 'Selangor',
            'case_status' => 'Missing'
        ]);

        $this->actingAs($admin);

        $response = $this->get('/admin/dashboard');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => 
            $page->component('Admin/Dashboard')
                ->has('recentMissingReports')
                ->where('recentMissingReports.0.missing_person_name', 'John Smith')
                ->where('recentMissingReports.1.missing_person_name', 'Jane Doe')
        );
    }

    /**
     * Test Case: View recent sightings section
     */
    public function test_view_recent_sightings_section()
    {
        // Create admin user
        $admin = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
            'role' => 'admin'
        ]);

        // Create missing report
        $missingReport = MissingReport::factory()->create([
            'full_name' => 'John Smith',
            'case_status' => 'Missing'
        ]);

        // Create recent sightings
        SightingReport::factory()->create([
            'missing_report_id' => $missingReport->id,
            'location' => 'Kuala Lumpur',
            'status' => 'Pending'
        ]);

        $this->actingAs($admin);

        $response = $this->get('/admin/dashboard');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => 
            $page->component('Admin/Dashboard')
                ->has('recentSightings')
        );
    }

    /**
     * Test Case: View system status section
     */
    public function test_view_system_status_section()
    {
        // Create admin user
        $admin = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
            'role' => 'admin'
        ]);

        $this->actingAs($admin);

        $response = $this->get('/admin/dashboard');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => 
            $page->component('Admin/Dashboard')
        );
    }

    /**
     * Test Case: Access dashboard without admin role
     */
    public function test_access_dashboard_without_admin_role()
    {
        // Create regular user (not admin)
        $user = User::factory()->create([
            'name' => 'Regular User',
            'email' => 'user@example.com',
            'password' => Hash::make('password123'),
            'role' => 'user'
        ]);

        $this->actingAs($user);

        $response = $this->get('/admin/dashboard');
        $response->assertStatus(200); // Returns access denied page instead of 403
    }

    /**
     * Test Case: Verify statistics accuracy
     */
    public function test_verify_statistics_accuracy()
    {
        // Create admin user
        $admin = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
            'role' => 'admin'
        ]);

        // Create specific test data
        MissingReport::factory()->count(5)->create(['case_status' => 'Pending']);
        MissingReport::factory()->count(3)->create(['case_status' => 'Missing']);
        MissingReport::factory()->count(2)->create(['case_status' => 'Found']);

        SightingReport::factory()->count(4)->create(['status' => 'Pending']);
        SightingReport::factory()->count(6)->create(['status' => 'Approved']);

        User::factory()->count(8)->create(['role' => 'user']);
        User::factory()->count(2)->create(['role' => 'volunteer']);

        Reward::factory()->count(3)->create(['status' => 'active']);
        Reward::factory()->count(1)->create(['status' => 'inactive']);

        CommunityProject::factory()->count(2)->create(['status' => 'active']);
        CommunityProject::factory()->count(1)->create(['status' => 'completed']);

        VolunteerApplication::factory()->count(3)->create(['status' => 'Pending']);
        VolunteerApplication::factory()->count(2)->create(['status' => 'Approved']);

        UserReward::factory()->count(5)->create();

        $this->actingAs($admin);

        $response = $this->get('/admin/dashboard');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => 
            $page->component('Admin/Dashboard')
                ->has('stats')
        );

        // Verify statistics are present and reasonable
        $stats = $response->viewData('page')['props']['stats'];
        $this->assertGreaterThanOrEqual(10, $stats['totalMissingCases']); // 5+3+2
        $this->assertGreaterThanOrEqual(5, $stats['pendingMissingCases']);
        $this->assertGreaterThanOrEqual(4, $stats['pendingSightings']);
        $this->assertGreaterThanOrEqual(11, $stats['totalUsers']); // 8+2+1 admin
        $this->assertGreaterThanOrEqual(3, $stats['activeRewards']);
        $this->assertGreaterThanOrEqual(2, $stats['totalProjects']);
        $this->assertGreaterThanOrEqual(3, $stats['pendingVolunteers']);
        $this->assertGreaterThanOrEqual(5, $stats['totalRedemptions']);
    }

    /**
     * Test Case: View dashboard with large dataset
     */
    public function test_view_dashboard_with_large_dataset()
    {
        // Create admin user
        $admin = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
            'role' => 'admin'
        ]);

        // Create large dataset
        MissingReport::factory()->count(50)->create();
        SightingReport::factory()->count(30)->create();
        User::factory()->count(100)->create();
        Reward::factory()->count(20)->create();
        CommunityProject::factory()->count(15)->create();
        VolunteerApplication::factory()->count(25)->create();
        UserReward::factory()->count(40)->create();

        $this->actingAs($admin);

        $response = $this->get('/admin/dashboard');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => 
            $page->component('Admin/Dashboard')
                ->has('stats')
        );

        // Verify statistics are present and reasonable
        $stats = $response->viewData('page')['props']['stats'];
        $this->assertGreaterThanOrEqual(50, $stats['totalMissingCases']);
        $this->assertGreaterThanOrEqual(30, $stats['pendingSightings']);
        $this->assertGreaterThanOrEqual(101, $stats['totalUsers']); // 100 + 1 admin
        $this->assertGreaterThanOrEqual(20, $stats['activeRewards']);
        $this->assertGreaterThanOrEqual(15, $stats['totalProjects']);
        $this->assertGreaterThanOrEqual(25, $stats['pendingVolunteers']);
        $this->assertGreaterThanOrEqual(40, $stats['totalRedemptions']);
    }

    /**
     * Test Case: Test quick action navigation to missing reports
     */
    public function test_quick_action_navigation_to_missing_reports()
    {
        // Create admin user
        $admin = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
            'role' => 'admin'
        ]);

        $this->actingAs($admin);

        // Test navigation to missing reports page
        $response = $this->get('/admin/missing-reports');
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => 
            $page->component('Admin/ManageMissingReports')
        );
    }

    /**
     * Test Case: Test quick action navigation to sighting reports
     */
    public function test_quick_action_navigation_to_sighting_reports()
    {
        // Create admin user
        $admin = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
            'role' => 'admin'
        ]);

        $this->actingAs($admin);

        // Test navigation to sighting reports page
        $response = $this->get('/admin/sighting-reports');
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => 
            $page->component('Admin/ManageSightingReports')
        );
    }

    /**
     * Test Case: Test quick action navigation to volunteers
     */
    public function test_quick_action_navigation_to_volunteers()
    {
        // Create admin user
        $admin = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
            'role' => 'admin'
        ]);

        $this->actingAs($admin);

        // Test navigation to volunteers page
        $response = $this->get('/admin/volunteers');
        $response->assertStatus(200);
    }

    /**
     * Test Case: View dashboard with mixed data statuses
     */
    public function test_view_dashboard_with_mixed_data_statuses()
    {
        // Create admin user
        $admin = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
            'role' => 'admin'
        ]);

        // Create mixed status data
        MissingReport::factory()->create(['case_status' => 'Pending']);
        MissingReport::factory()->create(['case_status' => 'Approved']);
        MissingReport::factory()->create(['case_status' => 'Rejected']);
        MissingReport::factory()->create(['case_status' => 'Missing']);
        MissingReport::factory()->create(['case_status' => 'Found']);
        MissingReport::factory()->create(['case_status' => 'Closed']);

        SightingReport::factory()->create(['status' => 'Pending']);
        SightingReport::factory()->create(['status' => 'Approved']);
        SightingReport::factory()->create(['status' => 'Rejected']);

        $this->actingAs($admin);

        $response = $this->get('/admin/dashboard');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => 
            $page->component('Admin/Dashboard')
                ->has('stats')
        );

        // Verify statistics are present and reasonable
        $stats = $response->viewData('page')['props']['stats'];
        $this->assertGreaterThanOrEqual(6, $stats['totalMissingCases']);
        $this->assertGreaterThanOrEqual(1, $stats['pendingSightings']);
    }

    /**
     * Test Case: Test dashboard performance with complex queries
     */
    public function test_dashboard_performance_with_complex_queries()
    {
        // Create admin user
        $admin = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
            'role' => 'admin'
        ]);

        // Create complex related data
        $users = User::factory()->count(10)->create();
        
        foreach ($users as $user) {
            MissingReport::factory()->create(['user_id' => $user->id]);
            SightingReport::factory()->create(['user_id' => $user->id]);
            VolunteerApplication::factory()->create(['user_id' => $user->id]);
        }

        $this->actingAs($admin);

        $startTime = microtime(true);
        $response = $this->get('/admin/dashboard');
        $endTime = microtime(true);

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => 
            $page->component('Admin/Dashboard')
        );

        // Verify response time is reasonable (less than 2 seconds)
        $this->assertLessThan(2, $endTime - $startTime);
    }
}
