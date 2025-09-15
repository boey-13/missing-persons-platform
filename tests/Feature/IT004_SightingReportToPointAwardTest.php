<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\MissingReport;
use App\Models\SightingReport;
use App\Models\UserPoint;
use App\Models\PointTransaction;
use App\Services\PointsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Hash;

class IT004_SightingReportToPointAwardTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test the complete flow from user submitting a sighting report to admin approval and points award
     * 
     * Test Procedure:
     * 1. User logs in to the system
     * 2. Navigate to an approved missing person case
     * 3. Click "Report Sighting" button
     * 4. Fill in sighting report form
     * 5. Submit the sighting report
     * 6. Verify report is created with "Pending" status
     * 7. Admin logs in to admin panel
     * 8. Navigate to "Manage Sighting Reports"
     * 9. View the newly submitted sighting report
     * 10. Review all report details and photos
     * 11. Approve the sighting report
     * 12. Verify report status changes to "Approved"
     * 13. Check that user receives points (10 points)
     * 14. Verify points transaction is recorded
     * 15. Check that user receives notification about points earned
     * 16. Verify user's total points are updated
     * 17. Check points history shows the sighting report points
     */
    public function test_complete_sighting_report_to_point_award_flow()
    {
        // Test Data
        $sightingData = [
            'location' => 'Kuala Lumpur City Center',
            'sighted_at' => '2024-01-20T14:30',
            'description' => 'Saw person matching description at KLCC',
            'reporter_name' => 'Mary Hong',
            'reporter_phone' => '0123456789',
            'reporter_email' => 'mary@gmail.com',
        ];

        // Create user (reporter)
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

        // Create an approved missing person case
        $missingReport = MissingReport::factory()->create([
            'full_name' => 'Sarah Johnson',
            'last_seen_date' => '2024-01-15',
            'case_status' => 'Missing'
        ]);

        // Step 1: User logs in to the system
        $this->actingAs($user);

        // Step 2: Navigate to an approved missing person case
        $response = $this->get('/missing-persons/' . $missingReport->id);
        $response->assertStatus(200);
        $response->assertSee('Sarah Johnson');

        // Step 3: Click "Report Sighting" button - Navigate to sighting report form
        $response = $this->get('/missing-persons/' . $missingReport->id . '/report-sighting');
        $response->assertStatus(200);
        $response->assertSee('SightingReports/ReportSighting');

        // Step 4-5: Fill in sighting report form and submit the sighting report
        Storage::fake('public');
        
        $photo1 = UploadedFile::fake()->image('sighting1.jpg', 800, 600)->size(2048); // 2MB
        $photo2 = UploadedFile::fake()->image('sighting2.jpg', 800, 600)->size(2048); // 2MB

        $reportData = array_merge($sightingData, [
            'photos' => [$photo1, $photo2]
        ]);

        $response = $this->post('/missing-persons/' . $missingReport->id . '/sightings', $reportData);

        // Step 6: Verify report is created with "Pending" status
        $response->assertRedirect('/missing-persons/' . $missingReport->id);
        $response->assertSessionHas('success', 'Sighting report submitted successfully!');

        $this->assertDatabaseHas('sighting_reports', [
            'user_id' => $user->id,
            'missing_report_id' => $missingReport->id,
            'location' => 'Kuala Lumpur City Center',
            'description' => 'Saw person matching description at KLCC',
            'reporter_name' => 'Mary Hong',
            'reporter_phone' => '0123456789',
            'reporter_email' => 'mary@gmail.com',
            'status' => 'Pending'
        ]);

        $sightingReport = SightingReport::where('user_id', $user->id)->first();
        $this->assertNotNull($sightingReport);
        $this->assertEquals('Pending', $sightingReport->status);

        // Verify files were uploaded
        $this->assertCount(2, $sightingReport->photo_paths);
        Storage::disk('public')->assertExists($sightingReport->photo_paths[0]);
        Storage::disk('public')->assertExists($sightingReport->photo_paths[1]);

        // Step 7: Admin logs in to admin panel
        $this->post('/logout');
        $this->actingAs($admin);

        // Step 8: Navigate to "Manage Sighting Reports"
        $response = $this->get('/admin/sighting-reports');
        $response->assertStatus(200);
        $response->assertSee('Admin/ManageSightingReports');

        // Step 9-10: View the newly submitted sighting report and review all report details and photos
        $response = $this->get('/admin/sighting-reports/' . $sightingReport->id);
        $response->assertStatus(200);
        $responseData = $response->json();
        $this->assertEquals('Kuala Lumpur City Center', $responseData['location']);
        $this->assertEquals('Saw person matching description at KLCC', $responseData['description']);
        $this->assertEquals('Mary Hong', $responseData['reporter_name']);
        $this->assertEquals('0123456789', $responseData['reporter_phone']);
        $this->assertEquals('mary@gmail.com', $responseData['reporter_email']);

        // Step 11: Approve the sighting report
        $response = $this->post('/admin/sighting-reports/' . $sightingReport->id . '/status', [
            'status' => 'Approved'
        ]);

        $response->assertRedirect();

        // Step 12: Verify report status changes to "Approved"
        $sightingReport->refresh();
        $this->assertEquals('Approved', $sightingReport->status);

        // Step 13-17: Check that user receives points and verify all point-related functionality
        $this->assertDatabaseHas('user_points', [
            'user_id' => $user->id,
            'current_points' => 10,
            'total_earned_points' => 10,
            'total_spent_points' => 0
        ]);

        $this->assertDatabaseHas('point_transactions', [
            'user_id' => $user->id,
            'type' => 'earned',
            'points' => 10,
            'action' => 'sighting_report',
            'description' => 'Submitted approved sighting report'
        ]);

        // Verify user's total points are updated
        $userPoint = UserPoint::where('user_id', $user->id)->first();
        $this->assertEquals(10, $userPoint->current_points);
        $this->assertEquals(10, $userPoint->total_earned_points);

        // Verify points transaction metadata
        $transaction = PointTransaction::where('user_id', $user->id)
            ->where('action', 'sighting_report')
            ->first();
        $this->assertNotNull($transaction);
        $this->assertEquals($sightingReport->id, $transaction->metadata['report_id']);
    }

    /**
     * Test sighting report validation errors
     */
    public function test_sighting_report_validation_errors()
    {
        $user = User::factory()->create([
            'name' => 'Mary Hong',
            'email' => 'mary@gmail.com',
            'password' => Hash::make('Password123!'),
            'role' => 'user'
        ]);

        $missingReport = MissingReport::factory()->create([
            'last_seen_date' => '2024-01-15',
            'case_status' => 'Missing'
        ]);

        $this->actingAs($user);

        // Test with missing required fields
        $response = $this->post('/missing-persons/' . $missingReport->id . '/sightings', [
            'location' => '',
            'description' => '',
            'sighted_at' => '',
            'reporter_name' => '',
            'reporter_phone' => '',
            'reporter_email' => '',
            'photos' => []
        ]);

        $response->assertSessionHasErrors(['location', 'description', 'sighted_at', 'reporter_name', 'reporter_phone', 'reporter_email', 'photos']);
    }

    /**
     * Test sighting date validation (must be after last seen date)
     */
    public function test_sighting_date_validation()
    {
        $user = User::factory()->create([
            'name' => 'Mary Hong',
            'email' => 'mary@gmail.com',
            'password' => Hash::make('Password123!'),
            'role' => 'user'
        ]);

        $missingReport = MissingReport::factory()->create([
            'last_seen_date' => '2024-01-15',
            'case_status' => 'Missing'
        ]);

        $this->actingAs($user);

        Storage::fake('public');
        $photo = UploadedFile::fake()->image('sighting.jpg', 800, 600)->size(1024);

        // Test with sighting date before last seen date
        $response = $this->post('/missing-persons/' . $missingReport->id . '/sightings', [
            'location' => 'Test Location',
            'description' => 'Test description',
            'sighted_at' => '2024-01-10', // Before last seen date
            'reporter_name' => 'Mary Hong',
            'reporter_phone' => '0123456789',
            'reporter_email' => 'mary@gmail.com',
            'photos' => [$photo]
        ]);

        $response->assertSessionHasErrors(['sighted_at']);
    }

    /**
     * Test phone number validation
     */
    public function test_phone_number_validation()
    {
        $user = User::factory()->create([
            'name' => 'Mary Hong',
            'email' => 'mary@gmail.com',
            'password' => Hash::make('Password123!'),
            'role' => 'user'
        ]);

        $missingReport = MissingReport::factory()->create([
            'last_seen_date' => '2024-01-15',
            'case_status' => 'Missing'
        ]);

        $this->actingAs($user);

        Storage::fake('public');
        $photo = UploadedFile::fake()->image('sighting.jpg', 800, 600)->size(1024);

        // Test with invalid phone number
        $response = $this->post('/missing-persons/' . $missingReport->id . '/sightings', [
            'location' => 'Test Location',
            'description' => 'Test description',
            'sighted_at' => '2024-01-20',
            'reporter_name' => 'Mary Hong',
            'reporter_phone' => '123456789', // Invalid format
            'reporter_email' => 'mary@gmail.com',
            'photos' => [$photo]
        ]);

        $response->assertSessionHasErrors(['reporter_phone']);
    }

    /**
     * Test file upload validation
     */
    public function test_file_upload_validation()
    {
        $user = User::factory()->create([
            'name' => 'Mary Hong',
            'email' => 'mary@gmail.com',
            'password' => Hash::make('Password123!'),
            'role' => 'user'
        ]);

        $missingReport = MissingReport::factory()->create([
            'last_seen_date' => '2024-01-15',
            'case_status' => 'Missing'
        ]);

        $this->actingAs($user);

        Storage::fake('public');

        // Test with file too large
        $largePhoto = UploadedFile::fake()->image('large.jpg', 800, 600)->size(6000); // 6MB
        $invalidFile = UploadedFile::fake()->create('document.txt', 1024, 'text/plain');

        $response = $this->post('/missing-persons/' . $missingReport->id . '/sightings', [
            'location' => 'Test Location',
            'description' => 'Test description',
            'sighted_at' => '2024-01-20',
            'reporter_name' => 'Mary Hong',
            'reporter_phone' => '0123456789',
            'reporter_email' => 'mary@gmail.com',
            'photos' => [$largePhoto, $invalidFile]
        ]);

        // Check for validation errors (may be in different fields)
        $response->assertSessionHasErrors();
    }

    /**
     * Test admin can reject sighting report
     */
    public function test_admin_can_reject_sighting_report()
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

        $missingReport = MissingReport::factory()->create([
            'case_status' => 'Missing'
        ]);

        // Create a pending sighting report
        $sightingReport = SightingReport::factory()->create([
            'user_id' => $user->id,
            'missing_report_id' => $missingReport->id,
            'status' => 'Pending'
        ]);

        $this->actingAs($admin);

        // Reject the sighting report
        $response = $this->post('/admin/sighting-reports/' . $sightingReport->id . '/status', [
            'status' => 'Rejected'
        ]);

        $response->assertRedirect();

        $sightingReport->refresh();
        $this->assertEquals('Rejected', $sightingReport->status);

        // Verify no points were awarded
        $this->assertDatabaseMissing('point_transactions', [
            'user_id' => $user->id,
            'action' => 'sighting_report'
        ]);
    }

    /**
     * Test that sighting reports cannot be submitted for non-approved cases
     */
    public function test_cannot_submit_sighting_for_non_approved_case()
    {
        $user = User::factory()->create([
            'name' => 'Mary Hong',
            'email' => 'mary@gmail.com',
            'password' => Hash::make('Password123!'),
            'role' => 'user'
        ]);

        // Create a pending missing report
        $missingReport = MissingReport::factory()->create([
            'case_status' => 'Pending'
        ]);

        $this->actingAs($user);

        // Try to access sighting report form
        $response = $this->get('/missing-persons/' . $missingReport->id . '/report-sighting');
        $response->assertRedirect();
        $response->assertSessionHas('error', 'Sighting submissions are not available for this case status.');
    }

    /**
     * Test points service functionality
     */
    public function test_points_service_awards_correct_points()
    {
        $user = User::factory()->create([
            'name' => 'Mary Hong',
            'email' => 'mary@gmail.com',
            'password' => Hash::make('Password123!'),
            'role' => 'user'
        ]);

        $pointsService = new PointsService();

        // Award sighting report points
        $pointsService->awardSightingReportPoints($user, 123);

        // Verify points were awarded correctly
        $this->assertDatabaseHas('user_points', [
            'user_id' => $user->id,
            'current_points' => 10,
            'total_earned_points' => 10
        ]);

        $this->assertDatabaseHas('point_transactions', [
            'user_id' => $user->id,
            'type' => 'earned',
            'points' => 10,
            'action' => 'sighting_report',
            'description' => 'Submitted approved sighting report',
            'metadata' => json_encode(['report_id' => 123])
        ]);
    }

    /**
     * Test that points are not awarded for already approved reports
     */
    public function test_no_duplicate_points_for_already_approved_reports()
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

        $missingReport = MissingReport::factory()->create([
            'case_status' => 'Missing'
        ]);

        // Create an already approved sighting report
        $sightingReport = SightingReport::factory()->create([
            'user_id' => $user->id,
            'missing_report_id' => $missingReport->id,
            'status' => 'Approved'
        ]);

        $this->actingAs($admin);

        // Try to approve again
        $response = $this->post('/admin/sighting-reports/' . $sightingReport->id . '/status', [
            'status' => 'Approved'
        ]);

        $response->assertRedirect();

        // Verify no duplicate points were awarded
        $transactionCount = PointTransaction::where('user_id', $user->id)
            ->where('action', 'sighting_report')
            ->count();
        
        $this->assertEquals(0, $transactionCount);
    }
}
