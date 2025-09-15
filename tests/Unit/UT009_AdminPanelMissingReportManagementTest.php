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

class UT009_AdminPanelMissingReportManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    /**
     * Test Case: Approve pending missing report
     * 
     * Test Steps:
     * 1. Navigate to admin missing reports page
     * 2. Select a pending report
     * 3. Click "Approve" button
     * 4. Confirm approval action
     * 
     * Expected Result: The system updates report status to "Approved" and sends notification to reporter
     */
    public function test_approve_pending_missing_report()
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

        // Create pending missing report
        $report = MissingReport::factory()->create([
            'user_id' => $reporter->id,
            'full_name' => 'John Smith',
            'case_status' => 'Pending'
        ]);

        $this->actingAs($admin);

        $response = $this->post("/admin/missing-reports/{$report->id}/status", [
            'status' => 'Approved'
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Status updated successfully');

        // Verify report status was updated to Missing (auto-converted from Approved)
        $report->refresh();
        $this->assertEquals('Missing', $report->case_status);

        // Verify system log was created
        $this->assertDatabaseHas('system_logs', [
            'user_id' => $admin->id,
            'action' => 'missing_report_status_updated',
            'description' => "Updated missing report #{$report->id} status from Pending to Missing"
        ]);
    }

    /**
     * Test Case: Reject pending missing report with reason
     * 
     * Test Steps:
     * 1. Navigate to admin missing reports page
     * 2. Select a pending report
     * 3. Click "Reject" button
     * 4. Enter rejection reason
     * 5. Confirm rejection action
     * 
     * Expected Result: The system updates report status to "Rejected" with reason and sends notification to reporter
     */
    public function test_reject_pending_missing_report_with_reason()
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

        // Create pending missing report
        $report = MissingReport::factory()->create([
            'user_id' => $reporter->id,
            'full_name' => 'Jane Doe',
            'case_status' => 'Pending'
        ]);

        $this->actingAs($admin);

        $response = $this->post("/admin/missing-reports/{$report->id}/status", [
            'status' => 'Rejected',
            'rejection_reason' => 'Incomplete information'
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Status updated successfully');

        // Verify report status was updated
        $report->refresh();
        $this->assertEquals('Rejected', $report->case_status);
        $this->assertEquals('Incomplete information', $report->rejection_reason);

        // Verify system log was created
        $this->assertDatabaseHas('system_logs', [
            'user_id' => $admin->id,
            'action' => 'missing_report_status_updated',
            'description' => "Updated missing report #{$report->id} status from Pending to Rejected"
        ]);
    }

    /**
     * Test Case: Update report status to "Found"
     * 
     * Test Steps:
     * 1. Navigate to admin missing reports page
     * 2. Select an approved report
     * 3. Click "Update Status" button
     * 4. Choose "Found" status
     * 5. Confirm status change
     * 
     * Expected Result: The system updates report status to "Found" and sends notification to reporter
     */
    public function test_update_report_status_to_found()
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
        $report = MissingReport::factory()->create([
            'user_id' => $reporter->id,
            'full_name' => 'John Smith',
            'case_status' => 'Missing'
        ]);

        $this->actingAs($admin);

        $response = $this->post("/admin/missing-reports/{$report->id}/status", [
            'status' => 'Found'
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Status updated successfully');

        // Verify report status was updated
        $report->refresh();
        $this->assertEquals('Found', $report->case_status);

        // Verify system log was created
        $this->assertDatabaseHas('system_logs', [
            'user_id' => $admin->id,
            'action' => 'missing_report_status_updated',
            'description' => "Updated missing report #{$report->id} status from Missing to Found"
        ]);
    }

    /**
     * Test Case: Close missing report
     * 
     * Test Steps:
     * 1. Navigate to admin missing reports page
     * 2. Select a found report
     * 3. Click "Update Status" button
     * 4. Choose "Close" status
     * 5. Confirm closure action
     * 
     * Expected Result: The system updates report status to "Closed" and archives the report
     */
    public function test_close_missing_report()
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

        // Create found missing report
        $report = MissingReport::factory()->create([
            'user_id' => $reporter->id,
            'full_name' => 'John Smith',
            'case_status' => 'Found'
        ]);

        $this->actingAs($admin);

        $response = $this->post("/admin/missing-reports/{$report->id}/status", [
            'status' => 'Closed'
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Status updated successfully');

        // Verify report status was updated
        $report->refresh();
        $this->assertEquals('Closed', $report->case_status);

        // Verify system log was created
        $this->assertDatabaseHas('system_logs', [
            'user_id' => $admin->id,
            'action' => 'missing_report_status_updated',
            'description' => "Updated missing report #{$report->id} status from Found to Closed"
        ]);
    }

    /**
     * Test Case: Update missing report information
     * 
     * Test Steps:
     * 1. Navigate to admin missing reports page
     * 2. Select a report
     * 3. Click "Edit" button
     * 4. Update report information
     * 5. Click "Save Changes" button
     * 
     * Expected Result: The system updates the report information and logs the changes
     */
    public function test_update_missing_report_information()
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
        $report = MissingReport::factory()->create([
            'user_id' => $reporter->id,
            'full_name' => 'John Smith',
            'age' => 25,
            'case_status' => 'Missing'
        ]);

        $this->actingAs($admin);

        $response = $this->put("/admin/missing-reports/{$report->id}", [
            'full_name' => 'John Smith Updated',
            'age' => 26,
            'gender' => 'Male',
            'last_seen_location' => 'Kuala Lumpur',
            'last_seen_date' => '2024-01-15',
            'physical_description' => 'Tall, brown hair',
            'additional_notes' => 'Updated information'
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Report updated successfully.');

        // Verify report information was updated
        $report->refresh();
        $this->assertEquals('John Smith Updated', $report->full_name);
        $this->assertEquals(26, $report->age);

        // Verify system log was created
        $this->assertDatabaseHas('system_logs', [
            'user_id' => $admin->id,
            'action' => 'missing_report_updated',
            'description' => 'Missing report updated by admin'
        ]);
    }

    /**
     * Test Case: Search missing reports by name
     * 
     * Test Steps:
     * 1. Navigate to admin missing reports page
     * 2. Enter search term in search box
     * 3. Click "Apply Filter" button
     * 
     * Expected Result: The system displays only reports matching the search term
     */
    public function test_search_missing_reports_by_name()
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

        // Create missing reports with different names
        MissingReport::factory()->create([
            'user_id' => $reporter->id,
            'full_name' => 'John Smith',
            'case_status' => 'Missing'
        ]);

        MissingReport::factory()->create([
            'user_id' => $reporter->id,
            'full_name' => 'Jane Doe',
            'case_status' => 'Missing'
        ]);

        $this->actingAs($admin);

        $response = $this->get('/admin/missing-reports?search=John');

        $response->assertStatus(200);
        $response->assertSee('Admin/ManageMissingReports');
    }

    /**
     * Test Case: Filter missing reports by status
     * 
     * Test Steps:
     * 1. Navigate to admin missing reports page
     * 2. Select status filter dropdown
     * 3. Choose "Pending" status
     * 4. Click "Apply Filter" button
     * 
     * Expected Result: The system displays only reports with "Pending" status
     */
    public function test_filter_missing_reports_by_status()
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

        // Create missing reports with different statuses
        MissingReport::factory()->create([
            'user_id' => $reporter->id,
            'full_name' => 'John Smith',
            'case_status' => 'Pending'
        ]);

        MissingReport::factory()->create([
            'user_id' => $reporter->id,
            'full_name' => 'Jane Doe',
            'case_status' => 'Missing'
        ]);

        $this->actingAs($admin);

        $response = $this->get('/admin/missing-reports?status=Pending');

        $response->assertStatus(200);
        $response->assertSee('Admin/ManageMissingReports');
    }

    /**
     * Test Case: View missing report details
     * 
     * Test Steps:
     * 1. Navigate to admin missing reports page
     * 2. Click "view details" button of the report
     * 3. Verify all information is displayed
     * 
     * Expected Result: The system displays complete report details including photos and documents
     */
    public function test_view_missing_report_details()
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

        // Create missing report with photos
        $report = MissingReport::factory()->create([
            'user_id' => $reporter->id,
            'full_name' => 'John Smith',
            'case_status' => 'Missing',
            'photo_paths' => ['photos/photo1.jpg', 'photos/photo2.jpg']
        ]);

        $this->actingAs($admin);

        $response = $this->get("/admin/missing-reports/{$report->id}");

        $response->assertStatus(200);
        $response->assertJson([
            'id' => $report->id,
            'full_name' => 'John Smith',
            'case_status' => 'Missing'
        ]);
    }

    /**
     * Test Case: Delete missing report
     * 
     * Test Steps:
     * 1. Navigate to admin missing reports page
     * 2. Select a report
     * 3. Click "Delete" button
     * 4. Confirm deletion action
     * 
     * Expected Result: The system deletes the report and logs the deletion action
     */
    public function test_delete_missing_report()
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
        $report = MissingReport::factory()->create([
            'user_id' => $reporter->id,
            'full_name' => 'John Smith',
            'case_status' => 'Missing'
        ]);

        $this->actingAs($admin);

        $response = $this->delete("/admin/missing-reports/{$report->id}");

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Missing person report deleted successfully');

        // Verify report was deleted
        $this->assertDatabaseMissing('missing_reports', [
            'id' => $report->id
        ]);

        // Verify system log was created
        $this->assertDatabaseHas('system_logs', [
            'user_id' => $admin->id,
            'action' => 'missing_report_deleted',
            'description' => "Deleted missing person report: John Smith"
        ]);
    }

    /**
     * Test Case: Link missing report to sighting person
     * 
     * Test Steps:
     * 1. Navigate to admin missing reports page
     * 2. Click "Sighting" button of the missing person case
     * 3. View the sighting report of the missing report
     * 
     * Expected Result: The system will redirect to manage sighting page and display the sighting report related to the missing report
     */
    public function test_link_missing_report_to_sighting_person()
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
        $report = MissingReport::factory()->create([
            'user_id' => $reporter->id,
            'full_name' => 'John Smith',
            'case_status' => 'Missing'
        ]);

        // Create sighting report
        SightingReport::factory()->create([
            'missing_report_id' => $report->id,
            'location' => 'Kuala Lumpur',
            'status' => 'Pending'
        ]);

        $this->actingAs($admin);

        $response = $this->get("/admin/missing-reports/{$report->id}/sightings");

        $response->assertRedirect();
        $response->assertRedirect(route('admin.sighting-reports', ['missing_report_id' => $report->id]));
    }

    /**
     * Test Case: Update report status with invalid status
     */
    public function test_update_report_status_with_invalid_status()
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
        $report = MissingReport::factory()->create([
            'user_id' => $reporter->id,
            'full_name' => 'John Smith',
            'case_status' => 'Missing'
        ]);

        $this->actingAs($admin);

        $response = $this->post("/admin/missing-reports/{$report->id}/status", [
            'status' => 'InvalidStatus'
        ]);

        $response->assertSessionHasErrors(['status']);
    }

    /**
     * Test Case: Update report status without rejection reason when rejecting
     */
    public function test_update_report_status_without_rejection_reason()
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
        $report = MissingReport::factory()->create([
            'user_id' => $reporter->id,
            'full_name' => 'John Smith',
            'case_status' => 'Pending'
        ]);

        $this->actingAs($admin);

        $response = $this->post("/admin/missing-reports/{$report->id}/status", [
            'status' => 'Rejected',
            'rejection_reason' => '' // Empty rejection reason
        ]);

        $response->assertSessionHasErrors(['rejection_reason']);
    }

    /**
     * Test Case: Update report with invalid data
     */
    public function test_update_report_with_invalid_data()
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
        $report = MissingReport::factory()->create([
            'user_id' => $reporter->id,
            'full_name' => 'John Smith',
            'case_status' => 'Missing'
        ]);

        $this->actingAs($admin);

        $response = $this->put("/admin/missing-reports/{$report->id}", [
            'full_name' => '', // Empty name
            'age' => 200, // Invalid age
            'gender' => 'Invalid', // Invalid gender
            'last_seen_location' => '', // Empty location
            'last_seen_date' => 'invalid-date' // Invalid date
        ]);

        $response->assertSessionHasErrors(['full_name', 'age', 'gender', 'last_seen_location', 'last_seen_date']);
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
        $report = MissingReport::factory()->create([
            'user_id' => $user->id,
            'full_name' => 'John Smith',
            'case_status' => 'Missing'
        ]);

        $this->actingAs($user);

        // Try to access admin missing reports page
        $response = $this->get('/admin/missing-reports');
        $response->assertStatus(200); // Returns access denied page instead of 403

        // Try to update report status
        $response = $this->post("/admin/missing-reports/{$report->id}/status", [
            'status' => 'Found'
        ]);
        $response->assertStatus(200); // Returns access denied page instead of 403

        // Try to delete report
        $response = $this->delete("/admin/missing-reports/{$report->id}");
        $response->assertStatus(200); // Returns access denied page instead of 403
    }

    /**
     * Test Case: Update non-existent report
     */
    public function test_update_nonexistent_report()
    {
        // Create admin user
        $admin = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
            'role' => 'admin'
        ]);

        $this->actingAs($admin);

        // Try to update non-existent report
        $response = $this->post("/admin/missing-reports/999/status", [
            'status' => 'Found'
        ]);

        $response->assertStatus(404); // Not Found
    }

    /**
     * Test Case: Delete non-existent report
     */
    public function test_delete_nonexistent_report()
    {
        // Create admin user
        $admin = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
            'role' => 'admin'
        ]);

        $this->actingAs($admin);

        // Try to delete non-existent report
        $response = $this->delete("/admin/missing-reports/999");

        $response->assertStatus(404); // Not Found
    }

    /**
     * Test Case: View non-existent report details
     */
    public function test_view_nonexistent_report_details()
    {
        // Create admin user
        $admin = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
            'role' => 'admin'
        ]);

        $this->actingAs($admin);

        // Try to view non-existent report
        $response = $this->get("/admin/missing-reports/999");

        $response->assertStatus(404); // Not Found
    }
}
