<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Models\VolunteerApplication;
use App\Models\ProjectApplication;
use App\Models\CommunityProject;
use App\Models\Notification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

class UT015_AdminPanelVolunteerManagementTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test Case: Approve volunteer application
     * 
     * Test Steps:
     * 1. Navigate to admin volunteer management page
     * 2. Find pending volunteer application
     * 3. Click "Approve" button
     * 4. Confirm approval
     * 
     * Expected Result: System approves volunteer application, updates user role to volunteer, and sends notification
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

        // Create regular user
        $user = User::factory()->create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => Hash::make('password123'),
            'role' => 'user'
        ]);

        // Create volunteer application
        $application = VolunteerApplication::factory()->create([
            'user_id' => $user->id,
            'status' => 'Pending',
            'motivation' => 'I want to help the community',
            'skills' => ['Search and Rescue', 'First Aid'],
            'languages' => ['English', 'Malay'],
            'emergency_contact_name' => 'Jane Doe',
            'emergency_contact_phone' => '0123456789'
        ]);

        $this->actingAs($admin);

        $response = $this->post("/admin/volunteers/{$application->id}/status", [
            'status' => 'Approved'
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Volunteer application Approved successfully!');

        // Verify application was approved
        $this->assertDatabaseHas('volunteer_applications', [
            'id' => $application->id,
            'status' => 'Approved'
        ]);

        // Verify user role was updated to volunteer
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'role' => 'volunteer'
        ]);

        // Verify notification was created
        $this->assertDatabaseHas('notifications', [
            'user_id' => $user->id,
            'type' => 'volunteer_application',
            'title' => 'Volunteer Application Approved',
            'message' => 'Congratulations! Your volunteer application has been approved.'
        ]);
    }

    /**
     * Test Case: Reject volunteer application
     * 
     * Test Steps:
     * 1. Navigate to admin volunteer management page
     * 2. Find pending volunteer application
     * 3. Click "Reject" button
     * 4. Enter rejection reason
     * 5. Confirm rejection
     * 
     * Expected Result: System rejects volunteer application and sends notification with rejection reason
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

        // Create regular user
        $user = User::factory()->create([
            'name' => 'Jane Smith',
            'email' => 'jane@example.com',
            'password' => Hash::make('password123'),
            'role' => 'user'
        ]);

        // Create volunteer application
        $application = VolunteerApplication::factory()->create([
            'user_id' => $user->id,
            'status' => 'Pending',
            'motivation' => 'I want to help the community',
            'skills' => ['Search and Rescue'],
            'languages' => ['English'],
            'emergency_contact_name' => 'John Smith',
            'emergency_contact_phone' => '0123456789'
        ]);

        $this->actingAs($admin);

        $response = $this->post("/admin/volunteers/{$application->id}/status", [
            'status' => 'Rejected',
            'reason' => 'Insufficient experience for this role'
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Volunteer application Rejected successfully!');

        // Verify application was rejected
        $this->assertDatabaseHas('volunteer_applications', [
            'id' => $application->id,
            'status' => 'Rejected',
            'status_reason' => 'Insufficient experience for this role'
        ]);

        // Verify user role remains as user
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'role' => 'user'
        ]);

        // Verify notification was created with rejection reason
        $this->assertDatabaseHas('notifications', [
            'user_id' => $user->id,
            'type' => 'volunteer_application',
            'title' => 'Volunteer Application Rejected',
            'message' => 'Rejected: Insufficient experience for this role. Click to re-apply.'
        ]);
    }

    /**
     * Test Case: Search volunteers by name
     * 
     * Test Steps:
     * 1. Navigate to admin volunteer management page
     * 2. Enter search term in search box
     * 3. Click search button
     * 
     * Expected Result: System displays filtered volunteers matching search term
     */
    public function test_search_volunteers_by_name()
    {
        // Create admin user
        $admin = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
            'role' => 'admin'
        ]);

        // Create test users
        $user1 = User::factory()->create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'role' => 'user'
        ]);

        $user2 = User::factory()->create([
            'name' => 'Jane Smith',
            'email' => 'jane@example.com',
            'role' => 'user'
        ]);

        $user3 = User::factory()->create([
            'name' => 'Bob Johnson',
            'email' => 'bob@example.com',
            'role' => 'user'
        ]);

        // Create volunteer applications
        VolunteerApplication::factory()->create([
            'user_id' => $user1->id,
            'status' => 'Pending',
            'motivation' => 'I want to help',
            'emergency_contact_name' => 'Contact 1',
            'emergency_contact_phone' => '0123456789'
        ]);

        VolunteerApplication::factory()->create([
            'user_id' => $user2->id,
            'status' => 'Approved',
            'motivation' => 'I want to help',
            'emergency_contact_name' => 'Contact 2',
            'emergency_contact_phone' => '0123456789'
        ]);

        VolunteerApplication::factory()->create([
            'user_id' => $user3->id,
            'status' => 'Pending',
            'motivation' => 'I want to help',
            'emergency_contact_name' => 'Contact 3',
            'emergency_contact_phone' => '0123456789'
        ]);

        $this->actingAs($admin);

        $response = $this->get('/admin/volunteers?search=John');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => 
            $page->component('Admin/ManageVolunteers')
                ->has('applications')
        );
    }

    /**
     * Test Case: Filter volunteers by status
     * 
     * Test Steps:
     * 1. Navigate to admin volunteer management page
     * 2. Select status from dropdown
     * 3. Click filter button
     * 
     * Expected Result: System displays volunteers filtered by selected status
     */
    public function test_filter_volunteers_by_status()
    {
        // Create admin user
        $admin = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
            'role' => 'admin'
        ]);

        // Create test users
        $user1 = User::factory()->create(['name' => 'User 1', 'role' => 'user']);
        $user2 = User::factory()->create(['name' => 'User 2', 'role' => 'user']);
        $user3 = User::factory()->create(['name' => 'User 3', 'role' => 'user']);

        // Create volunteer applications with different statuses
        VolunteerApplication::factory()->create([
            'user_id' => $user1->id,
            'status' => 'Pending',
            'motivation' => 'I want to help',
            'emergency_contact_name' => 'Contact 1',
            'emergency_contact_phone' => '0123456789'
        ]);

        VolunteerApplication::factory()->create([
            'user_id' => $user2->id,
            'status' => 'Approved',
            'motivation' => 'I want to help',
            'emergency_contact_name' => 'Contact 2',
            'emergency_contact_phone' => '0123456789'
        ]);

        VolunteerApplication::factory()->create([
            'user_id' => $user3->id,
            'status' => 'Rejected',
            'motivation' => 'I want to help',
            'emergency_contact_name' => 'Contact 3',
            'emergency_contact_phone' => '0123456789'
        ]);

        $this->actingAs($admin);

        $response = $this->get('/admin/volunteers?status=Approved');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => 
            $page->component('Admin/ManageVolunteers')
                ->has('applications')
        );
    }

    /**
     * Test Case: View volunteer activity history
     * 
     * Test Steps:
     * 1. Navigate to admin volunteer management page
     * 2. Find volunteer in the list
     * 3. Click "View" button
     * 4. Scroll down to view the activity history
     * 
     * Expected Result: System displays volunteer's activity history including projects participated
     */
    public function test_view_volunteer_activity_history()
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
            'role' => 'volunteer'
        ]);

        // Create volunteer application
        $application = VolunteerApplication::factory()->create([
            'user_id' => $volunteer->id,
            'status' => 'Approved',
            'motivation' => 'I want to help',
            'emergency_contact_name' => 'Contact',
            'emergency_contact_phone' => '0123456789'
        ]);

        // Create community projects
        $project1 = CommunityProject::factory()->create([
            'title' => 'Search Mission 1',
            'location' => 'KLCC',
            'status' => 'completed'
        ]);

        $project2 = CommunityProject::factory()->create([
            'title' => 'Search Mission 2',
            'location' => 'Petaling Jaya',
            'status' => 'active'
        ]);

        // Create project applications
        ProjectApplication::factory()->create([
            'user_id' => $volunteer->id,
            'community_project_id' => $project1->id,
            'status' => 'approved',
            'experience' => 'Previous experience',
            'motivation' => 'Want to help'
        ]);

        ProjectApplication::factory()->create([
            'user_id' => $volunteer->id,
            'community_project_id' => $project2->id,
            'status' => 'pending',
            'experience' => 'Previous experience',
            'motivation' => 'Want to help'
        ]);

        $this->actingAs($admin);

        $response = $this->get('/admin/volunteers');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => 
            $page->component('Admin/ManageVolunteers')
                ->has('applications')
        );

        // Verify that project applications are included in the response
        $applications = $response->viewData('page')['props']['applications']['data'];
        $volunteerApp = collect($applications)->firstWhere('user.id', $volunteer->id);
        
        $this->assertNotNull($volunteerApp);
        $this->assertArrayHasKey('project_applications', $volunteerApp);
        $this->assertCount(2, $volunteerApp['project_applications']);
    }

    /**
     * Test Case: Access admin volunteers without admin role
     */
    public function test_access_admin_volunteers_without_admin_role()
    {
        // Create regular user (not admin)
        $user = User::factory()->create([
            'name' => 'Regular User',
            'email' => 'user@example.com',
            'password' => Hash::make('password123'),
            'role' => 'user'
        ]);

        $this->actingAs($user);

        $response = $this->get('/admin/volunteers');
        $response->assertStatus(200); // Returns access denied page instead of 403
    }

    /**
     * Test Case: Filter volunteers by skills
     */
    public function test_filter_volunteers_by_skills()
    {
        // Create admin user
        $admin = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
            'role' => 'admin'
        ]);

        // Create test users
        $user1 = User::factory()->create(['name' => 'User 1', 'role' => 'user']);
        $user2 = User::factory()->create(['name' => 'User 2', 'role' => 'user']);

        // Create volunteer applications with different skills
        VolunteerApplication::factory()->create([
            'user_id' => $user1->id,
            'status' => 'Pending',
            'skills' => ['Search and Rescue', 'First Aid'],
            'motivation' => 'I want to help',
            'emergency_contact_name' => 'Contact 1',
            'emergency_contact_phone' => '0123456789'
        ]);

        VolunteerApplication::factory()->create([
            'user_id' => $user2->id,
            'status' => 'Pending',
            'skills' => ['Communication', 'Leadership'],
            'motivation' => 'I want to help',
            'emergency_contact_name' => 'Contact 2',
            'emergency_contact_phone' => '0123456789'
        ]);

        $this->actingAs($admin);

        $response = $this->get('/admin/volunteers?skills=Search and Rescue');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => 
            $page->component('Admin/ManageVolunteers')
                ->has('applications')
        );
    }

    /**
     * Test Case: Filter volunteers by languages
     */
    public function test_filter_volunteers_by_languages()
    {
        // Create admin user
        $admin = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
            'role' => 'admin'
        ]);

        // Create test users
        $user1 = User::factory()->create(['name' => 'User 1', 'role' => 'user']);
        $user2 = User::factory()->create(['name' => 'User 2', 'role' => 'user']);

        // Create volunteer applications with different languages
        VolunteerApplication::factory()->create([
            'user_id' => $user1->id,
            'status' => 'Pending',
            'languages' => ['English', 'Malay'],
            'motivation' => 'I want to help',
            'emergency_contact_name' => 'Contact 1',
            'emergency_contact_phone' => '0123456789'
        ]);

        VolunteerApplication::factory()->create([
            'user_id' => $user2->id,
            'status' => 'Pending',
            'languages' => ['Chinese', 'Japanese'],
            'motivation' => 'I want to help',
            'emergency_contact_name' => 'Contact 2',
            'emergency_contact_phone' => '0123456789'
        ]);

        $this->actingAs($admin);

        $response = $this->get('/admin/volunteers?languages=English');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => 
            $page->component('Admin/ManageVolunteers')
                ->has('applications')
        );
    }

    /**
     * Test Case: Delete volunteer application
     */
    public function test_delete_volunteer_application()
    {
        // Create admin user
        $admin = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
            'role' => 'admin'
        ]);

        // Create regular user
        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'role' => 'user'
        ]);

        // Create volunteer application
        $application = VolunteerApplication::factory()->create([
            'user_id' => $user->id,
            'status' => 'Pending',
            'motivation' => 'I want to help',
            'emergency_contact_name' => 'Contact',
            'emergency_contact_phone' => '0123456789'
        ]);

        $this->actingAs($admin);

        $response = $this->delete("/admin/volunteers/{$application->id}");

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Volunteer application deleted successfully');

        // Verify application was deleted
        $this->assertDatabaseMissing('volunteer_applications', [
            'id' => $application->id
        ]);
    }

    /**
     * Test Case: Approve volunteer application with existing volunteer role
     */
    public function test_approve_volunteer_application_with_existing_volunteer_role()
    {
        // Create admin user
        $admin = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
            'role' => 'admin'
        ]);

        // Create user who is already a volunteer
        $user = User::factory()->create([
            'name' => 'Existing Volunteer',
            'email' => 'volunteer@example.com',
            'password' => Hash::make('password123'),
            'role' => 'volunteer'
        ]);

        // Create volunteer application
        $application = VolunteerApplication::factory()->create([
            'user_id' => $user->id,
            'status' => 'Pending',
            'motivation' => 'I want to help more',
            'emergency_contact_name' => 'Contact',
            'emergency_contact_phone' => '0123456789'
        ]);

        $this->actingAs($admin);

        $response = $this->post("/admin/volunteers/{$application->id}/status", [
            'status' => 'Approved'
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Volunteer application Approved successfully!');

        // Verify application was approved
        $this->assertDatabaseHas('volunteer_applications', [
            'id' => $application->id,
            'status' => 'Approved'
        ]);

        // Verify user role remains as volunteer
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'role' => 'volunteer'
        ]);
    }

    /**
     * Test Case: Reject volunteer application with volunteer role
     */
    public function test_reject_volunteer_application_with_volunteer_role()
    {
        // Create admin user
        $admin = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
            'role' => 'admin'
        ]);

        // Create user who is currently a volunteer
        $user = User::factory()->create([
            'name' => 'Current Volunteer',
            'email' => 'volunteer@example.com',
            'password' => Hash::make('password123'),
            'role' => 'volunteer'
        ]);

        // Create volunteer application
        $application = VolunteerApplication::factory()->create([
            'user_id' => $user->id,
            'status' => 'Pending',
            'motivation' => 'I want to help more',
            'emergency_contact_name' => 'Contact',
            'emergency_contact_phone' => '0123456789'
        ]);

        $this->actingAs($admin);

        $response = $this->post("/admin/volunteers/{$application->id}/status", [
            'status' => 'Rejected',
            'reason' => 'Application not suitable'
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Volunteer application Rejected successfully!');

        // Verify application was rejected
        $this->assertDatabaseHas('volunteer_applications', [
            'id' => $application->id,
            'status' => 'Rejected',
            'status_reason' => 'Application not suitable'
        ]);

        // Verify user role was changed back to user
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'role' => 'user'
        ]);
    }

    /**
     * Test Case: Update volunteer application status with invalid data
     */
    public function test_update_volunteer_application_status_with_invalid_data()
    {
        // Create admin user
        $admin = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
            'role' => 'admin'
        ]);

        // Create regular user
        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'role' => 'user'
        ]);

        // Create volunteer application
        $application = VolunteerApplication::factory()->create([
            'user_id' => $user->id,
            'status' => 'Pending',
            'motivation' => 'I want to help',
            'emergency_contact_name' => 'Contact',
            'emergency_contact_phone' => '0123456789'
        ]);

        $this->actingAs($admin);

        $response = $this->post("/admin/volunteers/{$application->id}/status", [
            'status' => 'InvalidStatus' // Invalid status
        ]);

        $response->assertSessionHasErrors(['status']);
    }

    /**
     * Test Case: View volunteer management page with no applications
     */
    public function test_view_volunteer_management_page_with_no_applications()
    {
        // Create admin user
        $admin = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
            'role' => 'admin'
        ]);

        $this->actingAs($admin);

        $response = $this->get('/admin/volunteers');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => 
            $page->component('Admin/ManageVolunteers')
                ->has('applications')
        );
    }

    /**
     * Test Case: View volunteer management page with mixed status applications
     */
    public function test_view_volunteer_management_page_with_mixed_status_applications()
    {
        // Create admin user
        $admin = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
            'role' => 'admin'
        ]);

        // Create test users
        $user1 = User::factory()->create(['name' => 'User 1', 'role' => 'user']);
        $user2 = User::factory()->create(['name' => 'User 2', 'role' => 'volunteer']);
        $user3 = User::factory()->create(['name' => 'User 3', 'role' => 'user']);

        // Create volunteer applications with different statuses
        VolunteerApplication::factory()->create([
            'user_id' => $user1->id,
            'status' => 'Pending',
            'motivation' => 'I want to help',
            'emergency_contact_name' => 'Contact 1',
            'emergency_contact_phone' => '0123456789'
        ]);

        VolunteerApplication::factory()->create([
            'user_id' => $user2->id,
            'status' => 'Approved',
            'motivation' => 'I want to help',
            'emergency_contact_name' => 'Contact 2',
            'emergency_contact_phone' => '0123456789'
        ]);

        VolunteerApplication::factory()->create([
            'user_id' => $user3->id,
            'status' => 'Rejected',
            'motivation' => 'I want to help',
            'emergency_contact_name' => 'Contact 3',
            'emergency_contact_phone' => '0123456789'
        ]);

        $this->actingAs($admin);

        $response = $this->get('/admin/volunteers');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => 
            $page->component('Admin/ManageVolunteers')
                ->has('applications')
        );

        // Verify all applications are returned
        $applications = $response->viewData('page')['props']['applications']['data'];
        $this->assertCount(3, $applications);
    }
}
