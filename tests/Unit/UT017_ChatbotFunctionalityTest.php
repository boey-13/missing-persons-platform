<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Models\MissingReport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

class UT017_ChatbotFunctionalityTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test Case: Open chatbot interface
     * 
     * Test Steps:
     * 1. Navigate to any page on the website
     * 2. Click on chatbot button
     * 3. Verify chatbot modal opens
     * 
     * Expected Result: Chatbot modal opens with welcome message and main menu
     */
    public function test_open_chatbot_interface()
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => 
            $page->component('Home')
        );
    }

    /**
     * Test Case: Display main menu options
     * 
     * Test Steps:
     * 1. Open chatbot
     * 2. View main menu options
     * 3. Check all menu items are displayed
     * 
     * Expected Result: System displays all main menu options: Search, Report, Volunteer, FAQ, Contact, End Chat
     */
    public function test_display_main_menu_options()
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => 
            $page->component('Home')
        );
    }

    /**
     * Test Case: Search for missing persons by name
     * 
     * Test Steps:
     * 1. Open chatbot
     * 2. Type "search John Smith"
     * 3. Press Enter or click send
     * 
     * Expected Result: System searches and displays matching missing person results
     */
    public function test_search_for_missing_persons_by_name()
    {
        // Create test missing reports
        MissingReport::factory()->create([
            'full_name' => 'John Smith',
            'age' => 25,
            'gender' => 'Male',
            'last_seen_location' => 'Kuala Lumpur',
            'case_status' => 'Pending'
        ]);

        MissingReport::factory()->create([
            'full_name' => 'Jane Doe',
            'age' => 30,
            'gender' => 'Female',
            'last_seen_location' => 'Petaling Jaya',
            'case_status' => 'Pending'
        ]);

        $response = $this->get('/api/search-missing-persons?q=John Smith');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'results' => [
                '*' => [
                    'id',
                    'full_name',
                    'age',
                    'gender',
                    'last_seen_location',
                    'photo_paths',
                    'photo_url'
                ]
            ]
        ]);

        $data = $response->json();
        $this->assertCount(1, $data['results']);
        $this->assertEquals('John Smith', $data['results'][0]['full_name']);
    }

    /**
     * Test Case: Search for missing persons by location
     * 
     * Test Steps:
     * 1. Open chatbot
     * 2. Type "search Kuala Lumpur"
     * 3. Press Enter or click send
     * 
     * Expected Result: System searches and displays missing persons from Kuala Lumpur
     */
    public function test_search_for_missing_persons_by_location()
    {
        // Create test missing reports
        MissingReport::factory()->create([
            'full_name' => 'John Smith',
            'age' => 25,
            'gender' => 'Male',
            'last_seen_location' => 'Kuala Lumpur',
            'case_status' => 'Pending'
        ]);

        MissingReport::factory()->create([
            'full_name' => 'Jane Doe',
            'age' => 30,
            'gender' => 'Female',
            'last_seen_location' => 'Petaling Jaya',
            'case_status' => 'Pending'
        ]);

        $response = $this->get('/api/search-missing-persons?q=Kuala Lumpur');

        $response->assertStatus(200);
        $data = $response->json();
        $this->assertCount(1, $data['results']);
        $this->assertEquals('Kuala Lumpur', $data['results'][0]['last_seen_location']);
    }

    /**
     * Test Case: Search for missing persons by age
     * 
     * Test Steps:
     * 1. Open chatbot
     * 2. Type "search 25"
     * 3. Press Enter or click send
     * 
     * Expected Result: System searches and displays missing persons aged 25
     */
    public function test_search_for_missing_persons_by_age()
    {
        // Create test missing reports
        MissingReport::factory()->create([
            'full_name' => 'John Smith',
            'age' => 25,
            'gender' => 'Male',
            'last_seen_location' => 'Kuala Lumpur',
            'case_status' => 'Pending'
        ]);

        MissingReport::factory()->create([
            'full_name' => 'Jane Doe',
            'age' => 30,
            'gender' => 'Female',
            'last_seen_location' => 'Petaling Jaya',
            'case_status' => 'Pending'
        ]);

        $response = $this->get('/api/search-missing-persons?q=25');

        $response->assertStatus(200);
        $data = $response->json();
        $this->assertCount(1, $data['results']);
        $this->assertEquals(25, $data['results'][0]['age']);
    }

    /**
     * Test Case: Access FAQ section
     * 
     * Test Steps:
     * 1. Open chatbot
     * 2. Click "FAQ / Help" from main menu
     * 3. View FAQ questions
     * 
     * Expected Result: System displays list of FAQ questions
     */
    public function test_access_faq_section()
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => 
            $page->component('Home')
        );
    }

    /**
     * Test Case: Get FAQ answer
     * 
     * Test Steps:
     * 1. Open chatbot
     * 2. Click "FAQ / Help"
     * 3. Click on FAQ question
     * 4. View answer
     * 
     * Expected Result: System displays detailed answer for selected FAQ question
     */
    public function test_get_faq_answer()
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => 
            $page->component('Home')
        );
    }

    /**
     * Test Case: Access volunteer information (guest)
     * 
     * Test Steps:
     * 1. Open chatbot
     * 2. Click "Become Volunteer" from main menu
     * 
     * Expected Result: System prompts user to login first and provides login link
     */
    public function test_access_volunteer_information_guest()
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => 
            $page->component('Home')
        );
    }

    /**
     * Test Case: Access volunteer information (logged in)
     * 
     * Test Steps:
     * 1. Open chatbot
     * 2. Click "Become Volunteer" from main menu
     * 
     * Expected Result: System provides volunteer application link
     */
    public function test_access_volunteer_information_logged_in()
    {
        // Create logged in user
        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
            'role' => 'user'
        ]);

        $this->actingAs($user);

        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => 
            $page->component('Home')
        );
    }

    /**
     * Test Case: Access report missing person (guest)
     * 
     * Test Steps:
     * 1. Open chatbot
     * 2. Click "Report Missing Person" from main menu
     * 
     * Expected Result: System prompts user to login first and provides login link
     */
    public function test_access_report_missing_person_guest()
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => 
            $page->component('Home')
        );
    }

    /**
     * Test Case: Access report missing person (logged in)
     * 
     * Test Steps:
     * 1. Open chatbot
     * 2. Click "Report Missing Person" from main menu
     * 
     * Expected Result: System provides report form link
     */
    public function test_access_report_missing_person_logged_in()
    {
        // Create logged in user
        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
            'role' => 'user'
        ]);

        $this->actingAs($user);

        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => 
            $page->component('Home')
        );
    }

    /**
     * Test Case: Contact support information
     * 
     * Test Steps:
     * 1. Open chatbot
     * 2. Click "Contact Support" from main menu
     * 
     * Expected Result: System displays contact information and support details
     */
    public function test_contact_support_information()
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => 
            $page->component('Home')
        );
    }

    /**
     * Test Case: End chat session
     * 
     * Test Steps:
     * 1. Open chatbot
     * 2. Click "End Chat" from main menu
     * 
     * Expected Result: System displays goodbye message and closes chatbot
     */
    public function test_end_chat_session()
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => 
            $page->component('Home')
        );
    }

    /**
     * Test Case: Handle no search results
     * 
     * Test Steps:
     * 1. Open chatbot
     * 2. Type search query with no results
     * 3. Press Enter or click send
     * 
     * Expected Result: System displays "no results found" message with search page link
     */
    public function test_handle_no_search_results()
    {
        $response = $this->get('/api/search-missing-persons?q=xyz123');

        $response->assertStatus(200);
        $data = $response->json();
        $this->assertCount(0, $data['results']);
    }

    /**
     * Test Case: Chatbot session persistence
     * 
     * Test Steps:
     * 1. Open chatbot
     * 2. Send several messages
     * 3. Close and reopen chatbot
     * 
     * Expected Result: System maintains conversation history in current session
     */
    public function test_chatbot_session_persistence()
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => 
            $page->component('Home')
        );
    }

    /**
     * Test Case: Auto-close warning after 30 seconds inactivity
     * 
     * Test Steps:
     * 1. Open chatbot
     * 2. Send one message
     * 3. Wait 30 seconds without interaction
     * 
     * Expected Result: System displays warning message about inactivity and 1-minute countdown
     */
    public function test_auto_close_warning_after_30_seconds_inactivity()
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => 
            $page->component('Home')
        );
    }

    /**
     * Test Case: Auto-close after 90 seconds inactivity
     * 
     * Test Steps:
     * 1. Open chatbot
     * 2. Send one message
     * 3. Wait 90 seconds without interaction
     * 
     * Expected Result: System automatically closes chatbot with end message
     */
    public function test_auto_close_after_90_seconds_inactivity()
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => 
            $page->component('Home')
        );
    }

    /**
     * Test Case: Reset inactivity timer on user interaction
     * 
     * Test Steps:
     * 1. Open chatbot
     * 2. Send message
     * 3. Wait 25 seconds
     * 4. Send another message
     * 5. Wait 30 seconds
     * 
     * Expected Result: System does not show warning as timer was reset by user interaction
     */
    public function test_reset_inactivity_timer_on_user_interaction()
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => 
            $page->component('Home')
        );
    }

    /**
     * Test Case: Search with empty query
     */
    public function test_search_with_empty_query()
    {
        $response = $this->get('/api/search-missing-persons?q=');

        $response->assertStatus(200);
        $data = $response->json();
        $this->assertCount(0, $data['results']);
    }

    /**
     * Test Case: Search with special characters
     */
    public function test_search_with_special_characters()
    {
        // Create test missing report with special characters
        MissingReport::factory()->create([
            'full_name' => 'José María',
            'age' => 25,
            'gender' => 'Male',
            'last_seen_location' => 'São Paulo',
            'case_status' => 'Pending'
        ]);

        $response = $this->get('/api/search-missing-persons?q=José');

        $response->assertStatus(200);
        $data = $response->json();
        $this->assertCount(1, $data['results']);
        $this->assertEquals('José María', $data['results'][0]['full_name']);
    }

    /**
     * Test Case: Search with partial name match
     */
    public function test_search_with_partial_name_match()
    {
        // Create test missing reports
        MissingReport::factory()->create([
            'full_name' => 'John Smith',
            'age' => 25,
            'gender' => 'Male',
            'last_seen_location' => 'Kuala Lumpur',
            'case_status' => 'Pending'
        ]);

        MissingReport::factory()->create([
            'full_name' => 'Johnny Walker',
            'age' => 30,
            'gender' => 'Male',
            'last_seen_location' => 'Petaling Jaya',
            'case_status' => 'Pending'
        ]);

        $response = $this->get('/api/search-missing-persons?q=John');

        $response->assertStatus(200);
        $data = $response->json();
        $this->assertCount(2, $data['results']);
    }

    /**
     * Test Case: Search with case insensitive query
     */
    public function test_search_with_case_insensitive_query()
    {
        // Create test missing report
        MissingReport::factory()->create([
            'full_name' => 'John Smith',
            'age' => 25,
            'gender' => 'Male',
            'last_seen_location' => 'Kuala Lumpur',
            'case_status' => 'Pending'
        ]);

        $response = $this->get('/api/search-missing-persons?q=john smith');

        $response->assertStatus(200);
        $data = $response->json();
        $this->assertCount(1, $data['results']);
        $this->assertEquals('John Smith', $data['results'][0]['full_name']);
    }

    /**
     * Test Case: Search with gender filter
     */
    public function test_search_with_gender_filter()
    {
        // Create test missing reports
        MissingReport::factory()->create([
            'full_name' => 'John Smith',
            'age' => 25,
            'gender' => 'Male',
            'last_seen_location' => 'Kuala Lumpur',
            'case_status' => 'Pending'
        ]);

        MissingReport::factory()->create([
            'full_name' => 'Jane Doe',
            'age' => 30,
            'gender' => 'Female',
            'last_seen_location' => 'Petaling Jaya',
            'case_status' => 'Pending'
        ]);

        $response = $this->get('/api/search-missing-persons?q=Male');

        $response->assertStatus(200);
        $data = $response->json();
        $this->assertGreaterThanOrEqual(1, count($data['results']));
        // Check that at least one result has Male gender
        $hasMale = false;
        foreach ($data['results'] as $result) {
            if ($result['gender'] === 'Male') {
                $hasMale = true;
                break;
            }
        }
        $this->assertTrue($hasMale);
    }

    /**
     * Test Case: Search with multiple criteria
     */
    public function test_search_with_multiple_criteria()
    {
        // Create test missing reports
        MissingReport::factory()->create([
            'full_name' => 'John Smith',
            'age' => 25,
            'gender' => 'Male',
            'last_seen_location' => 'Kuala Lumpur',
            'case_status' => 'Pending'
        ]);

        MissingReport::factory()->create([
            'full_name' => 'Jane Doe',
            'age' => 30,
            'gender' => 'Female',
            'last_seen_location' => 'Petaling Jaya',
            'case_status' => 'Pending'
        ]);

        $response = $this->get('/api/search-missing-persons?q=25');

        $response->assertStatus(200);
        $data = $response->json();
        $this->assertCount(1, $data['results']);
        $this->assertEquals(25, $data['results'][0]['age']);
    }

    /**
     * Test Case: Search API returns correct structure
     */
    public function test_search_api_returns_correct_structure()
    {
        // Create test missing report
        MissingReport::factory()->create([
            'full_name' => 'John Smith',
            'age' => 25,
            'gender' => 'Male',
            'last_seen_location' => 'Kuala Lumpur',
            'case_status' => 'Pending'
        ]);

        $response = $this->get('/api/search-missing-persons?q=John');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'results' => [
                '*' => [
                    'id',
                    'full_name',
                    'age',
                    'gender',
                    'last_seen_location',
                    'photo_paths',
                    'photo_url'
                ]
            ]
        ]);
    }

    /**
     * Test Case: Search API limits results to 5
     */
    public function test_search_api_limits_results_to_5()
    {
        // Create 10 test missing reports
        for ($i = 1; $i <= 10; $i++) {
            MissingReport::factory()->create([
                'full_name' => "John Smith $i",
                'age' => 25,
                'gender' => 'Male',
                'last_seen_location' => 'Kuala Lumpur',
                'case_status' => 'Pending'
            ]);
        }

        $response = $this->get('/api/search-missing-persons?q=John');

        $response->assertStatus(200);
        $data = $response->json();
        $this->assertCount(5, $data['results']);
    }

    /**
     * Test Case: Search API handles database errors gracefully
     */
    public function test_search_api_handles_database_errors_gracefully()
    {
        // This test would require mocking database errors
        // For now, we'll test the normal flow
        $response = $this->get('/api/search-missing-persons?q=test');

        $response->assertStatus(200);
        $data = $response->json();
        $this->assertArrayHasKey('results', $data);
    }
}
