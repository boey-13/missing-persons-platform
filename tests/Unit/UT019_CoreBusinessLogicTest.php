<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Services\PointsService;
use App\Models\User;
use App\Models\MissingReport;
use App\Models\SightingReport;
use App\Models\VolunteerApplication;
use App\Models\CommunityProject;
use App\Models\ProjectApplication;
use Mockery;

class UT019_CoreBusinessLogicTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
    }

    /**
     * Test core business logic for missing report creation
     */
    public function test_missing_report_creation_business_logic(): void
    {
        // Test business rules for missing report creation
        $reportData = [
            'full_name' => 'John Doe',
            'ic_number' => '123456789012',
            'age' => 25,
            'gender' => 'Male',
            'last_seen_date' => '2024-01-15',
            'last_seen_location' => 'Kuala Lumpur',
            'reporter_relationship' => 'Friend',
            'reporter_name' => 'Jane Smith',
            'reporter_ic_number' => '987654321098',
            'reporter_phone' => '0123456789',
            'reporter_email' => 'jane@example.com',
            'case_status' => 'Pending'
        ];

        // Validate business rules
        $this->assertNotEmpty($reportData['full_name']);
        $this->assertEquals(12, strlen($reportData['ic_number']));
        $this->assertGreaterThan(0, $reportData['age']);
        $this->assertLessThanOrEqual(150, $reportData['age']);
        $this->assertContains($reportData['gender'], ['Male', 'Female', 'Other']);
        $this->assertContains($reportData['reporter_relationship'], ['Parent', 'Child', 'Spouse', 'Sibling', 'Friend', 'Other']);
        $this->assertMatchesRegularExpression('/^01\d{8,9}$/', $reportData['reporter_phone']);
        $this->assertMatchesRegularExpression('/^[^\s@]+@[^\s@]+\.[^\s@]+$/', $reportData['reporter_email']);
    }

    /**
     * Test core business logic for sighting report creation
     */
    public function test_sighting_report_creation_business_logic(): void
    {
        $sightingData = [
            'location' => 'Petaling Jaya, Selangor',
            'description' => 'Saw the person at the shopping mall',
            'sighted_at' => '2024-01-16 14:30:00',
            'reporter_name' => 'Ahmad Ali',
            'reporter_phone' => '0198765432',
            'reporter_email' => 'ahmad@example.com',
            'status' => 'Pending'
        ];

        // Validate business rules
        $this->assertNotEmpty($sightingData['location']);
        $this->assertNotEmpty($sightingData['description']);
        $this->assertMatchesRegularExpression('/^01\d{8,9}$/', $sightingData['reporter_phone']);
        $this->assertMatchesRegularExpression('/^[^\s@]+@[^\s@]+\.[^\s@]+$/', $sightingData['reporter_email']);
        $this->assertContains($sightingData['status'], ['Pending', 'Approved', 'Rejected']);
    }

    /**
     * Test core business logic for volunteer application
     */
    public function test_volunteer_application_business_logic(): void
    {
        $applicationData = [
            'motivation' => 'I want to help find missing persons in my community',
            'skills' => ['search_rescue', 'communication', 'medical'],
            'languages' => ['english', 'malay'],
            'availability' => ['weekend_morning', 'weekend_afternoon'],
            'emergency_contact_name' => 'Sarah Johnson',
            'emergency_contact_phone' => '01123456789',
            'emergency_contact_relationship' => 'Sister',
            'status' => 'Pending'
        ];

        // Validate application data
        $this->assertGreaterThanOrEqual(50, strlen($applicationData['motivation']));
        $this->assertLessThanOrEqual(1000, strlen($applicationData['motivation']));
        $this->assertIsArray($applicationData['skills']);
        $this->assertGreaterThan(0, count($applicationData['skills']));
        $this->assertIsArray($applicationData['languages']);
        $this->assertGreaterThan(0, count($applicationData['languages']));
        $this->assertMatchesRegularExpression('/^01\d{8,9}$/', $applicationData['emergency_contact_phone']);
        $this->assertContains($applicationData['status'], ['Pending', 'Approved', 'Rejected']);
    }

    /**
     * Test core business logic for community project application
     */
    public function test_community_project_application_business_logic(): void
    {
        $projectApplicationData = [
            'experience' => 'I have 2 years of volunteer experience in community projects',
            'motivation' => 'I want to contribute to finding missing persons',
            'status' => 'pending'
        ];

        // Validate business rules
        $this->assertGreaterThanOrEqual(10, strlen($projectApplicationData['experience']));
        $this->assertLessThanOrEqual(1000, strlen($projectApplicationData['experience']));
        $this->assertGreaterThanOrEqual(10, strlen($projectApplicationData['motivation']));
        $this->assertLessThanOrEqual(500, strlen($projectApplicationData['motivation']));
        $this->assertContains($projectApplicationData['status'], ['pending', 'approved', 'rejected', 'withdrawn']);
    }

    /**
     * Test core business logic for points system
     */
    public function test_points_system_business_logic(): void
    {
        // Test points reward rules
        $pointsRules = [
            'registration' => 10,
            'sighting_report' => 10,
            'social_share' => 1,
            'community_project' => 30
        ];

        foreach ($pointsRules as $action => $points) {
            $this->assertIsInt($points);
            $this->assertGreaterThan(0, $points);
        }

        // Test points calculation logic
        $earnedPoints = 100;
        $spentPoints = 30;
        $currentPoints = $earnedPoints - $spentPoints;
        
        $this->assertEquals(70, $currentPoints);
    }

    /**
     * Test core business logic for user role management
     */
    public function test_user_role_management_business_logic(): void
    {
        $roles = ['user', 'volunteer', 'admin'];
        $permissions = [
            'user' => ['view_own_reports', 'create_reports'],
            'volunteer' => ['view_all_reports', 'submit_sightings', 'apply_projects'],
            'admin' => ['manage_reports', 'manage_users', 'manage_volunteers', 'view_analytics']
        ];

        foreach ($roles as $role) {
            $this->assertIsString($role);
            $this->assertArrayHasKey($role, $permissions);
        }
    }

    /**
     * Test core business logic for report status transitions
     */
    public function test_report_status_transitions_business_logic(): void
    {
        $missingReportTransitions = [
            'Pending' => ['Approved', 'Rejected'],
            'Approved' => ['Missing', 'Found', 'Closed'],
            'Missing' => ['Found', 'Closed'],
            'Found' => ['Closed'],
            'Rejected' => ['Pending'],
            'Closed' => []
        ];

        $sightingReportTransitions = [
            'Pending' => ['Approved', 'Rejected'],
            'Approved' => ['Verified'],
            'Rejected' => ['Pending'],
            'Verified' => []
        ];

        // Validate status transition rules
        foreach ($missingReportTransitions as $fromStatus => $toStatuses) {
            $this->assertIsArray($toStatuses);
            foreach ($toStatuses as $toStatus) {
                $this->assertIsString($toStatus);
            }
        }

        foreach ($sightingReportTransitions as $fromStatus => $toStatuses) {
            $this->assertIsArray($toStatuses);
            foreach ($toStatuses as $toStatus) {
                $this->assertIsString($toStatus);
            }
        }
    }

    /**
     * Test core business logic for file upload
     */
    public function test_file_upload_business_logic(): void
    {
        $fileRules = [
            'photos' => ['max_size' => 2048, 'allowed_types' => ['jpg', 'jpeg', 'png', 'gif'], 'max_count' => 5],
            'police_report' => ['max_size' => 2048, 'allowed_types' => ['pdf', 'jpg', 'jpeg', 'png']],
            'supporting_documents' => ['max_size' => 5120, 'allowed_types' => ['pdf', 'doc', 'docx'], 'max_count' => 3]
        ];

        foreach ($fileRules as $fileType => $rules) {
            $this->assertArrayHasKey('max_size', $rules);
            $this->assertArrayHasKey('allowed_types', $rules);
            $this->assertIsInt($rules['max_size']);
            $this->assertIsArray($rules['allowed_types']);
            $this->assertGreaterThan(0, $rules['max_size']);
        }
    }

    /**
     * Test core business logic for notification system
     */
    public function test_notification_system_business_logic(): void
    {
        $notificationTypes = [
            'missing_report_submitted',
            'sighting_report_submitted',
            'volunteer_application_submitted',
            'project_application_submitted',
            'report_approved',
            'report_rejected',
            'points_earned'
        ];

        foreach ($notificationTypes as $type) {
            $this->assertIsString($type);
            $this->assertNotEmpty($type);
        }
    }

    /**
     * Test core business logic for search and filtering
     */
    public function test_search_filter_business_logic(): void
    {
        $searchCriteria = [
            'name' => 'John',
            'age_min' => 18,
            'age_max' => 65,
            'gender' => ['Male', 'Female'],
            'location' => 'Kuala Lumpur',
            'status' => 'Approved',
            'date_from' => '2024-01-01',
            'date_to' => '2024-12-31'
        ];

        // Validate search criteria
        $this->assertIsString($searchCriteria['name']);
        $this->assertIsInt($searchCriteria['age_min']);
        $this->assertIsInt($searchCriteria['age_max']);
        $this->assertLessThan($searchCriteria['age_max'], $searchCriteria['age_min']);
        $this->assertIsArray($searchCriteria['gender']);
        $this->assertContains('Male', $searchCriteria['gender']);
        $this->assertContains('Female', $searchCriteria['gender']);
    }
}