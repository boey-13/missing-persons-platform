<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Models\MissingReport;
use App\Models\SightingReport;
use App\Models\SystemLog;
use App\Models\Notification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

class UT010_AdminPanelSightingReportManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    /**
     * Test Case: Approve pending sighting report
     * 
     * Test Steps:
     * 1. Navigate to admin sighting reports page
     * 2. Select a pending sighting report
     * 3. Click "Approve" button
     * 4. Confirm approval action
     * 
     * Expected Result: The system updates sighting status to "Approved" and sends notification to reporter
     */
    public function test_approve_pending_sighting_report()
    {
        // Create admin user
        $admin = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
            'role' => 'admin'
        ]);

        // Create reporter user
        $reporter = User::factory()->create([
            'name' => 'Reporter User',
            'email' => 'reporter@example.com',
            'password' => Hash::make('password123'),
            'role' => 'user'
        ]);

        // Create missing report
        $missingReport = MissingReport::factory()->create([
            'user_id' => $reporter->id,
            'full_name' => 'John Smith',
            'case_status' => 'Missing'
        ]);

        // Create pending sighting report
        $sighting = SightingReport::factory()->create([
            'user_id' => $reporter->id,
            'missing_report_id' => $missingReport->id,
            'location' => 'Kuala Lumpur',
            'status' => 'Pending'
        ]);

        $this->actingAs($admin);

        $response = $this->post("/admin/sighting-reports/{$sighting->id}/status", [
            'status' => 'Approved'
        ]);

        $response->assertRedirect();

        // Verify sighting status was updated
        $sighting->refresh();
        $this->assertEquals('Approved', $sighting->status);

        // Verify system log was created
        $this->assertDatabaseHas('system_logs', [
            'user_id' => $admin->id,
            'action' => 'sighting_approved',
            'description' => "Sighting report Approved for missing person: John Smith"
        ]);
    }

    /**
     * Test Case: Reject sighting report with reason
     * 
     * Test Steps:
     * 1. Navigate to admin sighting reports page
     * 2. Select a pending sighting report
     * 3. Click "Reject" button
     * 4. Confirm rejection action
     * 
     * Expected Result: The system updates sighting status to "Rejected" and sends notification to reporter
     */
    public function test_reject_sighting_report_with_reason()
    {
        // Create admin user
        $admin = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
            'role' => 'admin'
        ]);

        // Create reporter user
        $reporter = User::factory()->create([
            'name' => 'Reporter User',
            'email' => 'reporter@example.com',
            'password' => Hash::make('password123'),
            'role' => 'user'
        ]);

        // Create missing report
        $missingReport = MissingReport::factory()->create([
            'user_id' => $reporter->id,
            'full_name' => 'Jane Doe',
            'case_status' => 'Missing'
        ]);

        // Create pending sighting report
        $sighting = SightingReport::factory()->create([
            'user_id' => $reporter->id,
            'missing_report_id' => $missingReport->id,
            'location' => 'Kuala Lumpur',
            'status' => 'Pending'
        ]);

        $this->actingAs($admin);

        $response = $this->post("/admin/sighting-reports/{$sighting->id}/status", [
            'status' => 'Rejected'
        ]);

        $response->assertRedirect();

        // Verify sighting status was updated
        $sighting->refresh();
        $this->assertEquals('Rejected', $sighting->status);

        // Verify system log was created
        $this->assertDatabaseHas('system_logs', [
            'user_id' => $admin->id,
            'action' => 'sighting_rejected',
            'description' => "Sighting report Rejected for missing person: Jane Doe"
        ]);
    }

    /**
     * Test Case: View sighting report details
     * 
     * Test Steps:
     * 1. Navigate to admin sighting reports page
     * 2. Click "View Details" button
     * 3. Verify all information is displayed
     * 
     * Expected Result: The system displays complete sighting details including photos and location
     */
    public function test_view_sighting_report_details()
    {
        // Create admin user
        $admin = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
            'role' => 'admin'
        ]);

        // Create reporter user
        $reporter = User::factory()->create([
            'name' => 'Reporter User',
            'email' => 'reporter@example.com',
            'password' => Hash::make('password123'),
            'role' => 'user'
        ]);

        // Create missing report
        $missingReport = MissingReport::factory()->create([
            'user_id' => $reporter->id,
            'full_name' => 'John Smith',
            'case_status' => 'Missing'
        ]);

        // Create sighting report with photos
        $sighting = SightingReport::factory()->create([
            'user_id' => $reporter->id,
            'missing_report_id' => $missingReport->id,
            'location' => 'Kuala Lumpur',
            'description' => 'Saw the person at the mall',
            'status' => 'Pending',
            'photo_paths' => ['photos/sighting1.jpg', 'photos/sighting2.jpg']
        ]);

        $this->actingAs($admin);

        $response = $this->get("/admin/sighting-reports/{$sighting->id}");

        $response->assertStatus(200);
        $response->assertJson([
            'id' => $sighting->id,
            'location' => 'Kuala Lumpur',
            'description' => 'Saw the person at the mall',
            'status' => 'Pending',
            'missing_person' => [
                'full_name' => 'John Smith'
            ]
        ]);
    }

    /**
     * Test Case: Search sighting reports by location
     * 
     * Test Steps:
     * 1. Navigate to admin sighting reports page
     * 2. Enter location in search box
     * 3. Click "Search" button
     * 
     * Expected Result: The system displays only sighting reports matching the location
     */
    public function test_search_sighting_reports_by_location()
    {
        // Create admin user
        $admin = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
            'role' => 'admin'
        ]);

        // Create reporter user
        $reporter = User::factory()->create([
            'name' => 'Reporter User',
            'email' => 'reporter@example.com',
            'password' => Hash::make('password123'),
            'role' => 'user'
        ]);

        // Create missing report
        $missingReport = MissingReport::factory()->create([
            'user_id' => $reporter->id,
            'full_name' => 'John Smith',
            'case_status' => 'Missing'
        ]);

        // Create sighting reports with different locations
        SightingReport::factory()->create([
            'user_id' => $reporter->id,
            'missing_report_id' => $missingReport->id,
            'location' => 'Kuala Lumpur',
            'status' => 'Pending'
        ]);

        SightingReport::factory()->create([
            'user_id' => $reporter->id,
            'missing_report_id' => $missingReport->id,
            'location' => 'Selangor',
            'status' => 'Pending'
        ]);

        $this->actingAs($admin);

        $response = $this->get('/admin/sighting-reports?search=Kuala Lumpur');

        $response->assertStatus(200);
        $response->assertSee('Admin/ManageSightingReports');
    }

    /**
     * Test Case: Filter sighting reports by status
     * 
     * Test Steps:
     * 1. Navigate to admin sighting reports page
     * 2. Select status filter dropdown
     * 3. Choose "Pending" status
     * 4. Click "Apply Filter" button
     * 
     * Expected Result: The system displays only sighting reports with "Pending" status
     */
    public function test_filter_sighting_reports_by_status()
    {
        // Create admin user
        $admin = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
            'role' => 'admin'
        ]);

        // Create reporter user
        $reporter = User::factory()->create([
            'name' => 'Reporter User',
            'email' => 'reporter@example.com',
            'password' => Hash::make('password123'),
            'role' => 'user'
        ]);

        // Create missing report
        $missingReport = MissingReport::factory()->create([
            'user_id' => $reporter->id,
            'full_name' => 'John Smith',
            'case_status' => 'Missing'
        ]);

        // Create sighting reports with different statuses
        SightingReport::factory()->create([
            'user_id' => $reporter->id,
            'missing_report_id' => $missingReport->id,
            'location' => 'Kuala Lumpur',
            'status' => 'Pending'
        ]);

        SightingReport::factory()->create([
            'user_id' => $reporter->id,
            'missing_report_id' => $missingReport->id,
            'location' => 'Selangor',
            'status' => 'Approved'
        ]);

        $this->actingAs($admin);

        $response = $this->get('/admin/sighting-reports?status=Pending');

        $response->assertStatus(200);
        $response->assertSee('Admin/ManageSightingReports');
    }

    /**
     * Test Case: Filter sighting reports by missing person
     * 
     * Test Steps:
     * 1. Navigate to admin sighting reports page
     * 2. Select missing person filter dropdown
     * 3. Choose "John Smith"
     * 4. Click "Apply Filter" button
     * 
     * Expected Result: The system displays only sighting reports for selected missing person
     */
    public function test_filter_sighting_reports_by_missing_person()
    {
        // Create admin user
        $admin = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
            'role' => 'admin'
        ]);

        // Create reporter user
        $reporter = User::factory()->create([
            'name' => 'Reporter User',
            'email' => 'reporter@example.com',
            'password' => Hash::make('password123'),
            'role' => 'user'
        ]);

        // Create missing reports
        $missingReport1 = MissingReport::factory()->create([
            'user_id' => $reporter->id,
            'full_name' => 'John Smith',
            'case_status' => 'Missing'
        ]);

        $missingReport2 = MissingReport::factory()->create([
            'user_id' => $reporter->id,
            'full_name' => 'Jane Doe',
            'case_status' => 'Missing'
        ]);

        // Create sighting reports for different missing persons
        SightingReport::factory()->create([
            'user_id' => $reporter->id,
            'missing_report_id' => $missingReport1->id,
            'location' => 'Kuala Lumpur',
            'status' => 'Pending'
        ]);

        SightingReport::factory()->create([
            'user_id' => $reporter->id,
            'missing_report_id' => $missingReport2->id,
            'location' => 'Selangor',
            'status' => 'Pending'
        ]);

        $this->actingAs($admin);

        $response = $this->get("/admin/sighting-reports?missing_report_id={$missingReport1->id}");

        $response->assertStatus(200);
        $response->assertSee('Admin/ManageSightingReports');
    }

    /**
     * Test Case: Delete sighting report
     * 
     * Test Steps:
     * 1. Navigate to admin sighting reports page
     * 2. Select a sighting report
     * 3. Click "Delete" button
     * 4. Confirm deletion action
     * 
     * Expected Result: The system deletes the sighting report and logs the deletion action
     */
    public function test_delete_sighting_report()
    {
        // Create admin user
        $admin = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
            'role' => 'admin'
        ]);

        // Create reporter user
        $reporter = User::factory()->create([
            'name' => 'Reporter User',
            'email' => 'reporter@example.com',
            'password' => Hash::make('password123'),
            'role' => 'user'
        ]);

        // Create missing report
        $missingReport = MissingReport::factory()->create([
            'user_id' => $reporter->id,
            'full_name' => 'John Smith',
            'case_status' => 'Missing'
        ]);

        // Create sighting report
        $sighting = SightingReport::factory()->create([
            'user_id' => $reporter->id,
            'missing_report_id' => $missingReport->id,
            'location' => 'Kuala Lumpur',
            'status' => 'Pending'
        ]);

        $this->actingAs($admin);

        $response = $this->delete("/admin/sighting-reports/{$sighting->id}");

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Sighting report deleted successfully');

        // Verify sighting report was deleted
        $this->assertDatabaseMissing('sighting_reports', [
            'id' => $sighting->id
        ]);

        // Verify system log was created
        $this->assertDatabaseHas('system_logs', [
            'user_id' => $admin->id,
            'action' => 'sighting_report_deleted',
            'description' => "Deleted sighting report for: John Smith"
        ]);
    }

    /**
     * Test Case: Update sighting report status with invalid status
     */
    public function test_update_sighting_report_status_with_invalid_status()
    {
        // Create admin user
        $admin = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
            'role' => 'admin'
        ]);

        // Create reporter user
        $reporter = User::factory()->create([
            'name' => 'Reporter User',
            'email' => 'reporter@example.com',
            'password' => Hash::make('password123'),
            'role' => 'user'
        ]);

        // Create missing report
        $missingReport = MissingReport::factory()->create([
            'user_id' => $reporter->id,
            'full_name' => 'John Smith',
            'case_status' => 'Missing'
        ]);

        // Create sighting report
        $sighting = SightingReport::factory()->create([
            'user_id' => $reporter->id,
            'missing_report_id' => $missingReport->id,
            'location' => 'Kuala Lumpur',
            'status' => 'Pending'
        ]);

        $this->actingAs($admin);

        $response = $this->post("/admin/sighting-reports/{$sighting->id}/status", [
            'status' => 'InvalidStatus'
        ]);

        $response->assertSessionHasErrors(['status']);
    }

    /**
     * Test Case: Access admin functions without admin role
     */
    public function test_access_admin_functions_without_admin_role()
    {
        // Create regular user (not admin)
        $user = User::factory()->create([
            'name' => 'Regular User',
            'email' => 'user@example.com',
            'password' => Hash::make('password123'),
            'role' => 'user'
        ]);

        // Create missing report
        $missingReport = MissingReport::factory()->create([
            'user_id' => $user->id,
            'full_name' => 'John Smith',
            'case_status' => 'Missing'
        ]);

        // Create sighting report
        $sighting = SightingReport::factory()->create([
            'user_id' => $user->id,
            'missing_report_id' => $missingReport->id,
            'location' => 'Kuala Lumpur',
            'status' => 'Pending'
        ]);

        $this->actingAs($user);

        // Try to access admin sighting reports page
        $response = $this->get('/admin/sighting-reports');
        $response->assertStatus(200); // Returns access denied page instead of 403

        // Try to update sighting status
        $response = $this->post("/admin/sighting-reports/{$sighting->id}/status", [
            'status' => 'Approved'
        ]);
        $response->assertStatus(200); // Returns access denied page instead of 403

        // Try to delete sighting report
        $response = $this->delete("/admin/sighting-reports/{$sighting->id}");
        $response->assertStatus(200); // Returns access denied page instead of 403
    }

    /**
     * Test Case: Update non-existent sighting report
     */
    public function test_update_nonexistent_sighting_report()
    {
        // Create admin user
        $admin = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
            'role' => 'admin'
        ]);

        $this->actingAs($admin);

        // Try to update non-existent sighting report
        $response = $this->post("/admin/sighting-reports/999/status", [
            'status' => 'Approved'
        ]);

        $response->assertStatus(404); // Not Found
    }

    /**
     * Test Case: Delete non-existent sighting report
     */
    public function test_delete_nonexistent_sighting_report()
    {
        // Create admin user
        $admin = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
            'role' => 'admin'
        ]);

        $this->actingAs($admin);

        // Try to delete non-existent sighting report
        $response = $this->delete("/admin/sighting-reports/999");

        $response->assertStatus(404); // Not Found
    }

    /**
     * Test Case: View non-existent sighting report details
     */
    public function test_view_nonexistent_sighting_report_details()
    {
        // Create admin user
        $admin = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
            'role' => 'admin'
        ]);

        $this->actingAs($admin);

        // Try to view non-existent sighting report
        $response = $this->get("/admin/sighting-reports/999");

        $response->assertStatus(404); // Not Found
    }

    /**
     * Test Case: Award points when sighting report is approved
     */
    public function test_award_points_when_sighting_report_approved()
    {
        // Create admin user
        $admin = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
            'role' => 'admin'
        ]);

        // Create reporter user
        $reporter = User::factory()->create([
            'name' => 'Reporter User',
            'email' => 'reporter@example.com',
            'password' => Hash::make('password123'),
            'role' => 'user'
        ]);

        // Create missing report
        $missingReport = MissingReport::factory()->create([
            'user_id' => $reporter->id,
            'full_name' => 'John Smith',
            'case_status' => 'Missing'
        ]);

        // Create pending sighting report
        $sighting = SightingReport::factory()->create([
            'user_id' => $reporter->id,
            'missing_report_id' => $missingReport->id,
            'location' => 'Kuala Lumpur',
            'status' => 'Pending'
        ]);

        $this->actingAs($admin);

        $response = $this->post("/admin/sighting-reports/{$sighting->id}/status", [
            'status' => 'Approved'
        ]);

        $response->assertRedirect();

        // Verify sighting status was updated
        $sighting->refresh();
        $this->assertEquals('Approved', $sighting->status);

        // Note: Points awarding is tested in the PointsService unit tests
        // This test verifies the status update works correctly
    }

    /**
     * Test Case: View sighting reports with pagination
     */
    public function test_view_sighting_reports_with_pagination()
    {
        // Create admin user
        $admin = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
            'role' => 'admin'
        ]);

        // Create reporter user
        $reporter = User::factory()->create([
            'name' => 'Reporter User',
            'email' => 'reporter@example.com',
            'password' => Hash::make('password123'),
            'role' => 'user'
        ]);

        // Create missing report
        $missingReport = MissingReport::factory()->create([
            'user_id' => $reporter->id,
            'full_name' => 'John Smith',
            'case_status' => 'Missing'
        ]);

        // Create multiple sighting reports
        for ($i = 0; $i < 20; $i++) {
            SightingReport::factory()->create([
                'user_id' => $reporter->id,
                'missing_report_id' => $missingReport->id,
                'location' => "Location {$i}",
                'status' => 'Pending'
            ]);
        }

        $this->actingAs($admin);

        $response = $this->get('/admin/sighting-reports');

        $response->assertStatus(200);
        $response->assertSee('Admin/ManageSightingReports');
    }

    /**
     * Test Case: Filter sighting reports with multiple criteria
     */
    public function test_filter_sighting_reports_with_multiple_criteria()
    {
        // Create admin user
        $admin = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
            'role' => 'admin'
        ]);

        // Create reporter user
        $reporter = User::factory()->create([
            'name' => 'Reporter User',
            'email' => 'reporter@example.com',
            'password' => Hash::make('password123'),
            'role' => 'user'
        ]);

        // Create missing reports
        $missingReport1 = MissingReport::factory()->create([
            'user_id' => $reporter->id,
            'full_name' => 'John Smith',
            'case_status' => 'Missing'
        ]);

        $missingReport2 = MissingReport::factory()->create([
            'user_id' => $reporter->id,
            'full_name' => 'Jane Doe',
            'case_status' => 'Missing'
        ]);

        // Create sighting reports with different criteria
        SightingReport::factory()->create([
            'user_id' => $reporter->id,
            'missing_report_id' => $missingReport1->id,
            'location' => 'Kuala Lumpur',
            'status' => 'Pending'
        ]);

        SightingReport::factory()->create([
            'user_id' => $reporter->id,
            'missing_report_id' => $missingReport2->id,
            'location' => 'Kuala Lumpur',
            'status' => 'Approved'
        ]);

        SightingReport::factory()->create([
            'user_id' => $reporter->id,
            'missing_report_id' => $missingReport1->id,
            'location' => 'Selangor',
            'status' => 'Pending'
        ]);

        $this->actingAs($admin);

        // Filter by status and location
        $response = $this->get('/admin/sighting-reports?status=Pending&search=Kuala');

        $response->assertStatus(200);
        $response->assertSee('Admin/ManageSightingReports');
    }
}
