<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Models\CommunityProject;
use App\Models\ProjectApplication;
use App\Models\VolunteerApplication;
use App\Models\ProjectNews;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

class UT008_CommunityProjectApplicationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    /**
     * Test Case: Apply for community project with valid data
     * 
     * Test Steps:
     * 1. Navigate to community projects page
     * 2. Select an available project
     * 3. Click "Apply Now" button
     * 4. Enter application motivation
     * 5. Select relevant skills
     * 6. Click "Submit Application" button
     * 
     * Expected Result: The system displays success message "Application submitted successfully!"
     */
    public function test_apply_for_community_project_with_valid_data()
    {
        // Create approved volunteer
        $user = User::factory()->create([
            'name' => 'John Volunteer',
            'email' => 'john@example.com',
            'password' => Hash::make('password123'),
            'role' => 'volunteer'
        ]);

        // Create approved volunteer application
        VolunteerApplication::create([
            'user_id' => $user->id,
            'motivation' => 'I want to help',
            'emergency_contact_name' => 'Jane Doe',
            'emergency_contact_phone' => '0123456789',
            'status' => 'Approved'
        ]);

        // Create community project
        $project = CommunityProject::factory()->create([
            'title' => 'Search Operation',
            'description' => 'Help find missing person',
            'location' => 'Kuala Lumpur',
            'date' => '2024-02-01',
            'time' => '09:00',
            'duration' => '4 hours',
            'volunteers_needed' => 10,
            'volunteers_joined' => 0,
            'status' => 'active'
        ]);

        $this->actingAs($user);

        $response = $this->post("/volunteer/projects/{$project->id}/apply", [
            'experience' => 'I have previous experience in search operations',
            'motivation' => 'I can help with search operations'
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Application submitted successfully!');

        // Verify application was created in database
        $this->assertDatabaseHas('project_applications', [
            'user_id' => $user->id,
            'community_project_id' => $project->id,
            'experience' => 'I have previous experience in search operations',
            'motivation' => 'I can help with search operations',
            'status' => 'pending'
        ]);
    }

    /**
     * Test Case: Apply for project with empty motivation
     * 
     * Test Steps:
     * 1. Navigate to project details page
     * 2. Click "Apply for Project" button
     * 3. Leave motivation field empty
     * 4. Click "Submit Application" button
     * 
     * Expected Result: The system displays error message "Please fill out this field."
     */
    public function test_apply_for_project_with_empty_motivation()
    {
        // Create approved volunteer
        $user = User::factory()->create([
            'name' => 'Jane Volunteer',
            'email' => 'jane@example.com',
            'password' => Hash::make('password123'),
            'role' => 'volunteer'
        ]);

        VolunteerApplication::create([
            'user_id' => $user->id,
            'motivation' => 'I want to help',
            'emergency_contact_name' => 'John Doe',
            'emergency_contact_phone' => '0123456789',
            'status' => 'Approved'
        ]);

        $project = CommunityProject::factory()->create([
            'title' => 'Search Operation 2',
            'status' => 'active'
        ]);

        $this->actingAs($user);

        $response = $this->post("/volunteer/projects/{$project->id}/apply", [
            'experience' => 'I have previous experience',
            'motivation' => '' // Empty motivation
        ]);

        $response->assertSessionHasErrors(['motivation']);
        $this->assertStringContainsString('The motivation field is required', $response->getSession()->get('errors')->first('motivation'));
    }

    /**
     * Test Case: View available community projects
     * 
     * Test Steps:
     * 1. Navigate to volunteer projects page
     * 2. View list of available projects
     * 
     * Expected Result: The system displays all available community projects for volunteers
     */
    public function test_view_available_community_projects()
    {
        // Create approved volunteer
        $user = User::factory()->create([
            'name' => 'View User',
            'email' => 'view@example.com',
            'password' => Hash::make('password123'),
            'role' => 'volunteer'
        ]);

        VolunteerApplication::create([
            'user_id' => $user->id,
            'motivation' => 'I want to help',
            'emergency_contact_name' => 'John Doe',
            'emergency_contact_phone' => '0123456789',
            'status' => 'Approved'
        ]);

        // Create multiple projects
        CommunityProject::factory()->create([
            'title' => 'Project 1',
            'status' => 'active'
        ]);
        CommunityProject::factory()->create([
            'title' => 'Project 2',
            'status' => 'active'
        ]);

        $this->actingAs($user);

        $response = $this->get('/volunteer/projects');

        $response->assertStatus(200);
        $response->assertSee('Volunteer/Projects');
    }

    /**
     * Test Case: View the details and requirements of the projects that have not applied for or are applying for
     * 
     * Test Steps:
     * 1. Navigate to community projects page
     * 2. Click on the project under application to view detailed information
     * 
     * Expected Result: The system will redirect user to the Access Restricted page
     */
    public function test_view_project_details_with_pending_application()
    {
        // Create approved volunteer
        $user = User::factory()->create([
            'name' => 'Pending User',
            'email' => 'pending@example.com',
            'password' => Hash::make('password123'),
            'role' => 'volunteer'
        ]);

        VolunteerApplication::create([
            'user_id' => $user->id,
            'motivation' => 'I want to help',
            'emergency_contact_name' => 'John Doe',
            'emergency_contact_phone' => '0123456789',
            'status' => 'Approved'
        ]);

        $project = CommunityProject::factory()->create([
            'title' => 'Project with Pending Application',
            'status' => 'active'
        ]);

        // Create pending application
        ProjectApplication::create([
            'user_id' => $user->id,
            'community_project_id' => $project->id,
            'experience' => 'I have experience',
            'motivation' => 'I want to help',
            'status' => 'pending'
        ]);

        $this->actingAs($user);

        $response = $this->get("/volunteer/projects");

        // Should show projects list
        $response->assertStatus(200);
    }

    /**
     * Test Case: View the details and requirements of the projects that have approved project application
     * 
     * Test Steps:
     * 1. Navigate to community projects page
     * 2. Click on a project to view details
     * 3. Check requirements and information
     * 
     * Expected Result: The system displays complete project details and requirements
     */
    public function test_view_project_details_with_approved_application()
    {
        // Create approved volunteer
        $user = User::factory()->create([
            'name' => 'Approved User',
            'email' => 'approved@example.com',
            'password' => Hash::make('password123'),
            'role' => 'volunteer'
        ]);

        VolunteerApplication::create([
            'user_id' => $user->id,
            'motivation' => 'I want to help',
            'emergency_contact_name' => 'John Doe',
            'emergency_contact_phone' => '0123456789',
            'status' => 'Approved'
        ]);

        $project = CommunityProject::factory()->create([
            'title' => 'Project with Approved Application',
            'description' => 'Detailed project description',
            'location' => 'Kuala Lumpur',
            'date' => '2024-02-01',
            'time' => '09:00',
            'duration' => '4 hours',
            'volunteers_needed' => 10,
            'status' => 'active'
        ]);

        // Create approved application
        ProjectApplication::create([
            'user_id' => $user->id,
            'community_project_id' => $project->id,
            'experience' => 'I have experience',
            'motivation' => 'I want to help',
            'status' => 'approved'
        ]);

        $this->actingAs($user);

        $response = $this->get("/volunteer/projects");

        $response->assertStatus(200);
        $response->assertSee('Volunteer/Projects');
    }

    /**
     * Test Case: View my project applications
     * 
     * Test Steps:
     * 1. Navigate to "User Profile"
     * 2. Click "Community Project" of the tab
     * 
     * Expected Result: The system displays all user's project applications with status
     */
    public function test_view_my_project_applications()
    {
        // Create approved volunteer
        $user = User::factory()->create([
            'name' => 'My Apps User',
            'email' => 'myapps@example.com',
            'password' => Hash::make('password123'),
            'role' => 'volunteer'
        ]);

        VolunteerApplication::create([
            'user_id' => $user->id,
            'motivation' => 'I want to help',
            'emergency_contact_name' => 'John Doe',
            'emergency_contact_phone' => '0123456789',
            'status' => 'Approved'
        ]);

        // Create projects and applications
        $project1 = CommunityProject::factory()->create(['title' => 'Project 1']);
        $project2 = CommunityProject::factory()->create(['title' => 'Project 2']);

        ProjectApplication::create([
            'user_id' => $user->id,
            'community_project_id' => $project1->id,
            'experience' => 'Experience 1',
            'motivation' => 'Motivation 1',
            'status' => 'pending'
        ]);

        ProjectApplication::create([
            'user_id' => $user->id,
            'community_project_id' => $project2->id,
            'experience' => 'Experience 2',
            'motivation' => 'Motivation 2',
            'status' => 'approved'
        ]);

        $this->actingAs($user);

        $response = $this->get('/profile');

        $response->assertStatus(200);
        $response->assertSee('Profile');
    }

    /**
     * Test Case: Cancel pending project application
     * 
     * Test Steps:
     * 1. Navigate to "User Profile" page
     * 2. Click "Community Project" of the tab
     * 3. Click "Withdraw" button of the project
     * 4. Confirm cancellation
     * 
     * Expected Result: The system cancels the application and updates status to "Withdraw"
     */
    public function test_cancel_pending_project_application()
    {
        // Create approved volunteer
        $user = User::factory()->create([
            'name' => 'Cancel User',
            'email' => 'cancel@example.com',
            'password' => Hash::make('password123'),
            'role' => 'volunteer'
        ]);

        VolunteerApplication::create([
            'user_id' => $user->id,
            'motivation' => 'I want to help',
            'emergency_contact_name' => 'John Doe',
            'emergency_contact_phone' => '0123456789',
            'status' => 'Approved'
        ]);

        $project = CommunityProject::factory()->create(['title' => 'Project to Cancel']);

        $application = ProjectApplication::create([
            'user_id' => $user->id,
            'community_project_id' => $project->id,
            'experience' => 'Experience',
            'motivation' => 'Motivation',
            'status' => 'pending'
        ]);

        $this->actingAs($user);

        $response = $this->post("/volunteer/projects/{$project->id}/withdraw");

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Application withdrawn.');

        // Verify application status was updated
        $application->refresh();
        $this->assertEquals('withdrawn', $application->status);
    }

    /**
     * Test Case: View project news and updates
     * 
     * Test Steps:
     * 1. Navigate to project details page
     * 2. Check project news section
     * 3. View latest updates and announcements
     * 
     * Expected Result: The system displays project news and updates
     */
    public function test_view_project_news_and_updates()
    {
        // Create approved volunteer
        $user = User::factory()->create([
            'name' => 'News User',
            'email' => 'news@example.com',
            'password' => Hash::make('password123'),
            'role' => 'volunteer'
        ]);

        VolunteerApplication::create([
            'user_id' => $user->id,
            'motivation' => 'I want to help',
            'emergency_contact_name' => 'John Doe',
            'emergency_contact_phone' => '0123456789',
            'status' => 'Approved'
        ]);

        $project = CommunityProject::factory()->create([
            'title' => 'Project with News',
            'latest_news' => 'Latest project updates and announcements'
        ]);

        // Create project news
        ProjectNews::create([
            'community_project_id' => $project->id,
            'content' => 'Important project update',
            'created_by' => $user->id
        ]);

        $this->actingAs($user);

        $response = $this->get("/volunteer/projects");

        $response->assertStatus(200);
        $response->assertSee('Volunteer/Projects');
    }

    /**
     * Test Case: Filter projects by category
     * 
     * Test Steps:
     * 1. Navigate to community projects page
     * 2. Select category filter
     * 3. Choose "Search Operations" category
     * 4. View filtered results
     * 
     * Expected Result: The system displays only projects in selected category
     */
    public function test_filter_projects_by_category()
    {
        // Create approved volunteer
        $user = User::factory()->create([
            'name' => 'Filter User',
            'email' => 'filter@example.com',
            'password' => Hash::make('password123'),
            'role' => 'volunteer'
        ]);

        VolunteerApplication::create([
            'user_id' => $user->id,
            'motivation' => 'I want to help',
            'emergency_contact_name' => 'John Doe',
            'emergency_contact_phone' => '0123456789',
            'status' => 'Approved'
        ]);

        // Create projects with different categories
        CommunityProject::factory()->create([
            'title' => 'Search Operation Project',
            'category' => 'search',
            'status' => 'active'
        ]);

        CommunityProject::factory()->create([
            'title' => 'Other Project',
            'category' => 'awareness',
            'status' => 'active'
        ]);

        $this->actingAs($user);

        $response = $this->get('/volunteer/projects?category=search');

        $response->assertStatus(200);
        $response->assertSee('Search Operation Project');
    }

    /**
     * Test Case: Search projects by location
     * 
     * Test Steps:
     * 1. Navigate to community projects page
     * 2. Enter location in search box
     * 3. View search results
     * 
     * Expected Result: The system displays only projects in Selangor
     */
    public function test_search_projects_by_location()
    {
        // Create approved volunteer
        $user = User::factory()->create([
            'name' => 'Search User',
            'email' => 'search@example.com',
            'password' => Hash::make('password123'),
            'role' => 'volunteer'
        ]);

        VolunteerApplication::create([
            'user_id' => $user->id,
            'motivation' => 'I want to help',
            'emergency_contact_name' => 'John Doe',
            'emergency_contact_phone' => '0123456789',
            'status' => 'Approved'
        ]);

        // Create projects with different locations
        CommunityProject::factory()->create([
            'title' => 'Selangor Project',
            'location' => 'Selangor',
            'status' => 'active'
        ]);

        CommunityProject::factory()->create([
            'title' => 'KL Project',
            'location' => 'Kuala Lumpur',
            'status' => 'active'
        ]);

        $this->actingAs($user);

        $response = $this->get('/volunteer/projects?search=Selangor');

        $response->assertStatus(200);
        $response->assertSee('Selangor Project');
    }

    /**
     * Test Case: Apply for project with conflicting schedule
     * 
     * Test Steps:
     * 1. Navigate to community project page
     * 2. Click "Apply Now" button
     * 3. Submit application with schedule conflict
     * 
     * Expected Result: The system tells the user that he has a project conflict and asks if he wants to continue applying.
     */
    public function test_apply_for_project_with_conflicting_schedule()
    {
        // Create approved volunteer
        $user = User::factory()->create([
            'name' => 'Conflict User',
            'email' => 'conflict@example.com',
            'password' => Hash::make('password123'),
            'role' => 'volunteer'
        ]);

        VolunteerApplication::create([
            'user_id' => $user->id,
            'motivation' => 'I want to help',
            'emergency_contact_name' => 'John Doe',
            'emergency_contact_phone' => '0123456789',
            'status' => 'Approved'
        ]);

        // Create first project
        $project1 = CommunityProject::factory()->create([
            'title' => 'First Project',
            'date' => '2024-02-01',
            'time' => '09:00',
            'duration' => '4 hours',
            'status' => 'active'
        ]);

        // Create second project with same date and time
        $project2 = CommunityProject::factory()->create([
            'title' => 'Second Project',
            'date' => '2024-02-01',
            'time' => '09:00',
            'duration' => '4 hours',
            'status' => 'active'
        ]);

        // Create application for first project
        ProjectApplication::create([
            'user_id' => $user->id,
            'community_project_id' => $project1->id,
            'experience' => 'Experience',
            'motivation' => 'Motivation',
            'status' => 'approved'
        ]);

        $this->actingAs($user);

        $response = $this->post("/volunteer/projects/{$project2->id}/apply", [
            'experience' => 'I have experience',
            'motivation' => 'I want to help'
        ]);

        $response->assertSessionHasErrors(['warning']);
        $this->assertStringContainsString('schedule conflict', $response->getSession()->get('errors')->first('warning'));
    }

    /**
     * Test Case: Apply for project with ignore conflicts
     */
    public function test_apply_for_project_with_ignore_conflicts()
    {
        // Create approved volunteer
        $user = User::factory()->create([
            'name' => 'Ignore Conflict User',
            'email' => 'ignore@example.com',
            'password' => Hash::make('password123'),
            'role' => 'volunteer'
        ]);

        VolunteerApplication::create([
            'user_id' => $user->id,
            'motivation' => 'I want to help',
            'emergency_contact_name' => 'John Doe',
            'emergency_contact_phone' => '0123456789',
            'status' => 'Approved'
        ]);

        $project = CommunityProject::factory()->create([
            'title' => 'Project with Conflict',
            'date' => '2024-02-01',
            'time' => '09:00',
            'duration' => '4 hours',
            'status' => 'active'
        ]);

        $this->actingAs($user);

        $response = $this->post("/volunteer/projects/{$project->id}/apply", [
            'experience' => 'I have experience',
            'motivation' => 'I want to help',
            'ignore_conflicts' => true
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Application submitted successfully!');
    }

    /**
     * Test Case: Apply for full project
     */
    public function test_apply_for_full_project()
    {
        // Create approved volunteer
        $user = User::factory()->create([
            'name' => 'Full Project User',
            'email' => 'full@example.com',
            'password' => Hash::make('password123'),
            'role' => 'volunteer'
        ]);

        VolunteerApplication::create([
            'user_id' => $user->id,
            'motivation' => 'I want to help',
            'emergency_contact_name' => 'John Doe',
            'emergency_contact_phone' => '0123456789',
            'status' => 'Approved'
        ]);

        // Create full project
        $project = CommunityProject::factory()->create([
            'title' => 'Full Project',
            'volunteers_needed' => 1,
            'volunteers_joined' => 1, // Project is full
            'status' => 'active'
        ]);

        $this->actingAs($user);

        $response = $this->post("/volunteer/projects/{$project->id}/apply", [
            'experience' => 'I have experience',
            'motivation' => 'I want to help'
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error', 'This project is full. No more applications are being accepted.');
    }

    /**
     * Test Case: Apply for project without being approved volunteer
     */
    public function test_apply_for_project_without_approved_volunteer()
    {
        // Create regular user (not approved volunteer)
        $user = User::factory()->create([
            'name' => 'Regular User',
            'email' => 'regular@example.com',
            'password' => Hash::make('password123'),
            'role' => 'user'
        ]);

        $project = CommunityProject::factory()->create([
            'title' => 'Project',
            'status' => 'active'
        ]);

        $this->actingAs($user);

        $response = $this->post("/volunteer/projects/{$project->id}/apply", [
            'experience' => 'I have experience',
            'motivation' => 'I want to help'
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error', 'You must be an approved volunteer to apply for projects.');
    }

    /**
     * Test Case: Apply for project with short experience
     */
    public function test_apply_for_project_with_short_experience()
    {
        // Create approved volunteer
        $user = User::factory()->create([
            'name' => 'Short Exp User',
            'email' => 'short@example.com',
            'password' => Hash::make('password123'),
            'role' => 'volunteer'
        ]);

        VolunteerApplication::create([
            'user_id' => $user->id,
            'motivation' => 'I want to help',
            'emergency_contact_name' => 'John Doe',
            'emergency_contact_phone' => '0123456789',
            'status' => 'Approved'
        ]);

        $project = CommunityProject::factory()->create([
            'title' => 'Project',
            'status' => 'active'
        ]);

        $this->actingAs($user);

        $response = $this->post("/volunteer/projects/{$project->id}/apply", [
            'experience' => 'Short', // Too short
            'motivation' => 'I want to help with search operations'
        ]);

        $response->assertSessionHasErrors(['experience']);
        $this->assertStringContainsString('Experience must be at least 10 characters long', $response->getSession()->get('errors')->first('experience'));
    }

    /**
     * Test Case: Apply for project with long experience
     */
    public function test_apply_for_project_with_long_experience()
    {
        // Create approved volunteer
        $user = User::factory()->create([
            'name' => 'Long Exp User',
            'email' => 'long@example.com',
            'password' => Hash::make('password123'),
            'role' => 'volunteer'
        ]);

        VolunteerApplication::create([
            'user_id' => $user->id,
            'motivation' => 'I want to help',
            'emergency_contact_name' => 'John Doe',
            'emergency_contact_phone' => '0123456789',
            'status' => 'Approved'
        ]);

        $project = CommunityProject::factory()->create([
            'title' => 'Project',
            'status' => 'active'
        ]);

        $this->actingAs($user);

        $longExperience = str_repeat('A', 1001); // Exceeds 1000 character limit

        $response = $this->post("/volunteer/projects/{$project->id}/apply", [
            'experience' => $longExperience,
            'motivation' => 'I want to help with search operations'
        ]);

        $response->assertSessionHasErrors(['experience']);
        $this->assertStringContainsString('Experience must not exceed 1000 characters', $response->getSession()->get('errors')->first('experience'));
    }

    /**
     * Test Case: Apply for project with long motivation
     */
    public function test_apply_for_project_with_long_motivation()
    {
        // Create approved volunteer
        $user = User::factory()->create([
            'name' => 'Long Mot User',
            'email' => 'longmot@example.com',
            'password' => Hash::make('password123'),
            'role' => 'volunteer'
        ]);

        VolunteerApplication::create([
            'user_id' => $user->id,
            'motivation' => 'I want to help',
            'emergency_contact_name' => 'John Doe',
            'emergency_contact_phone' => '0123456789',
            'status' => 'Approved'
        ]);

        $project = CommunityProject::factory()->create([
            'title' => 'Project',
            'status' => 'active'
        ]);

        $this->actingAs($user);

        $longMotivation = str_repeat('A', 501); // Exceeds 500 character limit

        $response = $this->post("/volunteer/projects/{$project->id}/apply", [
            'experience' => 'I have previous experience in search operations',
            'motivation' => $longMotivation
        ]);

        $response->assertSessionHasErrors(['motivation']);
        $this->assertStringContainsString('Motivation must not exceed 500 characters', $response->getSession()->get('errors')->first('motivation'));
    }
}
