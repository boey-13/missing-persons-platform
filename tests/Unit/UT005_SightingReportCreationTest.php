<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Models\MissingReport;
use App\Models\SightingReport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Hash;

class UT005_SightingReportCreationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    /**
     * Test Case: Create sighting report with valid data
     * 
     * Test Steps:
     * 1. Navigate to missing person details page
     * 2. Click "Submit Sighting" button
     * 3. Fill in all required information.
     * 4. Click "Submit Sighting" button
     * 
     * Expected Result: The system displays success message "Sighting report submitted successfully!" and redirects to missing person page
     */
    public function test_create_sighting_report_with_valid_data()
    {
        $user = User::factory()->create([
            'name' => 'John Reporter',
            'email' => 'john@example.com',
            'password' => Hash::make('password123')
        ]);

        // Create an approved missing report
        $missingReport = MissingReport::factory()->create([
            'user_id' => $user->id,
            'full_name' => 'John Smith',
            'ic_number' => '123456789012',
            'age' => 25,
            'gender' => 'Male',
            'last_seen_date' => '2024-01-15',
            'last_seen_location' => 'Kuala Lumpur',
            'case_status' => 'Approved'
        ]);

        $this->actingAs($user);

        $photo = UploadedFile::fake()->image('sighting_photo.jpg', 800, 600)->size(1024); // 1MB

        $response = $this->post("/missing-persons/{$missingReport->id}/sightings", [
            'location' => 'Kuala Lumpur City Center',
            'description' => 'Saw person matching description',
            'sighted_at' => '2024-01-20',
            'reporter_name' => 'John Reporter',
            'reporter_phone' => '0123456789',
            'reporter_email' => 'john@example.com',
            'photos' => [$photo]
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Sighting report submitted successfully!');

        // Verify sighting report was created in database
        $this->assertDatabaseHas('sighting_reports', [
            'missing_report_id' => $missingReport->id,
            'location' => 'Kuala Lumpur City Center',
            'description' => 'Saw person matching description',
            'reporter_name' => 'John Reporter',
            'reporter_phone' => '0123456789',
            'reporter_email' => 'john@example.com',
            'status' => 'Pending'
        ]);

        // Verify files were stored
        $this->assertTrue(Storage::disk('public')->exists('sightings'));
    }

    /**
     * Test Case: Create sighting report with empty one of the required fields
     * 
     * Test Steps:
     * 1. Navigate to missing person details page
     * 2. Click "Submit Sighting" button
     * 3. Fill in the required information and leave one require field empty.
     * 4. Click "Submit Sighting" button
     * 
     * Expected Result: The system displays error message "The field is required"
     */
    public function test_create_sighting_report_with_empty_required_fields()
    {
        $user = User::factory()->create([
            'name' => 'Empty Reporter',
            'email' => 'empty@example.com',
            'password' => Hash::make('password123')
        ]);

        // Create an approved missing report
        $missingReport = MissingReport::factory()->create([
            'user_id' => $user->id,
            'case_status' => 'Approved',
            'last_seen_date' => '2024-01-15'
        ]);

        $this->actingAs($user);

        $response = $this->post("/missing-persons/{$missingReport->id}/sightings", [
            'location' => '', // Empty required field
            'description' => '', // Empty required field
            'sighted_at' => '', // Empty required field
            'reporter_name' => '', // Empty required field
            'reporter_phone' => '', // Empty required field
            'reporter_email' => '', // Empty required field
            'photos' => [] // Empty required field
        ]);

        $response->assertSessionHasErrors(['location', 'description', 'sighted_at', 'reporter_name', 'reporter_phone', 'reporter_email', 'photos']);
    }

    /**
     * Test Case: Create a sighting date earlier than the missing report date
     * 
     * Test Steps:
     * 1. Navigate to missing person details page
     * 2. Click "Submit Sighting" button
     * 3. Fill in the required information and wants to select the date earlier than the missing report datte
     * 
     * Expected Result: The system hides dates earlier than the missing person report date.
     */
    public function test_create_sighting_date_earlier_than_missing_report_date()
    {
        $user = User::factory()->create([
            'name' => 'Date Reporter',
            'email' => 'date@example.com',
            'password' => Hash::make('password123')
        ]);

        // Create an approved missing report with last seen date
        $missingReport = MissingReport::factory()->create([
            'user_id' => $user->id,
            'case_status' => 'Approved',
            'last_seen_date' => '2024-01-15'
        ]);

        $this->actingAs($user);

        $photo = UploadedFile::fake()->image('sighting_photo.jpg', 800, 600)->size(1024);

        $response = $this->post("/missing-persons/{$missingReport->id}/sightings", [
            'location' => 'Kuala Lumpur City Center',
            'description' => 'Saw person matching description',
            'sighted_at' => '2024-01-10', // Earlier than last_seen_date (2024-01-15)
            'reporter_name' => 'Date Reporter',
            'reporter_phone' => '0123456789',
            'reporter_email' => 'date@example.com',
            'photos' => [$photo]
        ]);

        $response->assertSessionHasErrors(['sighted_at']);
        $this->assertStringContainsString('Sighting date must be after the last seen date', $response->getSession()->get('errors')->first('sighted_at'));
    }

    /**
     * Test Case: Create sighting report with invalid phone number format
     */
    public function test_create_sighting_report_with_invalid_phone_number()
    {
        $user = User::factory()->create([
            'name' => 'Phone Reporter',
            'email' => 'phone@example.com',
            'password' => Hash::make('password123')
        ]);

        $missingReport = MissingReport::factory()->create([
            'user_id' => $user->id,
            'case_status' => 'Approved',
            'last_seen_date' => '2024-01-15'
        ]);

        $this->actingAs($user);

        $photo = UploadedFile::fake()->image('sighting_photo.jpg', 800, 600)->size(1024);

        $response = $this->post("/missing-persons/{$missingReport->id}/sightings", [
            'location' => 'Kuala Lumpur City Center',
            'description' => 'Saw person matching description',
            'sighted_at' => '2024-01-20',
            'reporter_name' => 'Phone Reporter',
            'reporter_phone' => '123', // Invalid phone format
            'reporter_email' => 'phone@example.com',
            'photos' => [$photo]
        ]);

        $response->assertSessionHasErrors(['reporter_phone']);
        $this->assertStringContainsString('Phone number must be 10-11 digits starting with 01', $response->getSession()->get('errors')->first('reporter_phone'));
    }

    /**
     * Test Case: Create sighting report with invalid email format
     */
    public function test_create_sighting_report_with_invalid_email_format()
    {
        $user = User::factory()->create([
            'name' => 'Email Reporter',
            'email' => 'email@example.com',
            'password' => Hash::make('password123')
        ]);

        $missingReport = MissingReport::factory()->create([
            'user_id' => $user->id,
            'case_status' => 'Approved',
            'last_seen_date' => '2024-01-15'
        ]);

        $this->actingAs($user);

        $photo = UploadedFile::fake()->image('sighting_photo.jpg', 800, 600)->size(1024);

        $response = $this->post("/missing-persons/{$missingReport->id}/sightings", [
            'location' => 'Kuala Lumpur City Center',
            'description' => 'Saw person matching description',
            'sighted_at' => '2024-01-20',
            'reporter_name' => 'Email Reporter',
            'reporter_phone' => '0123456789',
            'reporter_email' => 'invalid-email', // Invalid email format
            'photos' => [$photo]
        ]);

        $response->assertSessionHasErrors(['reporter_email']);
    }

    /**
     * Test Case: Create sighting report with invalid photo format
     */
    public function test_create_sighting_report_with_invalid_photo_format()
    {
        $user = User::factory()->create([
            'name' => 'Photo Reporter',
            'email' => 'photo@example.com',
            'password' => Hash::make('password123')
        ]);

        $missingReport = MissingReport::factory()->create([
            'user_id' => $user->id,
            'case_status' => 'Approved',
            'last_seen_date' => '2024-01-15'
        ]);

        $this->actingAs($user);

        $invalidFile = UploadedFile::fake()->create('document.txt', 1024); // Not an image

        $response = $this->post("/missing-persons/{$missingReport->id}/sightings", [
            'location' => 'Kuala Lumpur City Center',
            'description' => 'Saw person matching description',
            'sighted_at' => '2024-01-20',
            'reporter_name' => 'Photo Reporter',
            'reporter_phone' => '0123456789',
            'reporter_email' => 'photo@example.com',
            'photos' => [$invalidFile]
        ]);

        $response->assertSessionHasErrors(['photos.0']);
        $this->assertStringContainsString('Photos must be image files', $response->getSession()->get('errors')->first('photos.0'));
    }

    /**
     * Test Case: Create sighting report with oversized photo
     */
    public function test_create_sighting_report_with_oversized_photo()
    {
        $user = User::factory()->create([
            'name' => 'Oversized Reporter',
            'email' => 'oversized@example.com',
            'password' => Hash::make('password123')
        ]);

        $missingReport = MissingReport::factory()->create([
            'user_id' => $user->id,
            'case_status' => 'Approved',
            'last_seen_date' => '2024-01-15'
        ]);

        $this->actingAs($user);

        $oversizedPhoto = UploadedFile::fake()->image('large_photo.jpg', 800, 600)->size(6144); // 6MB (over 5MB limit)

        $response = $this->post("/missing-persons/{$missingReport->id}/sightings", [
            'location' => 'Kuala Lumpur City Center',
            'description' => 'Saw person matching description',
            'sighted_at' => '2024-01-20',
            'reporter_name' => 'Oversized Reporter',
            'reporter_phone' => '0123456789',
            'reporter_email' => 'oversized@example.com',
            'photos' => [$oversizedPhoto]
        ]);

        $response->assertSessionHasErrors(['photos.0']);
        $this->assertStringContainsString('Each photo must be smaller than 5MB', $response->getSession()->get('errors')->first('photos.0'));
    }

    /**
     * Test Case: Create sighting report with missing report in wrong status
     */
    public function test_create_sighting_report_with_wrong_missing_report_status()
    {
        $user = User::factory()->create([
            'name' => 'Status Reporter',
            'email' => 'status@example.com',
            'password' => Hash::make('password123')
        ]);

        // Create missing report with status that doesn't allow sightings
        $missingReport = MissingReport::factory()->create([
            'user_id' => $user->id,
            'case_status' => 'Pending' // Not Approved or Missing
        ]);

        $this->actingAs($user);

        $photo = UploadedFile::fake()->image('sighting_photo.jpg', 800, 600)->size(1024);

        $response = $this->post("/missing-persons/{$missingReport->id}/sightings", [
            'location' => 'Kuala Lumpur City Center',
            'description' => 'Saw person matching description',
            'sighted_at' => '2024-01-20',
            'reporter_name' => 'Status Reporter',
            'reporter_phone' => '0123456789',
            'reporter_email' => 'status@example.com',
            'photos' => [$photo]
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error', 'Sighting submissions are not available for this case status.');
    }

    /**
     * Test Case: Create sighting report with valid phone number formats
     */
    public function test_create_sighting_report_with_valid_phone_formats()
    {
        $user = User::factory()->create([
            'name' => 'Phone Reporter',
            'email' => 'phone@example.com',
            'password' => Hash::make('password123')
        ]);

        $missingReport = MissingReport::factory()->create([
            'user_id' => $user->id,
            'case_status' => 'Approved',
            'last_seen_date' => '2024-01-15'
        ]);

        $this->actingAs($user);

        $validPhones = [
            '0123456789',  // 10 digits
            '01234567890', // 11 digits
        ];

        foreach ($validPhones as $phone) {
            $photo = UploadedFile::fake()->image('sighting_photo.jpg', 800, 600)->size(1024);

            $response = $this->post("/missing-persons/{$missingReport->id}/sightings", [
                'location' => 'Kuala Lumpur City Center',
                'description' => 'Saw person matching description',
                'sighted_at' => '2024-01-20',
                'reporter_name' => 'Phone Reporter',
                'reporter_phone' => $phone,
                'reporter_email' => 'phone@example.com',
                'photos' => [$photo]
            ]);

            $response->assertRedirect();
            $response->assertSessionHas('success', 'Sighting report submitted successfully!');
        }
    }

    /**
     * Test Case: Create sighting report with invalid phone number formats
     */
    public function test_create_sighting_report_with_invalid_phone_formats()
    {
        $user = User::factory()->create([
            'name' => 'Invalid Phone Reporter',
            'email' => 'invalidphone@example.com',
            'password' => Hash::make('password123')
        ]);

        $missingReport = MissingReport::factory()->create([
            'user_id' => $user->id,
            'case_status' => 'Approved',
            'last_seen_date' => '2024-01-15'
        ]);

        $this->actingAs($user);

        $invalidPhones = [
            '123',           // Too short
            '012345678',     // Too short
            '012345678901',  // Too long
            '0223456789',    // Doesn't start with 01
            '012345678a',    // Contains letter
        ];

        foreach ($invalidPhones as $phone) {
            $photo = UploadedFile::fake()->image('sighting_photo.jpg', 800, 600)->size(1024);

            $response = $this->post("/missing-persons/{$missingReport->id}/sightings", [
                'location' => 'Kuala Lumpur City Center',
                'description' => 'Saw person matching description',
                'sighted_at' => '2024-01-20',
                'reporter_name' => 'Invalid Phone Reporter',
                'reporter_phone' => $phone,
                'reporter_email' => 'invalidphone@example.com',
                'photos' => [$photo]
            ]);

            $response->assertSessionHasErrors(['reporter_phone']);
        }
    }

    /**
     * Test Case: Create sighting report with valid photo formats
     */
    public function test_create_sighting_report_with_valid_photo_formats()
    {
        $user = User::factory()->create([
            'name' => 'Photo Reporter',
            'email' => 'photo@example.com',
            'password' => Hash::make('password123')
        ]);

        $missingReport = MissingReport::factory()->create([
            'user_id' => $user->id,
            'case_status' => 'Approved',
            'last_seen_date' => '2024-01-15'
        ]);

        $this->actingAs($user);

        $validFormats = [
            'jpg' => UploadedFile::fake()->image('photo.jpg', 800, 600)->size(1024),
            'jpeg' => UploadedFile::fake()->image('photo.jpeg', 800, 600)->size(1024),
            'png' => UploadedFile::fake()->image('photo.png', 800, 600)->size(1024),
            'gif' => UploadedFile::fake()->image('photo.gif', 800, 600)->size(1024),
        ];

        foreach ($validFormats as $format => $file) {
            $response = $this->post("/missing-persons/{$missingReport->id}/sightings", [
                'location' => 'Kuala Lumpur City Center',
                'description' => 'Saw person matching description',
                'sighted_at' => '2024-01-20',
                'reporter_name' => 'Photo Reporter',
                'reporter_phone' => '0123456789',
                'reporter_email' => 'photo@example.com',
                'photos' => [$file]
            ]);

            $response->assertRedirect();
            $response->assertSessionHas('success', 'Sighting report submitted successfully!');
        }
    }

    /**
     * Test Case: Create sighting report with multiple photos
     */
    public function test_create_sighting_report_with_multiple_photos()
    {
        $user = User::factory()->create([
            'name' => 'Multi Photo Reporter',
            'email' => 'multiphoto@example.com',
            'password' => Hash::make('password123')
        ]);

        $missingReport = MissingReport::factory()->create([
            'user_id' => $user->id,
            'case_status' => 'Approved',
            'last_seen_date' => '2024-01-15'
        ]);

        $this->actingAs($user);

        $photos = [
            UploadedFile::fake()->image('photo1.jpg', 800, 600)->size(1024),
            UploadedFile::fake()->image('photo2.jpg', 800, 600)->size(1024),
            UploadedFile::fake()->image('photo3.jpg', 800, 600)->size(1024),
        ];

        $response = $this->post("/missing-persons/{$missingReport->id}/sightings", [
            'location' => 'Kuala Lumpur City Center',
            'description' => 'Saw person matching description',
            'sighted_at' => '2024-01-20',
            'reporter_name' => 'Multi Photo Reporter',
            'reporter_phone' => '0123456789',
            'reporter_email' => 'multiphoto@example.com',
            'photos' => $photos
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Sighting report submitted successfully!');

        // Verify sighting report was created
        $this->assertDatabaseHas('sighting_reports', [
            'missing_report_id' => $missingReport->id,
            'location' => 'Kuala Lumpur City Center',
            'reporter_name' => 'Multi Photo Reporter'
        ]);
    }

    /**
     * Test Case: Create sighting report with maximum length description
     */
    public function test_create_sighting_report_with_maximum_length_description()
    {
        $user = User::factory()->create([
            'name' => 'Max Desc Reporter',
            'email' => 'maxdesc@example.com',
            'password' => Hash::make('password123')
        ]);

        $missingReport = MissingReport::factory()->create([
            'user_id' => $user->id,
            'case_status' => 'Approved',
            'last_seen_date' => '2024-01-15'
        ]);

        $this->actingAs($user);

        $photo = UploadedFile::fake()->image('sighting_photo.jpg', 800, 600)->size(1024);
        $maxDescription = str_repeat('A', 2000); // Maximum length description

        $response = $this->post("/missing-persons/{$missingReport->id}/sightings", [
            'location' => 'Kuala Lumpur City Center',
            'description' => $maxDescription,
            'sighted_at' => '2024-01-20',
            'reporter_name' => 'Max Desc Reporter',
            'reporter_phone' => '0123456789',
            'reporter_email' => 'maxdesc@example.com',
            'photos' => [$photo]
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Sighting report submitted successfully!');
    }

    /**
     * Test Case: Create sighting report with description exceeding maximum length
     */
    public function test_create_sighting_report_with_description_exceeding_maximum_length()
    {
        $user = User::factory()->create([
            'name' => 'Exceed Desc Reporter',
            'email' => 'exceeddesc@example.com',
            'password' => Hash::make('password123')
        ]);

        $missingReport = MissingReport::factory()->create([
            'user_id' => $user->id,
            'case_status' => 'Approved',
            'last_seen_date' => '2024-01-15'
        ]);

        $this->actingAs($user);

        $photo = UploadedFile::fake()->image('sighting_photo.jpg', 800, 600)->size(1024);
        $exceedDescription = str_repeat('A', 2001); // Exceeds maximum length

        $response = $this->post("/missing-persons/{$missingReport->id}/sightings", [
            'location' => 'Kuala Lumpur City Center',
            'description' => $exceedDescription,
            'sighted_at' => '2024-01-20',
            'reporter_name' => 'Exceed Desc Reporter',
            'reporter_phone' => '0123456789',
            'reporter_email' => 'exceeddesc@example.com',
            'photos' => [$photo]
        ]);

        $response->assertSessionHasErrors(['description']);
    }

    /**
     * Test Case: Create sighting report with location exceeding maximum length
     */
    public function test_create_sighting_report_with_location_exceeding_maximum_length()
    {
        $user = User::factory()->create([
            'name' => 'Exceed Loc Reporter',
            'email' => 'exceedloc@example.com',
            'password' => Hash::make('password123')
        ]);

        $missingReport = MissingReport::factory()->create([
            'user_id' => $user->id,
            'case_status' => 'Approved',
            'last_seen_date' => '2024-01-15'
        ]);

        $this->actingAs($user);

        $photo = UploadedFile::fake()->image('sighting_photo.jpg', 800, 600)->size(1024);
        $exceedLocation = str_repeat('A', 256); // Exceeds maximum length (255)

        $response = $this->post("/missing-persons/{$missingReport->id}/sightings", [
            'location' => $exceedLocation,
            'description' => 'Saw person matching description',
            'sighted_at' => '2024-01-20',
            'reporter_name' => 'Exceed Loc Reporter',
            'reporter_phone' => '0123456789',
            'reporter_email' => 'exceedloc@example.com',
            'photos' => [$photo]
        ]);

        $response->assertSessionHasErrors(['location']);
    }

    /**
     * Test Case: Create sighting report with same date as missing report date
     */
    public function test_create_sighting_report_with_same_date_as_missing_report_date()
    {
        $user = User::factory()->create([
            'name' => 'Same Date Reporter',
            'email' => 'samedate@example.com',
            'password' => Hash::make('password123')
        ]);

        $missingReport = MissingReport::factory()->create([
            'user_id' => $user->id,
            'case_status' => 'Approved',
            'last_seen_date' => '2024-01-15'
        ]);

        $this->actingAs($user);

        $photo = UploadedFile::fake()->image('sighting_photo.jpg', 800, 600)->size(1024);

        $response = $this->post("/missing-persons/{$missingReport->id}/sightings", [
            'location' => 'Kuala Lumpur City Center',
            'description' => 'Saw person matching description',
            'sighted_at' => '2024-01-15', // Same date as last_seen_date
            'reporter_name' => 'Same Date Reporter',
            'reporter_phone' => '0123456789',
            'reporter_email' => 'samedate@example.com',
            'photos' => [$photo]
        ]);

        $response->assertSessionHasErrors(['sighted_at']);
        $this->assertStringContainsString('Sighting date must be after the last seen date', $response->getSession()->get('errors')->first('sighted_at'));
    }
}
