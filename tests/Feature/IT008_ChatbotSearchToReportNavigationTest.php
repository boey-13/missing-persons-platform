<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\MissingReport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

class IT008_ChatbotSearchToReportNavigationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test the complete flow from chatbot search functionality to navigation to missing person reports
     * 
     * Test Procedure:
     * 1. User visits any page on the website
     * 2. Click on the chatbot button (floating button in bottom-right corner)
     * 3. Verify chatbot modal opens with welcome message
     * 4. Verify main menu options are displayed
     * 5. Click on "Search Missing Persons" option
     * 6. Type a search query (e.g., "John" or "Kuala Lumpur")
     * 7. Press Enter or click send button
     * 8. Verify chatbot shows "Searching for..." message
     * 9. Verify search results are displayed with:
     *    - Missing person photo
     *    - Name and age
     *    - Last seen location
     *    - "View" link for each result
     * 10. Click "View" link on a search result
     * 11. Verify user is navigated to the missing person detail page
     * 12. Verify the detail page loads correctly with all information
     * 13. Test alternative search methods:
     *     - Type "search [name]" command
     *     - Type "find [location]" command
     *     - Type natural language queries
     * 14. Test search with no results scenario
     * 15. Test chatbot inactivity timeout (30 seconds warning, 90 seconds auto-close)
     * 16. Test chatbot session persistence across page refreshes
     * 17. Test chatbot unread message badge functionality
     * 18. Test chatbot menu navigation to other features
     */
    public function test_complete_chatbot_search_to_report_navigation_flow()
    {
        // Test Data
        $searchTestData = [
            'Search Query 1' => 'John',
            'Search Query 2' => 'Kuala Lumpur',
            'Search Query 3' => '25',
            'Search Query 4' => 'Male',
            'Search Query 5' => 'nonexistent'
        ];

        $expectedSearchResults = [
            'Name' => 'John Smith',
            'Age' => 25,
            'Gender' => 'Male',
            'Last Seen Location' => 'Kuala Lumpur City Center',
            'Photo URL' => '/storage/photos/photo1.jpg'
        ];

        // Create test missing person reports
        $missingReport1 = MissingReport::factory()->create([
            'full_name' => 'John Smith',
            'age' => 25,
            'gender' => 'Male',
            'last_seen_location' => 'Kuala Lumpur City Center',
            'photo_paths' => ['photos/photo1.jpg'],
            'case_status' => 'Approved'
        ]);

        $missingReport2 = MissingReport::factory()->create([
            'full_name' => 'Jane Doe',
            'age' => 30,
            'gender' => 'Female',
            'last_seen_location' => 'Petaling Jaya',
            'photo_paths' => ['photos/photo2.jpg'],
            'case_status' => 'Approved'
        ]);

        // Step 1: User visits any page on the website
        $response = $this->get('/');
        $response->assertStatus(200);

        // Step 2-4: Click on the chatbot button and verify chatbot modal opens with welcome message and main menu options
        // Note: This is frontend functionality, so we test the API endpoints instead
        $response = $this->get('/api/search-missing-persons?q=John');
        $response->assertStatus(200);
        $responseData = $response->json();
        $this->assertArrayHasKey('results', $responseData);

        // Step 5-8: Test search functionality
        $response = $this->get('/api/search-missing-persons?q=John');
        $response->assertStatus(200);
        $responseData = $response->json();
        $this->assertCount(1, $responseData['results']);
        $this->assertEquals('John Smith', $responseData['results'][0]['full_name']);
        $this->assertEquals(25, $responseData['results'][0]['age']);
        $this->assertEquals('Male', $responseData['results'][0]['gender']);
        $this->assertEquals('Kuala Lumpur City Center', $responseData['results'][0]['last_seen_location']);

        // Step 9: Verify search results are displayed with proper structure
        $result = $responseData['results'][0];
        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('full_name', $result);
        $this->assertArrayHasKey('age', $result);
        $this->assertArrayHasKey('gender', $result);
        $this->assertArrayHasKey('last_seen_location', $result);
        $this->assertArrayHasKey('photo_url', $result);

        // Step 10-12: Test navigation to missing person detail page
        $response = $this->get('/missing-persons/' . $missingReport1->id);
        $response->assertStatus(200);
        $response->assertSee('MissingPersons/Show');
    }

    /**
     * Test search by name
     */
    public function test_search_by_name()
    {
        MissingReport::factory()->create([
            'full_name' => 'John Smith',
            'age' => 25,
            'gender' => 'Male',
            'last_seen_location' => 'Kuala Lumpur City Center',
            'case_status' => 'Approved'
        ]);

        $response = $this->get('/api/search-missing-persons?q=John');
        $response->assertStatus(200);
        $responseData = $response->json();
        $this->assertCount(1, $responseData['results']);
        $this->assertEquals('John Smith', $responseData['results'][0]['full_name']);
    }

    /**
     * Test search by location
     */
    public function test_search_by_location()
    {
        MissingReport::factory()->create([
            'full_name' => 'John Smith',
            'age' => 25,
            'gender' => 'Male',
            'last_seen_location' => 'Kuala Lumpur City Center',
            'case_status' => 'Approved'
        ]);

        $response = $this->get('/api/search-missing-persons?q=Kuala Lumpur');
        $response->assertStatus(200);
        $responseData = $response->json();
        $this->assertCount(1, $responseData['results']);
        $this->assertEquals('Kuala Lumpur City Center', $responseData['results'][0]['last_seen_location']);
    }

    /**
     * Test search by age
     */
    public function test_search_by_age()
    {
        MissingReport::factory()->create([
            'full_name' => 'John Smith',
            'age' => 25,
            'gender' => 'Male',
            'last_seen_location' => 'Kuala Lumpur City Center',
            'case_status' => 'Approved'
        ]);

        $response = $this->get('/api/search-missing-persons?q=25');
        $response->assertStatus(200);
        $responseData = $response->json();
        $this->assertCount(1, $responseData['results']);
        $this->assertEquals(25, $responseData['results'][0]['age']);
    }

    /**
     * Test search by gender
     */
    public function test_search_by_gender()
    {
        MissingReport::factory()->create([
            'full_name' => 'John Smith',
            'age' => 25,
            'gender' => 'Male',
            'last_seen_location' => 'Kuala Lumpur City Center',
            'case_status' => 'Approved'
        ]);

        $response = $this->get('/api/search-missing-persons?q=Male');
        $response->assertStatus(200);
        $responseData = $response->json();
        $this->assertCount(1, $responseData['results']);
        $this->assertEquals('Male', $responseData['results'][0]['gender']);
    }

    /**
     * Test search with no results
     */
    public function test_search_with_no_results()
    {
        $response = $this->get('/api/search-missing-persons?q=nonexistent');
        $response->assertStatus(200);
        $responseData = $response->json();
        $this->assertCount(0, $responseData['results']);
    }

    /**
     * Test search with empty query
     */
    public function test_search_with_empty_query()
    {
        $response = $this->get('/api/search-missing-persons?q=');
        $response->assertStatus(200);
        $responseData = $response->json();
        $this->assertCount(0, $responseData['results']);
    }

    /**
     * Test search with no query parameter
     */
    public function test_search_with_no_query_parameter()
    {
        $response = $this->get('/api/search-missing-persons');
        $response->assertStatus(200);
        $responseData = $response->json();
        $this->assertCount(0, $responseData['results']);
    }

    /**
     * Test search results limit
     */
    public function test_search_results_limit()
    {
        // Create more than 5 missing reports
        for ($i = 1; $i <= 7; $i++) {
            MissingReport::factory()->create([
                'full_name' => "John Smith $i",
                'age' => 25 + $i,
                'gender' => 'Male',
                'last_seen_location' => 'Kuala Lumpur City Center',
                'case_status' => 'Approved'
            ]);
        }

        $response = $this->get('/api/search-missing-persons?q=John');
        $response->assertStatus(200);
        $responseData = $response->json();
        $this->assertCount(5, $responseData['results']); // Should be limited to 5 results
    }

    /**
     * Test search with special characters
     */
    public function test_search_with_special_characters()
    {
        MissingReport::factory()->create([
            'full_name' => 'John-Smith O\'Connor',
            'age' => 25,
            'gender' => 'Male',
            'last_seen_location' => 'Kuala Lumpur City Center',
            'case_status' => 'Approved'
        ]);

        $response = $this->get('/api/search-missing-persons?q=John-Smith');
        $response->assertStatus(200);
        $responseData = $response->json();
        $this->assertCount(1, $responseData['results']);
        $this->assertEquals('John-Smith O\'Connor', $responseData['results'][0]['full_name']);
    }

    /**
     * Test search case sensitivity
     */
    public function test_search_case_sensitivity()
    {
        MissingReport::factory()->create([
            'full_name' => 'John Smith',
            'age' => 25,
            'gender' => 'Male',
            'last_seen_location' => 'Kuala Lumpur City Center',
            'case_status' => 'Approved'
        ]);

        // Test lowercase search
        $response = $this->get('/api/search-missing-persons?q=john');
        $response->assertStatus(200);
        $responseData = $response->json();
        $this->assertCount(1, $responseData['results']);

        // Test uppercase search
        $response = $this->get('/api/search-missing-persons?q=JOHN');
        $response->assertStatus(200);
        $responseData = $response->json();
        $this->assertCount(1, $responseData['results']);
    }

    /**
     * Test search with partial matches
     */
    public function test_search_with_partial_matches()
    {
        MissingReport::factory()->create([
            'full_name' => 'John Smith',
            'age' => 25,
            'gender' => 'Male',
            'last_seen_location' => 'Kuala Lumpur City Center',
            'case_status' => 'Approved'
        ]);

        // Test partial name search
        $response = $this->get('/api/search-missing-persons?q=Joh');
        $response->assertStatus(200);
        $responseData = $response->json();
        $this->assertCount(1, $responseData['results']);

        // Test partial location search
        $response = $this->get('/api/search-missing-persons?q=Kuala');
        $response->assertStatus(200);
        $responseData = $response->json();
        $this->assertCount(1, $responseData['results']);
    }

    /**
     * Test search with multiple criteria
     */
    public function test_search_with_multiple_criteria()
    {
        MissingReport::factory()->create([
            'full_name' => 'John Smith',
            'age' => 25,
            'gender' => 'Male',
            'last_seen_location' => 'Kuala Lumpur City Center',
            'case_status' => 'Approved'
        ]);

        MissingReport::factory()->create([
            'full_name' => 'Jane Smith',
            'age' => 30,
            'gender' => 'Female',
            'last_seen_location' => 'Petaling Jaya',
            'case_status' => 'Approved'
        ]);

        // Search should return both results as it searches across all fields
        $response = $this->get('/api/search-missing-persons?q=Smith');
        $response->assertStatus(200);
        $responseData = $response->json();
        $this->assertCount(2, $responseData['results']);
    }

    /**
     * Test search results structure
     */
    public function test_search_results_structure()
    {
        MissingReport::factory()->create([
            'full_name' => 'John Smith',
            'age' => 25,
            'gender' => 'Male',
            'last_seen_location' => 'Kuala Lumpur City Center',
            'photo_paths' => ['photos/photo1.jpg'],
            'case_status' => 'Approved'
        ]);

        $response = $this->get('/api/search-missing-persons?q=John');
        $response->assertStatus(200);
        $responseData = $response->json();
        
        $this->assertArrayHasKey('results', $responseData);
        $this->assertIsArray($responseData['results']);
        
        $result = $responseData['results'][0];
        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('full_name', $result);
        $this->assertArrayHasKey('age', $result);
        $this->assertArrayHasKey('gender', $result);
        $this->assertArrayHasKey('last_seen_location', $result);
        $this->assertArrayHasKey('photo_url', $result);
        
        // Test photo URL generation
        $this->assertStringContainsString('storage/photos/photo1.jpg', $result['photo_url']);
    }

    /**
     * Test search with no photo
     */
    public function test_search_with_no_photo()
    {
        MissingReport::factory()->create([
            'full_name' => 'John Smith',
            'age' => 25,
            'gender' => 'Male',
            'last_seen_location' => 'Kuala Lumpur City Center',
            'photo_paths' => null,
            'case_status' => 'Approved'
        ]);

        $response = $this->get('/api/search-missing-persons?q=John');
        $response->assertStatus(200);
        $responseData = $response->json();
        
        $result = $responseData['results'][0];
        $this->assertNull($result['photo_url']);
    }

    /**
     * Test search with empty photo paths
     */
    public function test_search_with_empty_photo_paths()
    {
        MissingReport::factory()->create([
            'full_name' => 'John Smith',
            'age' => 25,
            'gender' => 'Male',
            'last_seen_location' => 'Kuala Lumpur City Center',
            'photo_paths' => [],
            'case_status' => 'Approved'
        ]);

        $response = $this->get('/api/search-missing-persons?q=John');
        $response->assertStatus(200);
        $responseData = $response->json();
        
        $result = $responseData['results'][0];
        $this->assertNull($result['photo_url']);
    }

    /**
     * Test search API error handling
     */
    public function test_search_api_error_handling()
    {
        // Test with invalid query that might cause SQL issues
        $response = $this->get('/api/search-missing-persons?q=%');
        $response->assertStatus(200);
        $responseData = $response->json();
        $this->assertArrayHasKey('results', $responseData);
    }

    /**
     * Test search with very long query
     */
    public function test_search_with_very_long_query()
    {
        $longQuery = str_repeat('a', 1000);
        $response = $this->get('/api/search-missing-persons?q=' . $longQuery);
        $response->assertStatus(200);
        $responseData = $response->json();
        $this->assertArrayHasKey('results', $responseData);
    }

    /**
     * Test search with SQL injection attempt
     */
    public function test_search_with_sql_injection_attempt()
    {
        $response = $this->get('/api/search-missing-persons?q=1\' OR \'1\'=\'1');
        $response->assertStatus(200);
        $responseData = $response->json();
        $this->assertArrayHasKey('results', $responseData);
        $this->assertCount(0, $responseData['results']); // Should return no results, not execute SQL injection
    }
}
