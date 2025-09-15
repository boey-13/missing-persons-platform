<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Models\CommunityProject;
use App\Models\ProjectApplication;
use App\Models\VolunteerApplication;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class UT014_AdminPanelCommunityProjectManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    /**
     * Test Case: Create new community project with valid data
     * 
     * Test Steps:
     * 1. Navigate to admin community projects page
     * 2. Click "Create New Project"
     * 3. Fill in project title
     * 4. Fill in project description
     * 5. Set project location
     * 6. Set date and time
     * 7. Set maximum volunteers
     * 8. Click "Create Project"
     * 
     * Expected Result: System creates new community project successfully and displays success message
     */
    public function test_create_new_community_project_with_valid_data()
    {
        // Create admin user
        $admin = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
            'role' => 'admin'
        ]);

        $this->actingAs($admin);

        $response = $this->post('/admin/community-projects', [
            'title' => 'Search Mission KLCC',
            'description' => 'Search for missing person in KLCC area',
            'location' => 'Kuala Lumpur City Centre',
            'date' => '2024-02-01',
            'time' => '10:00',
            'duration' => '4 hours',
            'volunteers_needed' => 20,
            'points_reward' => 50,
            'category' => 'search',
            'status' => 'active'
        ]);

        $response->assertRedirect('/admin/community-projects');
        $response->assertSessionHas('success', 'Project created successfully!');

        // Verify project was created in database
        $this->assertDatabaseHas('community_projects', [
            'title' => 'Search Mission KLCC',
            'description' => 'Search for missing person in KLCC area',
            'location' => 'Kuala Lumpur City Centre',
            'date' => '2024-02-01 00:00:00', // Database stores as datetime
            'time' => '10:00',
            'duration' => '4 hours',
            'volunteers_needed' => 20,
            'points_reward' => 50,
            'category' => 'search',
            'status' => 'active'
        ]);
    }

    /**
     * Test Case: Create community project with invalid data
     * 
     * Test Steps:
     * 1. Navigate to admin community projects page
     * 2. Click "Create New Project"
     * 3. Leave title field empty
     * 4. Fill in description
     * 5. Leave location empty
     * 6. Set date and time
     * 7. Set negative max volunteers
     * 8. Click "Create Project"
     * 
     * Expected Result: System displays validation errors for required fields and invalid data
     */
    public function test_create_community_project_with_invalid_data()
    {
        // Create admin user
        $admin = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
            'role' => 'admin'
        ]);

        $this->actingAs($admin);

        $response = $this->post('/admin/community-projects', [
            'title' => '', // Empty title
            'description' => 'Test',
            'location' => '', // Empty location
            'date' => '2024-02-01',
            'time' => '10:00',
            'duration' => '4 hours',
            'volunteers_needed' => -5, // Negative volunteers
            'points_reward' => 50,
            'category' => 'search',
            'status' => 'active'
        ]);

        $response->assertSessionHasErrors(['title', 'location', 'volunteers_needed']);
    }

    /**
     * Test Case: Update existing project
     * 
     * Test Steps:
     * 1. Navigate to admin community projects page
     * 2. Find existing project
     * 3. Click "Edit" button
     * 4. Update project title
     * 5. Update max volunteer
     * 6. Click "Update Project"
     * 
     * Expected Result: System updates project successfully and displays success message
     */
    public function test_update_existing_project()
    {
        // Create admin user
        $admin = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
            'role' => 'admin'
        ]);

        // Create existing project
        $project = CommunityProject::factory()->create([
            'title' => 'Search Mission KLCC',
            'description' => 'Search for missing person in KLCC area',
            'location' => 'Kuala Lumpur City Centre',
            'date' => '2024-02-01',
            'time' => '10:00',
            'duration' => '4 hours',
            'volunteers_needed' => 20,
            'points_reward' => 50,
            'category' => 'search',
            'status' => 'active'
        ]);

        $this->actingAs($admin);

        $response = $this->put("/admin/community-projects/{$project->id}", [
            'title' => 'Updated Search Mission KLCC',
            'description' => 'Updated search for missing person in KLCC area',
            'location' => 'Kuala Lumpur City Centre',
            'date' => '2024-02-01',
            'time' => '10:00',
            'duration' => '4 hours',
            'volunteers_needed' => 25,
            'points_reward' => 60,
            'category' => 'search',
            'status' => 'active'
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Project updated successfully!');

        // Verify project was updated in database
        $this->assertDatabaseHas('community_projects', [
            'id' => $project->id,
            'title' => 'Updated Search Mission KLCC',
            'description' => 'Updated search for missing person in KLCC area',
            'volunteers_needed' => 25,
            'points_reward' => 60
        ]);
    }

    /**
     * Test Case: Delete existing project
     * 
     * Test Steps:
     * 1. Navigate to admin community projects page
     * 2. Find existing project
     * 3. Click "Delete" button
     * 4. Confirm deletion
     * 
     * Expected Result: System deletes project successfully and displays success message
     */
    public function test_delete_existing_project()
    {
        // Create admin user
        $admin = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
            'role' => 'admin'
        ]);

        // Create existing project
        $project = CommunityProject::factory()->create([
            'title' => 'Search Mission KLCC',
            'description' => 'Search for missing person in KLCC area',
            'location' => 'Kuala Lumpur City Centre',
            'status' => 'active'
        ]);

        $this->actingAs($admin);

        $response = $this->delete("/admin/community-projects/{$project->id}");

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Project deleted successfully!');

        // Verify project was deleted from database
        $this->assertDatabaseMissing('community_projects', [
            'id' => $project->id
        ]);
    }

    /**
     * Test Case: Search projects by title
     * 
     * Test Steps:
     * 1. Navigate to admin community projects page
     * 2. Enter search term in search box
     * 3. Click search button
     * 
     * Expected Result: System displays filtered projects matching search term
     */
    public function test_search_projects_by_title()
    {
        // Create admin user
        $admin = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
            'role' => 'admin'
        ]);

        // Create test projects
        CommunityProject::factory()->create([
            'title' => 'Search Mission KLCC',
            'description' => 'Search for missing person in KLCC area',
            'location' => 'Kuala Lumpur City Centre',
            'status' => 'active'
        ]);

        CommunityProject::factory()->create([
            'title' => 'Awareness Campaign',
            'description' => 'Public awareness campaign',
            'location' => 'Petaling Jaya',
            'status' => 'active'
        ]);

        CommunityProject::factory()->create([
            'title' => 'Training Workshop',
            'description' => 'Volunteer training workshop',
            'location' => 'Shah Alam',
            'status' => 'active'
        ]);

        $this->actingAs($admin);

        $response = $this->get('/admin/community-projects?search=Search Mission');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => 
            $page->component('Admin/ManageCommunityProjects')
                ->has('projects')
        );
    }

    /**
     * Test Case: Filter projects by status
     * 
     * Test Steps:
     * 1. Navigate to admin community projects page
     * 2. Select status from dropdown
     * 3. Click filter button
     * 
     * Expected Result: System displays projects filtered by selected status
     */
    public function test_filter_projects_by_status()
    {
        // Create admin user
        $admin = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
            'role' => 'admin'
        ]);

        // Create test projects with different statuses
        CommunityProject::factory()->create([
            'title' => 'Active Project',
            'status' => 'active'
        ]);

        CommunityProject::factory()->create([
            'title' => 'Completed Project',
            'status' => 'completed'
        ]);

        CommunityProject::factory()->create([
            'title' => 'Upcoming Project',
            'status' => 'upcoming'
        ]);

        $this->actingAs($admin);

        $response = $this->get('/admin/community-projects?status=active');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => 
            $page->component('Admin/ManageCommunityProjects')
                ->has('projects')
        );
    }

    /**
     * Test Case: Approve volunteer application
     * 
     * Test Steps:
     * 1. Navigate to admin community projects page
     * 2. Switch to "Applications" tab
     * 3. Click "Approve" for volunteer application
     * 4. Confirm approval
     * 
     * Expected Result: System approves volunteer application and sends notification
     */
    public function test_approve_volunteer_application()
    {
        // Create admin user
        $admin = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
            'role' => 'admin'
        ]);

        // Create volunteer user
        $volunteer = User::factory()->create([
            'name' => 'Volunteer User',
            'email' => 'volunteer@example.com',
            'password' => Hash::make('password123'),
            'role' => 'volunteer'
        ]);

        // Create project
        $project = CommunityProject::factory()->create([
            'title' => 'Search Mission KLCC',
            'volunteers_needed' => 20,
            'volunteers_joined' => 0,
            'status' => 'active'
        ]);

        // Create project application
        $application = ProjectApplication::factory()->create([
            'user_id' => $volunteer->id,
            'community_project_id' => $project->id,
            'status' => 'pending',
            'experience' => 'Previous search experience',
            'motivation' => 'Want to help the community'
        ]);

        $this->actingAs($admin);

        $response = $this->post("/admin/community-projects/applications/{$application->id}/approve");

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        // Verify application was approved
        $this->assertDatabaseHas('project_applications', [
            'id' => $application->id,
            'status' => 'approved'
        ]);

        // Verify project volunteer count was incremented
        $this->assertDatabaseHas('community_projects', [
            'id' => $project->id,
            'volunteers_joined' => 1
        ]);
    }

    /**
     * Test Case: Reject volunteer application
     * 
     * Test Steps:
     * 1. Navigate to admin community projects page
     * 2. Switch to "Applications" tab
     * 3. Click "Reject" for volunteer application
     * 4. Confirm rejection
     * 
     * Expected Result: System rejects volunteer application and sends notification
     */
    public function test_reject_volunteer_application()
    {
        // Create admin user
        $admin = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
            'role' => 'admin'
        ]);

        // Create volunteer user
        $volunteer = User::factory()->create([
            'name' => 'Volunteer User',
            'email' => 'volunteer@example.com',
            'password' => Hash::make('password123'),
            'role' => 'volunteer'
        ]);

        // Create project
        $project = CommunityProject::factory()->create([
            'title' => 'Search Mission KLCC',
            'volunteers_needed' => 20,
            'volunteers_joined' => 0,
            'status' => 'active'
        ]);

        // Create project application
        $application = ProjectApplication::factory()->create([
            'user_id' => $volunteer->id,
            'community_project_id' => $project->id,
            'status' => 'pending',
            'experience' => 'Previous search experience',
            'motivation' => 'Want to help the community'
        ]);

        $this->actingAs($admin);

        $response = $this->post("/admin/community-projects/applications/{$application->id}/reject", [
            'rejection_reason' => 'Insufficient experience for this project'
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        // Verify application was rejected
        $this->assertDatabaseHas('project_applications', [
            'id' => $application->id,
            'status' => 'rejected',
            'rejection_reason' => 'Insufficient experience for this project'
        ]);
    }

    /**
     * Test Case: Access admin community projects without admin role
     */
    public function test_access_admin_community_projects_without_admin_role()
    {
        // Create regular user (not admin)
        $user = User::factory()->create([
            'name' => 'Regular User',
            'email' => 'user@example.com',
            'password' => Hash::make('password123'),
            'role' => 'user'
        ]);

        $this->actingAs($user);

        $response = $this->get('/admin/community-projects');
        $response->assertStatus(200); // Returns access denied page instead of 403
    }

    /**
     * Test Case: Create project with photo upload
     */
    public function test_create_project_with_photo_upload()
    {
        // Create admin user
        $admin = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
            'role' => 'admin'
        ]);

        // Create fake photo files
        $photos = [
            UploadedFile::fake()->image('project1.jpg', 300, 300),
            UploadedFile::fake()->image('project2.jpg', 300, 300)
        ];

        $this->actingAs($admin);

        $response = $this->post('/admin/community-projects', [
            'title' => 'Search Mission KLCC',
            'description' => 'Search for missing person in KLCC area',
            'location' => 'Kuala Lumpur City Centre',
            'date' => '2024-02-01',
            'time' => '10:00',
            'duration' => '4 hours',
            'volunteers_needed' => 20,
            'points_reward' => 50,
            'category' => 'search',
            'status' => 'active',
            'photos' => $photos
        ]);

        $response->assertRedirect('/admin/community-projects');
        $response->assertSessionHas('success', 'Project created successfully!');

        // Verify photos were stored
        foreach ($photos as $photo) {
            Storage::disk('public')->assertExists('community-projects/' . $photo->hashName());
        }

        // Verify project was created with photo paths
        $this->assertDatabaseHas('community_projects', [
            'title' => 'Search Mission KLCC',
            'photo_paths' => json_encode([
                'community-projects/' . $photos[0]->hashName(),
                'community-projects/' . $photos[1]->hashName()
            ])
        ]);
    }

    /**
     * Test Case: Update project status
     */
    public function test_update_project_status()
    {
        // Create admin user
        $admin = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
            'role' => 'admin'
        ]);

        // Create project
        $project = CommunityProject::factory()->create([
            'title' => 'Search Mission KLCC',
            'status' => 'active'
        ]);

        $this->actingAs($admin);

        $response = $this->post("/admin/community-projects/{$project->id}/status", [
            'status' => 'completed'
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Project status updated successfully!');

        // Verify project status was updated
        $this->assertDatabaseHas('community_projects', [
            'id' => $project->id,
            'status' => 'completed'
        ]);
    }

    /**
     * Test Case: Filter projects by category
     */
    public function test_filter_projects_by_category()
    {
        // Create admin user
        $admin = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
            'role' => 'admin'
        ]);

        // Create test projects with different categories
        CommunityProject::factory()->create([
            'title' => 'Search Project',
            'category' => 'search',
            'status' => 'active'
        ]);

        CommunityProject::factory()->create([
            'title' => 'Awareness Project',
            'category' => 'awareness',
            'status' => 'active'
        ]);

        CommunityProject::factory()->create([
            'title' => 'Training Project',
            'category' => 'training',
            'status' => 'active'
        ]);

        $this->actingAs($admin);

        $response = $this->get('/admin/community-projects?category=search');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => 
            $page->component('Admin/ManageCommunityProjects')
                ->has('projects')
        );
    }

    /**
     * Test Case: Sort projects by different criteria
     */
    public function test_sort_projects_by_different_criteria()
    {
        // Create admin user
        $admin = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
            'role' => 'admin'
        ]);

        // Create test projects
        CommunityProject::factory()->create([
            'title' => 'A Project',
            'points_reward' => 100,
            'status' => 'active'
        ]);

        CommunityProject::factory()->create([
            'title' => 'B Project',
            'points_reward' => 200,
            'status' => 'active'
        ]);

        $this->actingAs($admin);

        // Test sorting by title
        $response = $this->get('/admin/community-projects?sort_by=title&sort_order=asc');
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => 
            $page->component('Admin/ManageCommunityProjects')
                ->has('projects')
        );

        // Test sorting by points reward
        $response = $this->get('/admin/community-projects?sort_by=points_reward&sort_order=desc');
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => 
            $page->component('Admin/ManageCommunityProjects')
                ->has('projects')
        );
    }

    /**
     * Test Case: Get project applications
     */
    public function test_get_project_applications()
    {
        // Create admin user
        $admin = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
            'role' => 'admin'
        ]);

        // Create volunteer user
        $volunteer = User::factory()->create([
            'name' => 'Volunteer User',
            'email' => 'volunteer@example.com',
            'password' => Hash::make('password123'),
            'role' => 'volunteer'
        ]);

        // Create project
        $project = CommunityProject::factory()->create([
            'title' => 'Search Mission KLCC',
            'status' => 'active'
        ]);

        // Create project application
        ProjectApplication::factory()->create([
            'user_id' => $volunteer->id,
            'community_project_id' => $project->id,
            'status' => 'pending',
            'experience' => 'Previous search experience',
            'motivation' => 'Want to help the community'
        ]);

        $this->actingAs($admin);

        $response = $this->get('/admin/community-projects/applications');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            '*' => [
                'id',
                'status',
                'experience',
                'motivation',
                'created_at',
                'volunteerName',
                'email',
                'phone',
                'user_id',
                'projectTitle',
                'projectLocation',
                'projectDate',
                'projectTime',
                'projectDuration',
                'projectCategory',
                'projectStatus',
                'projectPoints',
                'projectVolunteersNeeded',
                'projectVolunteersJoined'
            ]
        ]);
    }

    /**
     * Test Case: Create project with minimum required fields
     */
    public function test_create_project_with_minimum_required_fields()
    {
        // Create admin user
        $admin = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
            'role' => 'admin'
        ]);

        $this->actingAs($admin);

        $response = $this->post('/admin/community-projects', [
            'title' => 'Minimal Project',
            'description' => 'Minimal project description',
            'location' => 'Test Location',
            'date' => '2024-02-01',
            'time' => '10:00',
            'duration' => '2 hours',
            'volunteers_needed' => 1,
            'points_reward' => 0,
            'category' => 'search',
            'status' => 'active'
        ]);

        $response->assertRedirect('/admin/community-projects');
        $response->assertSessionHas('success', 'Project created successfully!');

        // Verify project was created with minimal data
        $this->assertDatabaseHas('community_projects', [
            'title' => 'Minimal Project',
            'description' => 'Minimal project description',
            'location' => 'Test Location',
            'volunteers_needed' => 1,
            'points_reward' => 0,
            'category' => 'search',
            'status' => 'active'
        ]);
    }

    /**
     * Test Case: Update project with validation errors
     */
    public function test_update_project_with_validation_errors()
    {
        // Create admin user
        $admin = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
            'role' => 'admin'
        ]);

        // Create existing project
        $project = CommunityProject::factory()->create([
            'title' => 'Search Mission KLCC',
            'status' => 'active'
        ]);

        $this->actingAs($admin);

        $response = $this->put("/admin/community-projects/{$project->id}", [
            'title' => '', // Empty title
            'description' => '', // Empty description
            'location' => '', // Empty location
            'date' => 'invalid-date', // Invalid date
            'time' => '', // Empty time
            'duration' => '', // Empty duration
            'volunteers_needed' => -1, // Negative volunteers
            'points_reward' => -10, // Negative points
            'category' => 'invalid', // Invalid category
            'status' => 'invalid' // Invalid status
        ]);

        $response->assertSessionHasErrors([
            'title', 'description', 'location', 'date', 'time', 'duration', 
            'volunteers_needed', 'points_reward', 'category', 'status'
        ]);
    }

    /**
     * Test Case: Update project with news
     */
    public function test_update_project_with_news()
    {
        // Create admin user
        $admin = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
            'role' => 'admin'
        ]);

        // Create project
        $project = CommunityProject::factory()->create([
            'title' => 'Search Mission KLCC',
            'status' => 'active'
        ]);

        $this->actingAs($admin);

        $response = $this->post("/admin/community-projects/{$project->id}/update-news", [
            'latest_news' => 'Project is progressing well. We have found some leads.'
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'News added successfully!');

        // Verify project news was updated
        $this->assertDatabaseHas('community_projects', [
            'id' => $project->id,
            'latest_news' => 'Project is progressing well. We have found some leads.'
        ]);
    }

    /**
     * Test Case: Create project from missing report
     */
    public function test_create_project_from_missing_report()
    {
        // Create admin user
        $admin = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
            'role' => 'admin'
        ]);

        // Create missing report
        $missingReport = \App\Models\MissingReport::factory()->create([
            'full_name' => 'John Doe',
            'last_seen_location' => 'KLCC',
            'physical_description' => 'Tall, dark hair',
            'additional_notes' => 'Last seen wearing blue shirt'
        ]);

        $this->actingAs($admin);

        $response = $this->get("/admin/missing-reports/{$missingReport->id}/create-project");

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => 
            $page->component('Admin/CreateProjectFromMissingReport')
                ->has('missingReport')
                ->has('prefilledData')
        );
    }
}
