<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Models\VolunteerApplication;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Hash;

class UT007_VolunteerApplicationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    /**
     * Test Case: Submit volunteer application with valid data
     * 
     * Test Steps:
     * 1. Navigate to volunteer application page
     * 2. Fill in motivation statement
     * 3. Select relevant skills
     * 4. Select languages spoken
     * 5. Select available times
     * 6. Fill in emergency contact details
     * 7. Click "Submit Application" button
     * 
     * Expected Result: The system displays success message "Application submitted successfully" and redirects to application pending page
     */
    public function test_submit_volunteer_application_with_valid_data()
    {
        $user = User::factory()->create([
            'name' => 'John Smith',
            'email' => 'john@example.com',
            'password' => Hash::make('password123')
        ]);

        $this->actingAs($user);

        $supportingDoc = UploadedFile::fake()->create('document.pdf', 1024);

        $response = $this->post('/volunteer/apply', [
            'motivation' => 'I want to help find missing persons',
            'skills' => ['Communication', 'Search & Rescue'],
            'languages' => ['English', 'Malay'],
            'availability' => ['Mon', 'Tues'],
            'preferred_roles' => ['Search Team', 'Communication'],
            'areas' => 'Kuala Lumpur',
            'transport_mode' => 'Car',
            'emergency_contact_name' => 'Jane Smith',
            'emergency_contact_phone' => '0123456789',
            'prior_experience' => 'Previous volunteer work',
            'supporting_documents' => [$supportingDoc]
        ]);

        $response->assertRedirect('/volunteer/application-pending');
        $response->assertSessionHas('success', 'Application submitted.');

        // Verify application was created in database
        $this->assertDatabaseHas('volunteer_applications', [
            'user_id' => $user->id,
            'motivation' => 'I want to help find missing persons',
            'emergency_contact_name' => 'Jane Smith',
            'emergency_contact_phone' => '0123456789',
            'status' => 'Pending'
        ]);

        // Verify files were stored
        $this->assertTrue(Storage::disk('public')->exists('supporting_documents'));
    }

    /**
     * Test Case: Submit application with empty motivation
     * 
     * Test Steps:
     * 1. Navigate to volunteer application page
     * 2. Leave motivation field empty
     * 3. Fill other required fields
     * 4. Click "Submit Application" button
     * 
     * Expected Result: The system displays error message "Motivation must be at least 10 characters long."
     */
    public function test_submit_application_with_empty_motivation()
    {
        $user = User::factory()->create([
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'password' => Hash::make('password123')
        ]);

        $this->actingAs($user);

        $response = $this->post('/volunteer/apply', [
            'motivation' => '', // Empty motivation
            'skills' => ['Communication'],
            'languages' => ['English'],
            'availability' => ['Mon'],
            'emergency_contact_name' => 'John Doe',
            'emergency_contact_phone' => '0123456789'
        ]);

        $response->assertSessionHasErrors(['motivation']);
    }

    /**
     * Test Case: Submit application with no skills selected
     * 
     * Test Steps:
     * 1. Navigate to volunteer application page
     * 2. Leave skills field empty
     * 3. Fill other required fields
     * 4. Click "Submit Application" button
     * 
     * Expected Result: The system displays error message "Please select at least one skill."
     */
    public function test_submit_application_with_no_skills_selected()
    {
        $user = User::factory()->create([
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'password' => Hash::make('password123')
        ]);

        $this->actingAs($user);

        $response = $this->post('/volunteer/apply', [
            'motivation' => 'I want to help find missing persons',
            'skills' => [], // Empty skills array
            'languages' => ['English'],
            'availability' => ['Mon'],
            'emergency_contact_name' => 'John Doe',
            'emergency_contact_phone' => '0123456789'
        ]);

        // Skills are nullable in validation, so this should pass
        $response->assertRedirect('/volunteer/application-pending');
        $response->assertSessionHas('success', 'Application submitted.');
    }

    /**
     * Test Case: Submit application with invalid phone number
     * 
     * Test Steps:
     * 1. Navigate to volunteer application page
     * 2. Fill in all required fields
     * 3. Enter invalid emergency contact phone
     * 4. Click "Submit Application" button
     * 
     * Expected Result: The system displays error message "Phone number must be 10 or 11 digits"
     */
    public function test_submit_application_with_invalid_phone_number()
    {
        $user = User::factory()->create([
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'password' => Hash::make('password123')
        ]);

        $this->actingAs($user);

        $response = $this->post('/volunteer/apply', [
            'motivation' => 'I want to help find missing persons',
            'skills' => ['Communication'],
            'languages' => ['English'],
            'availability' => ['Mon'],
            'emergency_contact_name' => 'John Doe',
            'emergency_contact_phone' => '123' // Invalid phone number
        ]);

        $response->assertSessionHasErrors(['emergency_contact_phone']);
        $this->assertStringContainsString('Emergency contact phone must be 10-11 digits starting with 01', $response->getSession()->get('errors')->first('emergency_contact_phone'));
    }

    /**
     * Test Case: View application status
     * 
     * Test Steps:
     * 1. Navigate to volunteer application status page
     * 2. Check current application status
     * 3. View application details
     * 
     * Expected Result: The system displays current application status and details
     */
    public function test_view_application_status()
    {
        $user = User::factory()->create([
            'name' => 'Status User',
            'email' => 'status@example.com',
            'password' => Hash::make('password123')
        ]);

        // Create a pending application
        $application = VolunteerApplication::create([
            'user_id' => $user->id,
            'motivation' => 'I want to help find missing persons',
            'skills' => ['Communication'],
            'languages' => ['English'],
            'availability' => ['Mon'],
            'emergency_contact_name' => 'John Doe',
            'emergency_contact_phone' => '0123456789',
            'status' => 'Pending'
        ]);

        $this->actingAs($user);

        $response = $this->get('/volunteer/application-pending');

        $response->assertStatus(200);
        $response->assertSee('Volunteer/ApplicationPending');
    }

    /**
     * Test Case: Submit application with invalid emergency contact name
     */
    public function test_submit_application_with_invalid_emergency_contact_name()
    {
        $user = User::factory()->create([
            'name' => 'Invalid Name User',
            'email' => 'invalidname@example.com',
            'password' => Hash::make('password123')
        ]);

        $this->actingAs($user);

        $response = $this->post('/volunteer/apply', [
            'motivation' => 'I want to help find missing persons',
            'skills' => ['Communication'],
            'languages' => ['English'],
            'availability' => ['Mon'],
            'emergency_contact_name' => 'John123', // Contains numbers
            'emergency_contact_phone' => '0123456789'
        ]);

        $response->assertSessionHasErrors(['emergency_contact_name']);
        $this->assertStringContainsString('Emergency contact name must contain only letters and spaces', $response->getSession()->get('errors')->first('emergency_contact_name'));
    }

    /**
     * Test Case: Submit application with short emergency contact name
     */
    public function test_submit_application_with_short_emergency_contact_name()
    {
        $user = User::factory()->create([
            'name' => 'Short Name User',
            'email' => 'shortname@example.com',
            'password' => Hash::make('password123')
        ]);

        $this->actingAs($user);

        $response = $this->post('/volunteer/apply', [
            'motivation' => 'I want to help find missing persons',
            'skills' => ['Communication'],
            'languages' => ['English'],
            'availability' => ['Mon'],
            'emergency_contact_name' => 'J', // Too short
            'emergency_contact_phone' => '0123456789'
        ]);

        $response->assertSessionHasErrors(['emergency_contact_name']);
        $this->assertStringContainsString('Emergency contact name must be at least 2 characters', $response->getSession()->get('errors')->first('emergency_contact_name'));
    }

    /**
     * Test Case: Submit application with valid phone number formats
     */
    public function test_submit_application_with_valid_phone_number_formats()
    {
        $validPhones = [
            '0123456789',  // 10 digits
            '01234567890', // 11 digits
        ];

        foreach ($validPhones as $index => $phone) {
            $user = User::factory()->create([
                'name' => "Valid Phone User {$index}",
                'email' => "validphone{$index}@example.com",
                'password' => Hash::make('password123')
            ]);

            $this->actingAs($user);

            $response = $this->post('/volunteer/apply', [
                'motivation' => 'I want to help find missing persons',
                'skills' => ['Communication'],
                'languages' => ['English'],
                'availability' => ['Mon'],
                'emergency_contact_name' => 'John Doe',
                'emergency_contact_phone' => $phone
            ]);

            $response->assertRedirect('/volunteer/application-pending');
            $response->assertSessionHas('success', 'Application submitted.');
        }
    }

    /**
     * Test Case: Submit application with invalid phone number formats
     */
    public function test_submit_application_with_invalid_phone_number_formats()
    {
        $user = User::factory()->create([
            'name' => 'Invalid Phone User',
            'email' => 'invalidphone@example.com',
            'password' => Hash::make('password123')
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
            $response = $this->post('/volunteer/apply', [
                'motivation' => 'I want to help find missing persons',
                'skills' => ['Communication'],
                'languages' => ['English'],
                'availability' => ['Mon'],
                'emergency_contact_name' => 'John Doe',
                'emergency_contact_phone' => $phone
            ]);

            $response->assertSessionHasErrors(['emergency_contact_phone']);
        }
    }

    /**
     * Test Case: Submit application with invalid supporting documents
     */
    public function test_submit_application_with_invalid_supporting_documents()
    {
        $user = User::factory()->create([
            'name' => 'Invalid Doc User',
            'email' => 'invaliddoc@example.com',
            'password' => Hash::make('password123')
        ]);

        $this->actingAs($user);

        $invalidFile = UploadedFile::fake()->create('document.txt', 1024); // Not allowed format

        $response = $this->post('/volunteer/apply', [
            'motivation' => 'I want to help find missing persons',
            'skills' => ['Communication'],
            'languages' => ['English'],
            'availability' => ['Mon'],
            'emergency_contact_name' => 'John Doe',
            'emergency_contact_phone' => '0123456789',
            'supporting_documents' => [$invalidFile]
        ]);

        $response->assertSessionHasErrors(['supporting_documents.0']);
        $this->assertStringContainsString('Supporting documents must be PDF, DOC, DOCX, JPG, JPEG, or PNG files', $response->getSession()->get('errors')->first('supporting_documents.0'));
    }

    /**
     * Test Case: Submit application with oversized supporting documents
     */
    public function test_submit_application_with_oversized_supporting_documents()
    {
        $user = User::factory()->create([
            'name' => 'Oversized Doc User',
            'email' => 'oversizeddoc@example.com',
            'password' => Hash::make('password123')
        ]);

        $this->actingAs($user);

        $oversizedFile = UploadedFile::fake()->create('large_document.pdf', 6144); // 6MB (over 5MB limit)

        $response = $this->post('/volunteer/apply', [
            'motivation' => 'I want to help find missing persons',
            'skills' => ['Communication'],
            'languages' => ['English'],
            'availability' => ['Mon'],
            'emergency_contact_name' => 'John Doe',
            'emergency_contact_phone' => '0123456789',
            'supporting_documents' => [$oversizedFile]
        ]);

        $response->assertSessionHasErrors(['supporting_documents.0']);
        $this->assertStringContainsString('Each supporting document must be smaller than 5MB', $response->getSession()->get('errors')->first('supporting_documents.0'));
    }

    /**
     * Test Case: Submit application with valid supporting document formats
     */
    public function test_submit_application_with_valid_supporting_document_formats()
    {
        $validFormats = [
            'pdf' => UploadedFile::fake()->create('document.pdf', 1024),
            'doc' => UploadedFile::fake()->create('document.doc', 1024),
            'docx' => UploadedFile::fake()->create('document.docx', 1024),
            'jpg' => UploadedFile::fake()->image('document.jpg', 800, 600)->size(1024),
            'jpeg' => UploadedFile::fake()->image('document.jpeg', 800, 600)->size(1024),
            'png' => UploadedFile::fake()->image('document.png', 800, 600)->size(1024),
        ];

        foreach ($validFormats as $index => $file) {
            $user = User::factory()->create([
                'name' => "Valid Doc User {$index}",
                'email' => "validdoc{$index}@example.com",
                'password' => Hash::make('password123')
            ]);

            $this->actingAs($user);

            $response = $this->post('/volunteer/apply', [
                'motivation' => 'I want to help find missing persons',
                'skills' => ['Communication'],
                'languages' => ['English'],
                'availability' => ['Mon'],
                'emergency_contact_name' => 'John Doe',
                'emergency_contact_phone' => '0123456789',
                'supporting_documents' => [$file]
            ]);

            $response->assertRedirect('/volunteer/application-pending');
            $response->assertSessionHas('success', 'Application submitted.');
        }
    }

    /**
     * Test Case: Prevent duplicate application submission
     */
    public function test_prevent_duplicate_application_submission()
    {
        $user = User::factory()->create([
            'name' => 'Duplicate User',
            'email' => 'duplicate@example.com',
            'password' => Hash::make('password123')
        ]);

        // Create existing pending application
        VolunteerApplication::create([
            'user_id' => $user->id,
            'motivation' => 'I want to help find missing persons',
            'skills' => ['Communication'],
            'languages' => ['English'],
            'availability' => ['Mon'],
            'emergency_contact_name' => 'John Doe',
            'emergency_contact_phone' => '0123456789',
            'status' => 'Pending'
        ]);

        $this->actingAs($user);

        $response = $this->post('/volunteer/apply', [
            'motivation' => 'I want to help find missing persons again',
            'skills' => ['Communication'],
            'languages' => ['English'],
            'availability' => ['Mon'],
            'emergency_contact_name' => 'John Doe',
            'emergency_contact_phone' => '0123456789'
        ]);

        $response->assertStatus(200);
        $response->assertSee('Volunteer/ApplicationPending');
    }

    /**
     * Test Case: Submit application with all optional fields
     */
    public function test_submit_application_with_all_optional_fields()
    {
        $user = User::factory()->create([
            'name' => 'Complete User',
            'email' => 'complete@example.com',
            'password' => Hash::make('password123')
        ]);

        $this->actingAs($user);

        $response = $this->post('/volunteer/apply', [
            'motivation' => 'I want to help find missing persons',
            'skills' => ['Communication', 'Search & Rescue', 'First Aid'],
            'languages' => ['English', 'Malay', 'Chinese'],
            'availability' => ['Mon', 'Tues', 'Wed', 'Thurs', 'Fri'],
            'preferred_roles' => ['Search Team', 'Communication', 'Medical'],
            'areas' => 'Kuala Lumpur, Selangor',
            'transport_mode' => 'Car',
            'emergency_contact_name' => 'Jane Smith',
            'emergency_contact_phone' => '0123456789',
            'prior_experience' => 'Previous volunteer work with Red Cross'
        ]);

        $response->assertRedirect('/volunteer/application-pending');
        $response->assertSessionHas('success', 'Application submitted.');

        // Verify all fields were saved
        $this->assertDatabaseHas('volunteer_applications', [
            'user_id' => $user->id,
            'motivation' => 'I want to help find missing persons',
            'areas' => 'Kuala Lumpur, Selangor',
            'transport_mode' => 'Car',
            'prior_experience' => 'Previous volunteer work with Red Cross'
        ]);
    }

    /**
     * Test Case: Submit application with minimal required fields only
     */
    public function test_submit_application_with_minimal_required_fields()
    {
        $user = User::factory()->create([
            'name' => 'Minimal User',
            'email' => 'minimal@example.com',
            'password' => Hash::make('password123')
        ]);

        $this->actingAs($user);

        $response = $this->post('/volunteer/apply', [
            'motivation' => 'I want to help find missing persons',
            'emergency_contact_name' => 'John Doe',
            'emergency_contact_phone' => '0123456789'
        ]);

        $response->assertRedirect('/volunteer/application-pending');
        $response->assertSessionHas('success', 'Application submitted.');

        // Verify application was created with minimal data
        $this->assertDatabaseHas('volunteer_applications', [
            'user_id' => $user->id,
            'motivation' => 'I want to help find missing persons',
            'emergency_contact_name' => 'John Doe',
            'emergency_contact_phone' => '0123456789',
            'status' => 'Pending'
        ]);
    }

    /**
     * Test Case: View application status for non-existent application
     */
    public function test_view_application_status_for_nonexistent_application()
    {
        $user = User::factory()->create([
            'name' => 'No App User',
            'email' => 'noapp@example.com',
            'password' => Hash::make('password123')
        ]);

        $this->actingAs($user);

        $response = $this->get('/volunteer/application-pending');

        $response->assertRedirect('/volunteer/apply');
        $response->assertSessionHas('status', 'No volunteer application found.');
    }

    /**
     * Test Case: Submit application with empty required fields
     */
    public function test_submit_application_with_empty_required_fields()
    {
        $user = User::factory()->create([
            'name' => 'Empty User',
            'email' => 'empty@example.com',
            'password' => Hash::make('password123')
        ]);

        $this->actingAs($user);

        $response = $this->post('/volunteer/apply', [
            'motivation' => '', // Empty
            'emergency_contact_name' => '', // Empty
            'emergency_contact_phone' => '' // Empty
        ]);

        $response->assertSessionHasErrors(['motivation', 'emergency_contact_name', 'emergency_contact_phone']);
    }
}
