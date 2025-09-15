<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Models\Notification;
use App\Models\MissingReport;
use App\Models\SightingReport;
use App\Models\VolunteerApplication;
use App\Models\CommunityProject;
use App\Models\ProjectApplication;
use App\Models\ContactMessage;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

class UT018_NotificationSystemTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test Case: View notification bell
     * 
     * Test Steps:
     * 1. Navigate to any page
     * 2. Look for notification bell icon
     * 3. Check if bell displays unread count
     * 
     * Expected Result: Notification bell is visible with correct unread count
     */
    public function test_view_notification_bell()
    {
        // Create user with notifications
        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
            'role' => 'user'
        ]);

        // Create some notifications
        Notification::factory()->create([
            'user_id' => $user->id,
            'type' => 'welcome',
            'title' => 'Welcome',
            'message' => 'Welcome to the platform',
            'read_at' => null
        ]);

        Notification::factory()->create([
            'user_id' => $user->id,
            'type' => 'points_earned',
            'title' => 'Points Earned',
            'message' => 'You earned 10 points',
            'read_at' => now()
        ]);

        $this->actingAs($user);

        $response = $this->get('/notifications');

        $response->assertStatus(200);
        $data = $response->json();
        $this->assertIsArray($data);
    }

    /**
     * Test Case: View missing report submitted notification
     * 
     * Test Steps:
     * 1. Submit missing person report
     * 2. Check notification bell
     * 3. View notification details
     * 
     * Expected Result: System displays "Missing Person Report Submitted" notification
     */
    public function test_view_missing_report_submitted_notification()
    {
        // Create user
        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
            'role' => 'user'
        ]);

        // Create missing report
        $report = MissingReport::factory()->create([
            'user_id' => $user->id,
            'full_name' => 'John Smith',
            'case_status' => 'Pending'
        ]);

        // Send notification
        NotificationService::missingReportSubmitted($report);

        $this->actingAs($user);

        $response = $this->get('/notifications');

        $response->assertStatus(200);
        $data = $response->json();
        
        $this->assertCount(1, $data);
        $this->assertEquals('missing_report_submitted', $data[0]['type']);
        $this->assertEquals('Missing Person Report Submitted', $data[0]['title']);
        $this->assertStringContainsString('John Smith', $data[0]['message']);
    }

    /**
     * Test Case: View missing report approved notification
     * 
     * Test Steps:
     * 1. Admin approves missing report
     * 2. Check notification bell
     * 3. View notification details
     * 
     * Expected Result: System displays "Missing Person Report Approved" notification
     */
    public function test_view_missing_report_approved_notification()
    {
        // Create user
        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
            'role' => 'user'
        ]);

        // Create missing report
        $report = MissingReport::factory()->create([
            'user_id' => $user->id,
            'full_name' => 'John Smith',
            'case_status' => 'Pending'
        ]);

        // Send notification
        NotificationService::missingReportApproved($report);

        $this->actingAs($user);

        $response = $this->get('/notifications');

        $response->assertStatus(200);
        $data = $response->json();
        
        $this->assertCount(1, $data);
        $this->assertEquals('missing_report_approved', $data[0]['type']);
        $this->assertEquals('Missing Person Report Approved', $data[0]['title']);
        $this->assertStringContainsString('John Smith', $data[0]['message']);
    }

    /**
     * Test Case: View missing report rejected notification
     * 
     * Test Steps:
     * 1. Admin rejects missing report
     * 2. Check notification bell
     * 3. View notification details
     * 
     * Expected Result: System displays "Missing Person Report Rejected" notification with reason
     */
    public function test_view_missing_report_rejected_notification()
    {
        // Create user
        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
            'role' => 'user'
        ]);

        // Create missing report
        $report = MissingReport::factory()->create([
            'user_id' => $user->id,
            'full_name' => 'John Smith',
            'case_status' => 'Pending'
        ]);

        // Send notification
        NotificationService::missingReportRejected($report, 'Incomplete information provided');

        $this->actingAs($user);

        $response = $this->get('/notifications');

        $response->assertStatus(200);
        $data = $response->json();
        
        $this->assertCount(1, $data);
        $this->assertEquals('missing_report_rejected', $data[0]['type']);
        $this->assertEquals('Missing Person Report Rejected', $data[0]['title']);
        $this->assertStringContainsString('John Smith', $data[0]['message']);
        $this->assertEquals('Incomplete information provided', $data[0]['data']['reason']);
    }

    /**
     * Test Case: View sighting report submitted notification
     * 
     * Test Steps:
     * 1. Submit sighting report
     * 2. Check notification bell
     * 3. View notification details
     * 
     * Expected Result: System displays "Sighting Report Submitted" notification
     */
    public function test_view_sighting_report_submitted_notification()
    {
        // Create user
        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
            'role' => 'user'
        ]);

        // Create missing report
        $missingReport = MissingReport::factory()->create([
            'user_id' => $user->id,
            'full_name' => 'John Smith',
            'case_status' => 'Approved'
        ]);

        // Create sighting report
        $sighting = SightingReport::factory()->create([
            'user_id' => $user->id,
            'missing_report_id' => $missingReport->id,
            'location' => 'Kuala Lumpur'
        ]);

        // Send notification
        NotificationService::sightingReportSubmitted($sighting);

        $this->actingAs($user);

        $response = $this->get('/notifications');

        $response->assertStatus(200);
        $data = $response->json();
        
        $this->assertCount(1, $data);
        $this->assertEquals('sighting_report_submitted', $data[0]['type']);
        $this->assertEquals('Sighting Report Submitted', $data[0]['title']);
        $this->assertStringContainsString('Thank you for helping', $data[0]['message']);
    }

    /**
     * Test Case: View points earned notification
     * 
     * Test Steps:
     * 1. Perform action that earns points
     * 2. Check notification bell
     * 3. View notification details
     * 
     * Expected Result: System displays "Points Earned" notification with points amount
     */
    public function test_view_points_earned_notification()
    {
        // Create user
        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
            'role' => 'user'
        ]);

        // Send notification
        NotificationService::send(
            $user->id,
            'points_earned',
            'Points Earned',
            'You earned 50 points for submitting a missing person report.',
            ['points' => 50, 'action' => 'view_points']
        );

        $this->actingAs($user);

        $response = $this->get('/notifications');

        $response->assertStatus(200);
        $data = $response->json();
        
        $this->assertCount(1, $data);
        $this->assertEquals('points_earned', $data[0]['type']);
        $this->assertEquals('Points Earned', $data[0]['title']);
        $this->assertStringContainsString('50 points', $data[0]['message']);
        $this->assertEquals(50, $data[0]['data']['points']);
    }

    /**
     * Test Case: View volunteer application approved notification
     * 
     * Test Steps:
     * 1. Admin approves volunteer application
     * 2. Check notification bell
     * 3. View notification details
     * 
     * Expected Result: System displays "Volunteer Application Approved" notification
     */
    public function test_view_volunteer_application_approved_notification()
    {
        // Create user
        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
            'role' => 'user'
        ]);

        // Create volunteer application
        $application = VolunteerApplication::factory()->create([
            'user_id' => $user->id,
            'status' => 'Approved'
        ]);

        // Send notification
        NotificationService::volunteerApplicationApproved($application);

        $this->actingAs($user);

        $response = $this->get('/notifications');

        $response->assertStatus(200);
        $data = $response->json();
        
        $this->assertCount(1, $data);
        $this->assertEquals('volunteer_application_approved', $data[0]['type']);
        $this->assertEquals('Volunteer Application Approved', $data[0]['title']);
        $this->assertStringContainsString('Congratulations', $data[0]['message']);
    }

    /**
     * Test Case: View volunteer application rejected notification
     * 
     * Test Steps:
     * 1. Admin rejects volunteer application
     * 2. Check notification bell
     * 3. View notification details
     * 
     * Expected Result: System displays "Volunteer Application Rejected" notification with reason
     */
    public function test_view_volunteer_application_rejected_notification()
    {
        // Create user
        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
            'role' => 'user'
        ]);

        // Create volunteer application
        $application = VolunteerApplication::factory()->create([
            'user_id' => $user->id,
            'status' => 'Rejected'
        ]);

        // Send notification
        NotificationService::volunteerApplicationRejected($application, 'Insufficient experience');

        $this->actingAs($user);

        $response = $this->get('/notifications');

        $response->assertStatus(200);
        $data = $response->json();
        
        $this->assertCount(1, $data);
        $this->assertEquals('volunteer_application_rejected', $data[0]['type']);
        $this->assertEquals('Volunteer Application Rejected', $data[0]['title']);
        $this->assertStringContainsString('rejected', $data[0]['message']);
        $this->assertEquals('Insufficient experience', $data[0]['data']['reason']);
    }

    /**
     * Test Case: View community project application approved notification
     * 
     * Test Steps:
     * 1. Admin approves project application
     * 2. Check notification bell
     * 3. View notification details
     * 
     * Expected Result: System displays "Project Application Approved" notification
     */
    public function test_view_community_project_application_approved_notification()
    {
        // Create user
        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
            'role' => 'volunteer'
        ]);

        // Create community project
        $project = CommunityProject::factory()->create([
            'title' => 'Search Mission KLCC',
            'location' => 'Kuala Lumpur'
        ]);

        // Create project application
        $application = ProjectApplication::factory()->create([
            'user_id' => $user->id,
            'community_project_id' => $project->id,
            'status' => 'approved'
        ]);

        // Send notification
        NotificationService::projectApplicationApproved($application);

        $this->actingAs($user);

        $response = $this->get('/notifications');

        $response->assertStatus(200);
        $data = $response->json();
        
        $this->assertCount(1, $data);
        $this->assertEquals('project_application_approved', $data[0]['type']);
        $this->assertEquals('Project Application Approved', $data[0]['title']);
        $this->assertStringContainsString('Search Mission KLCC', $data[0]['message']);
    }

    /**
     * Test Case: View community project application rejected notification
     * 
     * Test Steps:
     * 1. Admin rejects project application
     * 2. Check notification bell
     * 3. View notification details
     * 
     * Expected Result: System displays "Project Application Rejected" notification with reason
     */
    public function test_view_community_project_application_rejected_notification()
    {
        // Create user
        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
            'role' => 'volunteer'
        ]);

        // Create community project
        $project = CommunityProject::factory()->create([
            'title' => 'Search Mission KLCC',
            'location' => 'Kuala Lumpur'
        ]);

        // Create project application
        $application = ProjectApplication::factory()->create([
            'user_id' => $user->id,
            'community_project_id' => $project->id,
            'status' => 'rejected'
        ]);

        // Send notification
        NotificationService::projectApplicationRejected($application, 'Project is full');

        $this->actingAs($user);

        $response = $this->get('/notifications');

        $response->assertStatus(200);
        $data = $response->json();
        
        $this->assertCount(1, $data);
        $this->assertEquals('project_application_rejected', $data[0]['type']);
        $this->assertEquals('Project Application Rejected', $data[0]['title']);
        $this->assertStringContainsString('Search Mission KLCC', $data[0]['message']);
        $this->assertEquals('Project is full', $data[0]['data']['reason']);
    }

    /**
     * Test Case: View welcome notification for new user
     * 
     * Test Steps:
     * 1. Complete user registration
     * 2. Check notification bell
     * 3. View notification details
     * 
     * Expected Result: System displays "Welcome to Missing Persons Platform" notification
     */
    public function test_view_welcome_notification_for_new_user()
    {
        // Create user
        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
            'role' => 'user'
        ]);

        // Send welcome notification
        NotificationService::send(
            $user->id,
            'welcome',
            'Welcome to Missing Persons Platform',
            'Welcome to our platform! Thank you for joining our community.',
            ['action' => 'explore_platform']
        );

        $this->actingAs($user);

        $response = $this->get('/notifications');

        $response->assertStatus(200);
        $data = $response->json();
        
        $this->assertCount(1, $data);
        $this->assertEquals('welcome', $data[0]['type']);
        $this->assertEquals('Welcome to Missing Persons Platform', $data[0]['title']);
        $this->assertStringContainsString('Welcome to our platform', $data[0]['message']);
    }

    /**
     * Test Case: View social share bonus notification
     * 
     * Test Steps:
     * 1. Share missing person case on social media
     * 2. Check notification bell
     * 3. View notification details
     * 
     * Expected Result: System displays "Social Share Bonus" notification with 5 points
     */
    public function test_view_social_share_bonus_notification()
    {
        // Create user
        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
            'role' => 'user'
        ]);

        // Send social share notification
        NotificationService::send(
            $user->id,
            'social_share_bonus',
            'Social Share Bonus',
            'You earned 5 points for sharing a missing person case on social media.',
            ['points' => 5, 'action' => 'view_points']
        );

        $this->actingAs($user);

        $response = $this->get('/notifications');

        $response->assertStatus(200);
        $data = $response->json();
        
        $this->assertCount(1, $data);
        $this->assertEquals('social_share_bonus', $data[0]['type']);
        $this->assertEquals('Social Share Bonus', $data[0]['title']);
        $this->assertStringContainsString('5 points', $data[0]['message']);
        $this->assertEquals(5, $data[0]['data']['points']);
    }

    /**
     * Test Case: View admin notification for new missing report
     * 
     * Test Steps:
     * 1. New missing report is submitted
     * 2. Check notification bell
     * 3. View notification details
     * 
     * Expected Result: System displays "New Missing Person Report" notification for admin
     */
    public function test_view_admin_notification_for_new_missing_report()
    {
        // Create admin user
        $admin = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
            'role' => 'admin'
        ]);

        // Create missing report
        $report = MissingReport::factory()->create([
            'full_name' => 'John Smith',
            'case_status' => 'Pending'
        ]);

        // Send admin notification
        NotificationService::send(
            $admin->id,
            'new_missing_report',
            'New Missing Person Report',
            "A new missing person report for John Smith has been submitted and requires review.",
            ['action' => 'review_report', 'report_id' => $report->id]
        );

        $this->actingAs($admin);

        $response = $this->get('/notifications');

        $response->assertStatus(200);
        $data = $response->json();
        
        $this->assertCount(1, $data);
        $this->assertEquals('new_missing_report', $data[0]['type']);
        $this->assertEquals('New Missing Person Report', $data[0]['title']);
        $this->assertStringContainsString('John Smith', $data[0]['message']);
    }

    /**
     * Test Case: View admin notification for new sighting report
     * 
     * Test Steps:
     * 1. New sighting report is submitted
     * 2. Check notification bell
     * 3. View notification details
     * 
     * Expected Result: System displays "New Sighting Report" notification for admin
     */
    public function test_view_admin_notification_for_new_sighting_report()
    {
        // Create admin user
        $admin = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
            'role' => 'admin'
        ]);

        // Create sighting report
        $sighting = SightingReport::factory()->create([
            'location' => 'Kuala Lumpur'
        ]);

        // Send admin notification
        NotificationService::send(
            $admin->id,
            'new_sighting_report',
            'New Sighting Report',
            "A new sighting report has been submitted for review.",
            ['action' => 'review_sighting', 'sighting_id' => $sighting->id]
        );

        $this->actingAs($admin);

        $response = $this->get('/notifications');

        $response->assertStatus(200);
        $data = $response->json();
        
        $this->assertCount(1, $data);
        $this->assertEquals('new_sighting_report', $data[0]['type']);
        $this->assertEquals('New Sighting Report', $data[0]['title']);
        $this->assertStringContainsString('sighting report', $data[0]['message']);
    }

    /**
     * Test Case: View admin notification for new contact message
     * 
     * Test Steps:
     * 1. New contact message is submitted
     * 2. Check notification bell
     * 3. View notification details
     * 
     * Expected Result: System displays "New Contact Message" notification for admin
     */
    public function test_view_admin_notification_for_new_contact_message()
    {
        // Create admin user
        $admin = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
            'role' => 'admin'
        ]);

        // Create contact message
        $message = ContactMessage::factory()->create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'subject' => 'Help Request'
        ]);

        // Send admin notification
        NotificationService::send(
            $admin->id,
            'new_contact_message',
            'New Contact Message',
            "A new contact message from John Doe has been received.",
            ['action' => 'view_message', 'message_id' => $message->id]
        );

        $this->actingAs($admin);

        $response = $this->get('/notifications');

        $response->assertStatus(200);
        $data = $response->json();
        
        $this->assertCount(1, $data);
        $this->assertEquals('new_contact_message', $data[0]['type']);
        $this->assertEquals('New Contact Message', $data[0]['title']);
        $this->assertStringContainsString('John Doe', $data[0]['message']);
    }

    /**
     * Test Case: Mark notifications as read
     */
    public function test_mark_notifications_as_read()
    {
        // Create user
        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
            'role' => 'user'
        ]);

        // Create notifications
        $notification1 = Notification::factory()->create([
            'user_id' => $user->id,
            'type' => 'welcome',
            'title' => 'Welcome',
            'message' => 'Welcome to the platform',
            'read_at' => null
        ]);

        $notification2 = Notification::factory()->create([
            'user_id' => $user->id,
            'type' => 'points_earned',
            'title' => 'Points Earned',
            'message' => 'You earned 10 points',
            'read_at' => null
        ]);

        $this->actingAs($user);

        // Mark notifications as read
        $response = $this->post('/notifications/read', [
            'ids' => [$notification1->id, $notification2->id]
        ]);

        $response->assertStatus(200);
        $data = $response->json();
        $this->assertTrue($data['ok']);

        // Verify notifications are marked as read
        $this->assertDatabaseHas('notifications', [
            'id' => $notification1->id,
            'read_at' => now()
        ]);

        $this->assertDatabaseHas('notifications', [
            'id' => $notification2->id,
            'read_at' => now()
        ]);
    }

    /**
     * Test Case: Non-admin users should not see admin notifications
     */
    public function test_non_admin_users_should_not_see_admin_notifications()
    {
        // Create regular user
        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
            'role' => 'user'
        ]);

        // Create admin notification
        Notification::factory()->create([
            'user_id' => $user->id,
            'type' => 'new_missing_report',
            'title' => 'New Missing Person Report',
            'message' => 'A new report needs review'
        ]);

        // Create regular notification
        Notification::factory()->create([
            'user_id' => $user->id,
            'type' => 'welcome',
            'title' => 'Welcome',
            'message' => 'Welcome to the platform'
        ]);

        $this->actingAs($user);

        $response = $this->get('/notifications');

        $response->assertStatus(200);
        $data = $response->json();
        
        // Should only see the welcome notification, not the admin notification
        $this->assertCount(1, $data);
        $this->assertEquals('welcome', $data[0]['type']);
    }

    /**
     * Test Case: Admin users should see all notifications
     */
    public function test_admin_users_should_see_all_notifications()
    {
        // Create admin user
        $admin = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
            'role' => 'admin'
        ]);

        // Create admin notification
        Notification::factory()->create([
            'user_id' => $admin->id,
            'type' => 'new_missing_report',
            'title' => 'New Missing Person Report',
            'message' => 'A new report needs review'
        ]);

        // Create regular notification
        Notification::factory()->create([
            'user_id' => $admin->id,
            'type' => 'welcome',
            'title' => 'Welcome',
            'message' => 'Welcome to the platform'
        ]);

        $this->actingAs($admin);

        $response = $this->get('/notifications');

        $response->assertStatus(200);
        $data = $response->json();
        
        // Should see both notifications
        $this->assertCount(2, $data);
    }

    /**
     * Test Case: Notifications are ordered by creation date (newest first)
     */
    public function test_notifications_are_ordered_by_creation_date()
    {
        // Create user
        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
            'role' => 'user'
        ]);

        // Create notifications with different timestamps
        $oldNotification = Notification::factory()->create([
            'user_id' => $user->id,
            'type' => 'welcome',
            'title' => 'Welcome',
            'message' => 'Welcome to the platform',
            'created_at' => now()->subMinutes(10)
        ]);

        $newNotification = Notification::factory()->create([
            'user_id' => $user->id,
            'type' => 'points_earned',
            'title' => 'Points Earned',
            'message' => 'You earned 10 points',
            'created_at' => now()
        ]);

        $this->actingAs($user);

        $response = $this->get('/notifications');

        $response->assertStatus(200);
        $data = $response->json();
        
        // Should be ordered by creation date (newest first)
        $this->assertCount(2, $data);
        $this->assertEquals('points_earned', $data[0]['type']);
        $this->assertEquals('welcome', $data[1]['type']);
    }

    /**
     * Test Case: Notifications are limited to 10 per request
     */
    public function test_notifications_are_limited_to_10_per_request()
    {
        // Create user
        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
            'role' => 'user'
        ]);

        // Create 15 notifications
        for ($i = 1; $i <= 15; $i++) {
            Notification::factory()->create([
                'user_id' => $user->id,
                'type' => 'welcome',
                'title' => "Welcome $i",
                'message' => "Welcome message $i"
            ]);
        }

        $this->actingAs($user);

        $response = $this->get('/notifications');

        $response->assertStatus(200);
        $data = $response->json();
        
        // Should be limited to 10 notifications
        $this->assertCount(10, $data);
    }

    /**
     * Test Case: Unauthenticated users get empty notifications
     */
    public function test_unauthenticated_users_get_empty_notifications()
    {
        $response = $this->get('/notifications');

        $response->assertStatus(302); // Redirects to login
    }
}
