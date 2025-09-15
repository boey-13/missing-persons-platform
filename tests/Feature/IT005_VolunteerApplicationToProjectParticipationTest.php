<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\VolunteerApplication;
use App\Models\CommunityProject;
use App\Models\ProjectApplication;
use App\Models\UserPoint;
use App\Models\PointTransaction;
use App\Services\PointsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Hash;

class IT005_VolunteerApplicationToProjectParticipationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test the complete flow from user applying to become a volunteer to participating in community projects
     * 
     * Test Procedure:
     * 1. User logs in to the system
     * 2. Navigate to "Become a Volunteer" page
     * 3. Fill in volunteer application form
     * 4. Submit volunteer application
     * 5. Verify application is created with "Pending" status
     * 6. Admin logs in to admin panel
     * 7. Navigate to "Manage Volunteers"
     * 8. View the newly submitted volunteer application
     * 9. Review all application details and documents
     * 10. Approve the volunteer application
     * 11. Verify user role changes to "volunteer"
     * 12. Check that user receives approval notification
     * 13. User navigates to "Community Projects" page
     * 14. Browse available community projects
     * 15. Select a project and click "Apply Now"
     * 16. Fill in project application form
     * 17. Submit project application
     * 18. Verify project application is created with "Pending" status
     * 19. Admin reviews project application
     * 20. Approve the project application
     * 21. Verify project volunteer count increases
     * 22. Check that user receives project approval notification
     * 23. User can now access project details page
     * 24. Verify project completion awards points to user
     */
    public function test_complete_volunteer_application_to_project_participation_flow()
    {
        // Test Data
        $volunteerApplicationData = [
            'motivation' => 'I want to help the community and make a difference',
            'skills' => ['Communication', 'Leadership', 'First Aid'],
            'languages' => ['English', 'Malay', 'Chinese'],
            'availability' => ['Sun'],
            'preferred_roles' => ['Search Operations', 'Awareness Campaigns'],
            'areas' => 'Kuala Lumpur and surrounding areas',
            'transport_mode' => 'Public Transport',
            'emergency_contact_name' => 'Mary Hong',
            'emergency_contact_phone' => '0123456789',
            'prior_experience' => 'Volunteered at local charity for 2 years',
        ];

        $projectApplicationData = [
            'experience' => 'I have 2 years of volunteer experience in community service and search operations. I am trained in first aid and have good communication skills.',
            'motivation' => 'I am passionate about helping find missing persons and want to contribute to this important community project.',
        ];

        // Create user (applicant)
        $user = User::factory()->create([
            'name' => 'Mary Hong',
            'email' => 'mary@gmail.com',
            'password' => Hash::make('Password123!'),
            'role' => 'user'
        ]);

        // Create admin user
        $admin = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('AdminPassword123!'),
            'role' => 'admin'
        ]);

        // Create a community project
        $communityProject = CommunityProject::factory()->create([
            'title' => 'Search Operation for Missing Person',
            'category' => 'search',
            'status' => 'active',
            'volunteers_needed' => 10,
            'points_reward' => 50,
            'date' => '2024-02-15',
            'time' => '09:00',
            'duration' => '4 hours'
        ]);

        // Step 1: User logs in to the system
        $this->actingAs($user);

        // Step 2: Navigate to "Become a Volunteer" page
        $response = $this->get('/volunteer/apply');
        $response->assertStatus(200);
        $response->assertSee('Volunteer/BecomeVolunteer');

        // Step 3-4: Fill in volunteer application form and submit volunteer application
        Storage::fake('public');
        
        $supportingDoc = UploadedFile::fake()->create('resume.pdf', 1024, 'application/pdf'); // 1MB

        $applicationData = array_merge($volunteerApplicationData, [
            'supporting_documents' => [$supportingDoc]
        ]);

        $response = $this->post('/volunteer/apply', $applicationData);

        // Step 5: Verify application is created with "Pending" status
        $response->assertRedirect('/volunteer/application-pending');
        $response->assertSessionHas('success', 'Application submitted.');

        $this->assertDatabaseHas('volunteer_applications', [
            'user_id' => $user->id,
            'motivation' => 'I want to help the community and make a difference',
            'emergency_contact_name' => 'Mary Hong',
            'emergency_contact_phone' => '0123456789',
            'status' => 'Pending'
        ]);

        $volunteerApplication = VolunteerApplication::where('user_id', $user->id)->first();
        $this->assertNotNull($volunteerApplication);
        $this->assertEquals('Pending', $volunteerApplication->status);

        // Verify file was uploaded
        $this->assertCount(1, $volunteerApplication->supporting_documents);
        Storage::disk('public')->assertExists($volunteerApplication->supporting_documents[0]);

        // Step 6: Admin logs in to admin panel
        $this->post('/logout');
        $this->actingAs($admin);

        // Step 7: Navigate to "Manage Volunteers"
        $response = $this->get('/admin/volunteers');
        $response->assertStatus(200);
        $response->assertSee('Admin/ManageVolunteers');

        // Step 8-9: View the newly submitted volunteer application and review all application details and documents
        // Note: The admin interface shows applications in a list, not individual detail pages
        $this->assertDatabaseHas('volunteer_applications', [
            'user_id' => $user->id,
            'motivation' => 'I want to help the community and make a difference',
            'emergency_contact_name' => 'Mary Hong',
            'emergency_contact_phone' => '0123456789',
            'status' => 'Pending'
        ]);

        // Step 10: Approve the volunteer application
        $response = $this->post('/admin/volunteers/' . $volunteerApplication->id . '/status', [
            'status' => 'Approved'
        ]);

        $response->assertRedirect();

        // Step 11: Verify user role changes to "volunteer"
        $user->refresh();
        $this->assertEquals('volunteer', $user->role);

        $volunteerApplication->refresh();
        $this->assertEquals('Approved', $volunteerApplication->status);

        // Step 12: Check that user receives approval notification
        $this->assertDatabaseHas('notifications', [
            'user_id' => $user->id,
            'type' => 'volunteer_application'
        ]);

        // Step 13: User navigates to "Community Projects" page
        $this->post('/logout');
        $this->actingAs($user);

        $response = $this->get('/volunteer/projects');
        $response->assertStatus(200);
        $response->assertSee('Volunteer/Projects');

        // Step 14-15: Browse available community projects and select a project and click "Apply Now"
        $response = $this->get('/community-projects/' . $communityProject->id);
        $response->assertStatus(200);
        $response->assertSee('Search Operation for Missing Person');

        // Step 16-17: Fill in project application form and submit project application
        $response = $this->post('/volunteer/projects/' . $communityProject->id . '/apply', $projectApplicationData);

        // Step 18: Verify project application is created with "Pending" status
        $response->assertRedirect();
        $response->assertSessionHas('success', 'Application submitted successfully!');

        $this->assertDatabaseHas('project_applications', [
            'user_id' => $user->id,
            'community_project_id' => $communityProject->id,
            'experience' => 'I have 2 years of volunteer experience in community service and search operations. I am trained in first aid and have good communication skills.',
            'motivation' => 'I am passionate about helping find missing persons and want to contribute to this important community project.',
            'status' => 'pending'
        ]);

        $projectApplication = ProjectApplication::where('user_id', $user->id)
            ->where('community_project_id', $communityProject->id)
            ->first();
        $this->assertNotNull($projectApplication);
        $this->assertEquals('pending', $projectApplication->status);

        // Step 19-20: Admin reviews project application and approve the project application
        $this->post('/logout');
        $this->actingAs($admin);

        $response = $this->post('/admin/community-projects/applications/' . $projectApplication->id . '/approve');

        $response->assertJson(['success' => true]);

        // Step 21: Verify project volunteer count increases
        $communityProject->refresh();
        $this->assertEquals(1, $communityProject->volunteers_joined);

        $projectApplication->refresh();
        $this->assertEquals('approved', $projectApplication->status);

        // Step 22: Check that user receives project approval notification
        $this->assertDatabaseHas('notifications', [
            'user_id' => $user->id,
            'type' => 'project_application_approved'
        ]);

        // Step 23: User can now access project details page
        $this->post('/logout');
        $this->actingAs($user);

        $response = $this->get('/community-projects/' . $communityProject->id);
        $response->assertStatus(200);
        $response->assertSee('Search Operation for Missing Person');

        // Step 24: Verify project completion awards points to user
        $pointsService = new PointsService();
        $pointsService->awardCommunityProjectPoints($user, $communityProject->id, $communityProject->title, $communityProject->points_reward);

        $this->assertDatabaseHas('user_points', [
            'user_id' => $user->id,
            'current_points' => 50,
            'total_earned_points' => 50
        ]);

        $this->assertDatabaseHas('point_transactions', [
            'user_id' => $user->id,
            'type' => 'earned',
            'points' => 50,
            'action' => 'community_project',
            'description' => 'Completed community project: Search Operation for Missing Person'
        ]);
    }

    /**
     * Test volunteer application validation errors
     */
    public function test_volunteer_application_validation_errors()
    {
        $user = User::factory()->create([
            'name' => 'Mary Hong',
            'email' => 'mary@gmail.com',
            'password' => Hash::make('Password123!'),
            'role' => 'user'
        ]);

        $this->actingAs($user);

        // Test with missing required fields
        $response = $this->post('/volunteer/apply', [
            'motivation' => '',
            'emergency_contact_name' => '',
            'emergency_contact_phone' => '',
        ]);

        $response->assertSessionHasErrors(['motivation', 'emergency_contact_name', 'emergency_contact_phone']);
    }

    /**
     * Test emergency contact phone validation
     */
    public function test_emergency_contact_phone_validation()
    {
        $user = User::factory()->create([
            'name' => 'Mary Hong',
            'email' => 'mary@gmail.com',
            'password' => Hash::make('Password123!'),
            'role' => 'user'
        ]);

        $this->actingAs($user);

        // Test with invalid phone number
        $response = $this->post('/volunteer/apply', [
            'motivation' => 'I want to help',
            'emergency_contact_name' => 'Mary Hong',
            'emergency_contact_phone' => '123456789', // Invalid format
        ]);

        $response->assertSessionHasErrors(['emergency_contact_phone']);
    }

    /**
     * Test file upload validation for supporting documents
     */
    public function test_supporting_documents_validation()
    {
        $user = User::factory()->create([
            'name' => 'Mary Hong',
            'email' => 'mary@gmail.com',
            'password' => Hash::make('Password123!'),
            'role' => 'user'
        ]);

        $this->actingAs($user);

        Storage::fake('public');

        // Test with file too large
        $largeFile = UploadedFile::fake()->create('large.pdf', 6000, 'application/pdf'); // 6MB
        $invalidFile = UploadedFile::fake()->create('document.txt', 1024, 'text/plain');

        $response = $this->post('/volunteer/apply', [
            'motivation' => 'I want to help',
            'emergency_contact_name' => 'Mary Hong',
            'emergency_contact_phone' => '0123456789',
            'supporting_documents' => [$largeFile, $invalidFile]
        ]);

        // Check for validation errors (may be in different fields)
        $response->assertSessionHasErrors();
    }

    /**
     * Test project application validation errors
     */
    public function test_project_application_validation_errors()
    {
        $user = User::factory()->create([
            'name' => 'Mary Hong',
            'email' => 'mary@gmail.com',
            'password' => Hash::make('Password123!'),
            'role' => 'volunteer'
        ]);

        // Create approved volunteer application
        VolunteerApplication::factory()->create([
            'user_id' => $user->id,
            'status' => 'Approved'
        ]);

        $communityProject = CommunityProject::factory()->create([
            'status' => 'active'
        ]);

        $this->actingAs($user);

        // Test with missing required fields
        $response = $this->post('/volunteer/projects/' . $communityProject->id . '/apply', [
            'experience' => '',
            'motivation' => '',
        ]);

        $response->assertSessionHasErrors(['experience', 'motivation']);
    }

    /**
     * Test character count validation for project application
     */
    public function test_project_application_character_count_validation()
    {
        $user = User::factory()->create([
            'name' => 'Mary Hong',
            'email' => 'mary@gmail.com',
            'password' => Hash::make('Password123!'),
            'role' => 'volunteer'
        ]);

        // Create approved volunteer application
        VolunteerApplication::factory()->create([
            'user_id' => $user->id,
            'status' => 'Approved'
        ]);

        $communityProject = CommunityProject::factory()->create([
            'status' => 'active'
        ]);

        $this->actingAs($user);

        // Test with too short experience
        $response = $this->post('/volunteer/projects/' . $communityProject->id . '/apply', [
            'experience' => 'Short', // Too short
            'motivation' => 'I want to help the community and make a difference in finding missing persons.',
        ]);

        $response->assertSessionHasErrors(['experience']);

        // Test with too long experience
        $response = $this->post('/volunteer/projects/' . $communityProject->id . '/apply', [
            'experience' => str_repeat('a', 1001), // Too long
            'motivation' => 'I want to help the community and make a difference in finding missing persons.',
        ]);

        $response->assertSessionHasErrors(['experience']);

        // Test with too short motivation
        $response = $this->post('/volunteer/projects/' . $communityProject->id . '/apply', [
            'experience' => 'I have extensive experience in community service and search operations.',
            'motivation' => 'Short', // Too short
        ]);

        $response->assertSessionHasErrors(['motivation']);

        // Test with too long motivation
        $response = $this->post('/volunteer/projects/' . $communityProject->id . '/apply', [
            'experience' => 'I have extensive experience in community service and search operations.',
            'motivation' => str_repeat('a', 501), // Too long
        ]);

        $response->assertSessionHasErrors(['motivation']);
    }

    /**
     * Test admin can reject volunteer application
     */
    public function test_admin_can_reject_volunteer_application()
    {
        $user = User::factory()->create([
            'name' => 'Mary Hong',
            'email' => 'mary@gmail.com',
            'password' => Hash::make('Password123!'),
            'role' => 'user'
        ]);

        $admin = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('AdminPassword123!'),
            'role' => 'admin'
        ]);

        // Create a pending volunteer application
        $volunteerApplication = VolunteerApplication::factory()->create([
            'user_id' => $user->id,
            'status' => 'Pending'
        ]);

        $this->actingAs($admin);

        // Reject the volunteer application
        $response = $this->post('/admin/volunteers/' . $volunteerApplication->id . '/status', [
            'status' => 'Rejected',
            'reason' => 'Insufficient experience'
        ]);

        $response->assertRedirect();

        $volunteerApplication->refresh();
        $this->assertEquals('Rejected', $volunteerApplication->status);
        $this->assertEquals('Insufficient experience', $volunteerApplication->status_reason);

        // Verify user role remains unchanged
        $user->refresh();
        $this->assertEquals('user', $user->role);
    }

    /**
     * Test that only volunteers can apply to projects
     */
    public function test_only_volunteers_can_apply_to_projects()
    {
        $user = User::factory()->create([
            'name' => 'Mary Hong',
            'email' => 'mary@gmail.com',
            'password' => Hash::make('Password123!'),
            'role' => 'user' // Not a volunteer
        ]);

        $communityProject = CommunityProject::factory()->create([
            'status' => 'active'
        ]);

        $this->actingAs($user);

        // Try to apply to project
        $response = $this->post('/volunteer/projects/' . $communityProject->id . '/apply', [
            'experience' => 'I have experience in community service.',
            'motivation' => 'I want to help the community.'
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error', 'You must be an approved volunteer to apply for projects.');
    }

    /**
     * Test that users cannot apply to the same project twice
     */
    public function test_cannot_apply_to_same_project_twice()
    {
        $user = User::factory()->create([
            'name' => 'Mary Hong',
            'email' => 'mary@gmail.com',
            'password' => Hash::make('Password123!'),
            'role' => 'volunteer'
        ]);

        // Create approved volunteer application
        VolunteerApplication::factory()->create([
            'user_id' => $user->id,
            'status' => 'Approved'
        ]);

        $communityProject = CommunityProject::factory()->create([
            'status' => 'active'
        ]);

        // Create an existing application
        ProjectApplication::factory()->create([
            'user_id' => $user->id,
            'community_project_id' => $communityProject->id,
            'status' => 'pending'
        ]);

        $this->actingAs($user);

        // Try to apply again
        $response = $this->post('/volunteer/projects/' . $communityProject->id . '/apply', [
            'experience' => 'I have experience in community service.',
            'motivation' => 'I want to help the community.'
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error', 'You have already applied to this project.');
    }

    /**
     * Test that project volunteer count increases when application is approved
     */
    public function test_project_volunteer_count_increases_on_approval()
    {
        $user = User::factory()->create([
            'name' => 'Mary Hong',
            'email' => 'mary@gmail.com',
            'password' => Hash::make('Password123!'),
            'role' => 'volunteer'
        ]);

        // Create approved volunteer application
        VolunteerApplication::factory()->create([
            'user_id' => $user->id,
            'status' => 'Approved'
        ]);

        $admin = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('AdminPassword123!'),
            'role' => 'admin'
        ]);

        $communityProject = CommunityProject::factory()->create([
            'volunteers_needed' => 5,
            'volunteers_joined' => 0
        ]);

        $projectApplication = ProjectApplication::factory()->create([
            'user_id' => $user->id,
            'community_project_id' => $communityProject->id,
            'status' => 'pending'
        ]);

        $this->actingAs($admin);

        // Approve the application
        $response = $this->post('/admin/community-projects/applications/' . $projectApplication->id . '/approve');

        $response->assertJson(['success' => true]);

        // Verify volunteer count increased
        $communityProject->refresh();
        $this->assertEquals(1, $communityProject->volunteers_joined);
    }
}
