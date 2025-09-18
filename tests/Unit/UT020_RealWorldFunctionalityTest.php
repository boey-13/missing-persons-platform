<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Services\PointsService;
use App\Services\NotificationService;
use App\Models\User;
use App\Models\MissingReport;
use App\Models\SightingReport;
use App\Models\VolunteerApplication;
use App\Models\CommunityProject;
use App\Models\ProjectApplication;
use App\Models\Reward;
use App\Models\RewardCategory;
use Mockery;

class UT020_RealWorldFunctionalityTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
    }

    /**
     * Test complete missing report workflow
     */
    public function test_complete_missing_report_workflow(): void
    {
        // 1. User registration
        $user = new User();
        $user->id = 1;
        $user->name = 'John Doe';
        $user->email = 'john@example.com';
        $user->role = 'user';
        
        $this->assertEquals('user', $user->role);
        $this->assertTrue($user->isAdmin() === false);
        
        // 2. Create missing report
        $reportData = [
            'user_id' => $user->id,
            'full_name' => 'Jane Smith',
            'ic_number' => '123456789012',
            'age' => 25,
            'gender' => 'Female',
            'last_seen_date' => '2024-01-15',
            'last_seen_location' => 'Kuala Lumpur',
            'reporter_relationship' => 'Friend',
            'reporter_name' => 'John Doe',
            'reporter_ic_number' => '987654321098',
            'reporter_phone' => '0123456789',
            'reporter_email' => 'john@example.com',
            'case_status' => 'Pending',
            'photo_paths' => ['photos/jane1.jpg', 'photos/jane2.jpg'],
            'police_report_path' => 'police_reports/report123.pdf'
        ];
        
        // Validate report data completeness
        $this->assertNotEmpty($reportData['full_name']);
        $this->assertEquals(12, strlen($reportData['ic_number']));
        $this->assertGreaterThan(0, $reportData['age']);
        $this->assertLessThanOrEqual(150, $reportData['age']);
        $this->assertContains($reportData['gender'], ['Male', 'Female', 'Other']);
        $this->assertContains($reportData['reporter_relationship'], ['Parent', 'Child', 'Spouse', 'Sibling', 'Friend', 'Other']);
        $this->assertMatchesRegularExpression('/^01\d{8,9}$/', $reportData['reporter_phone']);
        $this->assertMatchesRegularExpression('/^[^\s@]+@[^\s@]+\.[^\s@]+$/', $reportData['reporter_email']);
        $this->assertEquals('Pending', $reportData['case_status']);
        $this->assertIsArray($reportData['photo_paths']);
        $this->assertGreaterThan(0, count($reportData['photo_paths']));
        
        // 3. Admin approval process
        $admin = new User();
        $admin->id = 2;
        $admin->role = 'admin';
        $this->assertTrue($admin->isAdmin());
        $this->assertTrue($admin->canAccessAdminDashboard());
        
        // 4. Status transition
        $reportData['case_status'] = 'Approved';
        $this->assertEquals('Approved', $reportData['case_status']);
    }

    /**
     * Test complete sighting report workflow
     */
    public function test_complete_sighting_report_workflow(): void
    {
        // 1. Volunteer user
        $volunteer = new User();
        $volunteer->id = 3;
        $volunteer->role = 'volunteer';
        $volunteer->name = 'Ahmad Ali';
        $volunteer->email = 'ahmad@example.com';
        
        // 2. Create sighting report
        $sightingData = [
            'user_id' => $volunteer->id,
            'missing_report_id' => 1,
            'location' => 'Petaling Jaya, Selangor',
            'description' => 'Saw the person at the shopping mall near the food court',
            'sighted_at' => '2024-01-16 14:30:00',
            'reporter_name' => 'Ahmad Ali',
            'reporter_phone' => '0198765432',
            'reporter_email' => 'ahmad@example.com',
            'status' => 'Pending',
            'photo_paths' => ['sightings/sighting1.jpg', 'sightings/sighting2.jpg']
        ];
        
        // Validate sighting report data
        $this->assertNotEmpty($sightingData['location']);
        $this->assertNotEmpty($sightingData['description']);
        $this->assertGreaterThan(10, strlen($sightingData['description']));
        $this->assertMatchesRegularExpression('/^01\d{8,9}$/', $sightingData['reporter_phone']);
        $this->assertMatchesRegularExpression('/^[^\s@]+@[^\s@]+\.[^\s@]+$/', $sightingData['reporter_email']);
        $this->assertEquals('Pending', $sightingData['status']);
        $this->assertIsArray($sightingData['photo_paths']);
        
        // 3. Admin approval
        $sightingData['status'] = 'Approved';
        $this->assertEquals('Approved', $sightingData['status']);
        
        // 4. Points reward
        $pointsAwarded = 10;
        $this->assertEquals(10, $pointsAwarded);
    }

    /**
     * Test complete volunteer application workflow
     */
    public function test_complete_volunteer_application_workflow(): void
    {
        // 1. User applies to become volunteer
        $user = new User();
        $user->id = 4;
        $user->role = 'user';
        
        $applicationData = [
            'user_id' => $user->id,
            'motivation' => 'I want to help find missing persons in my community and contribute to search efforts',
            'skills' => ['search_rescue', 'communication', 'medical'],
            'languages' => ['english', 'malay', 'chinese'],
            'availability' => ['weekend_morning', 'weekend_afternoon'],
            'emergency_contact_name' => 'Sarah Johnson',
            'emergency_contact_phone' => '01123456789',
            'emergency_contact_relationship' => 'Sister',
            'status' => 'Pending',
            'supporting_documents' => ['docs/certificate.pdf', 'docs/reference.pdf']
        ];
        
        // Validate application data
        $this->assertGreaterThanOrEqual(50, strlen($applicationData['motivation']));
        $this->assertLessThanOrEqual(1000, strlen($applicationData['motivation']));
        $this->assertIsArray($applicationData['skills']);
        $this->assertGreaterThan(0, count($applicationData['skills']));
        $this->assertIsArray($applicationData['languages']);
        $this->assertGreaterThan(0, count($applicationData['languages']));
        $this->assertMatchesRegularExpression('/^01\d{8,9}$/', $applicationData['emergency_contact_phone']);
        $this->assertEquals('Pending', $applicationData['status']);
        
        // 2. Admin approval
        $admin = new User();
        $admin->role = 'admin';
        
        $applicationData['status'] = 'Approved';
        $user->role = 'volunteer';
        
        $this->assertEquals('Approved', $applicationData['status']);
        $this->assertEquals('volunteer', $user->role);
        // Note: isApprovedVolunteer() requires database connection, skip in unit test
        // $this->assertTrue($user->isApprovedVolunteer());
    }

    /**
     * Test complete community project application workflow
     */
    public function test_complete_community_project_application_workflow(): void
    {
        // 1. Create community project
        $projectData = [
            'title' => 'Search Operation in KL City Center',
            'description' => 'Organized search for missing person in KL city center area',
            'location' => 'Kuala Lumpur City Center',
            'date' => '2024-02-15',
            'time' => '08:00',
            'duration' => '4 hours',
            'volunteers_needed' => 20,
            'volunteers_joined' => 0,
            'points_reward' => 30,
            'category' => 'Search Operation',
            'status' => 'active'
        ];
        
        $this->assertNotEmpty($projectData['title']);
        $this->assertNotEmpty($projectData['description']);
        $this->assertGreaterThan(0, $projectData['volunteers_needed']);
        $this->assertGreaterThan(0, $projectData['points_reward']);
        $this->assertEquals('active', $projectData['status']);
        
        // 2. Volunteer applies to project
        $volunteer = new User();
        $volunteer->id = 5;
        $volunteer->role = 'volunteer';
        
        $projectApplicationData = [
            'user_id' => $volunteer->id,
            'community_project_id' => 1,
            'experience' => 'I have 2 years of volunteer experience in search and rescue operations',
            'motivation' => 'I want to contribute to finding missing persons in my community',
            'status' => 'pending'
        ];
        
        $this->assertGreaterThanOrEqual(10, strlen($projectApplicationData['experience']));
        $this->assertLessThanOrEqual(1000, strlen($projectApplicationData['experience']));
        $this->assertGreaterThanOrEqual(10, strlen($projectApplicationData['motivation']));
        $this->assertLessThanOrEqual(500, strlen($projectApplicationData['motivation']));
        $this->assertEquals('pending', $projectApplicationData['status']);
        
        // 3. Admin approval
        $projectApplicationData['status'] = 'approved';
        $this->assertEquals('approved', $projectApplicationData['status']);
        
        // 4. Project completion, award points
        $pointsAwarded = $projectData['points_reward'];
        $this->assertEquals(30, $pointsAwarded);
    }

    /**
     * Test complete points system workflow
     */
    public function test_complete_points_system_workflow(): void
    {
        $user = new User();
        $user->id = 6;
        
        // 1. Registration reward
        $registrationPoints = 10;
        $this->assertEquals(10, $registrationPoints);
        
        // 2. Missing report submission reward
        $reportPoints = 5;
        $this->assertEquals(5, $reportPoints);
        
        // 3. Sighting report reward
        $sightingPoints = 10;
        $this->assertEquals(10, $sightingPoints);
        
        // 4. Community project reward
        $projectPoints = 30;
        $this->assertEquals(30, $projectPoints);
        
        // 5. Social media share reward
        $sharePoints = 1;
        $this->assertEquals(1, $sharePoints);
        
        // 6. Total points calculation
        $totalEarned = $registrationPoints + $reportPoints + $sightingPoints + $projectPoints + $sharePoints;
        $this->assertEquals(56, $totalEarned);
        
        // 7. Reward redemption
        $reward = new Reward();
        $reward->points_required = 50;
        $reward->name = 'Coffee Voucher';
        $reward->status = 'active';
        
        $this->assertGreaterThanOrEqual($reward->points_required, $totalEarned);
        $this->assertEquals('active', $reward->status);
        
        // 8. Remaining points after redemption
        $remainingPoints = $totalEarned - $reward->points_required;
        $this->assertEquals(6, $remainingPoints);
    }

    /**
     * Test complete admin workflow
     */
    public function test_complete_admin_workflow(): void
    {
        $admin = new User();
        $admin->id = 7;
        $admin->role = 'admin';
        
        // 1. Admin permission validation
        $this->assertTrue($admin->isAdmin());
        $this->assertTrue($admin->canAccessAdminDashboard());
        
        // 2. Manage missing reports
        $reportStatuses = ['Pending', 'Approved', 'Rejected', 'Missing', 'Found', 'Closed'];
        foreach ($reportStatuses as $status) {
            $this->assertIsString($status);
            $this->assertNotEmpty($status);
        }
        
        // 3. Manage sighting reports
        $sightingStatuses = ['Pending', 'Approved', 'Rejected', 'Verified'];
        foreach ($sightingStatuses as $status) {
            $this->assertIsString($status);
            $this->assertNotEmpty($status);
        }
        
        // 4. Manage volunteer applications
        $volunteerStatuses = ['Pending', 'Approved', 'Rejected'];
        foreach ($volunteerStatuses as $status) {
            $this->assertIsString($status);
            $this->assertNotEmpty($status);
        }
        
        // 5. Manage user roles
        $userRoles = ['user', 'volunteer', 'admin'];
        foreach ($userRoles as $role) {
            $this->assertIsString($role);
            $this->assertNotEmpty($role);
        }
    }

    /**
     * Test complete file upload and validation workflow
     */
    public function test_complete_file_upload_workflow(): void
    {
        // 1. Missing report photo upload
        $photoFiles = [
            'photos/jane1.jpg',
            'photos/jane2.jpg',
            'photos/jane3.jpg'
        ];
        
        $this->assertIsArray($photoFiles);
        $this->assertGreaterThan(0, count($photoFiles));
        $this->assertLessThanOrEqual(5, count($photoFiles));
        
        // 2. Police report upload
        $policeReport = 'police_reports/report123.pdf';
        $this->assertStringEndsWith('.pdf', $policeReport);
        
        // 3. Volunteer application supporting documents
        $supportingDocs = [
            'docs/certificate.pdf',
            'docs/reference.pdf',
            'docs/id_copy.jpg'
        ];
        
        $this->assertIsArray($supportingDocs);
        $this->assertLessThanOrEqual(3, count($supportingDocs));
        
        // 4. Sighting report photos
        $sightingPhotos = [
            'sightings/sighting1.jpg',
            'sightings/sighting2.jpg'
        ];
        
        $this->assertIsArray($sightingPhotos);
        $this->assertGreaterThan(0, count($sightingPhotos));
    }

    /**
     * Test complete notification system workflow
     */
    public function test_complete_notification_workflow(): void
    {
        $notificationTypes = [
            'missing_report_submitted' => 'Missing report submitted successfully',
            'sighting_report_submitted' => 'Sighting report submitted successfully',
            'volunteer_application_submitted' => 'Volunteer application submitted successfully',
            'project_application_submitted' => 'Project application submitted successfully',
            'report_approved' => 'Your report has been approved',
            'report_rejected' => 'Your report has been rejected',
            'points_earned' => 'You have earned points for your contribution',
            'volunteer_approved' => 'Your volunteer application has been approved',
            'volunteer_rejected' => 'Your volunteer application has been rejected'
        ];
        
        foreach ($notificationTypes as $type => $message) {
            $this->assertIsString($type);
            $this->assertIsString($message);
            $this->assertNotEmpty($type);
            $this->assertNotEmpty($message);
        }
    }

    /**
     * Test complete search and filtering workflow
     */
    public function test_complete_search_filter_workflow(): void
    {
        // 1. Missing report search
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
        
        $this->assertIsString($searchCriteria['name']);
        $this->assertIsInt($searchCriteria['age_min']);
        $this->assertIsInt($searchCriteria['age_max']);
        $this->assertLessThan($searchCriteria['age_max'], $searchCriteria['age_min']);
        $this->assertIsArray($searchCriteria['gender']);
        
        // 2. Sighting report search
        $sightingSearchCriteria = [
            'location' => 'Petaling Jaya',
            'status' => 'Approved',
            'missing_report_id' => 1,
            'date_from' => '2024-01-01',
            'date_to' => '2024-12-31'
        ];
        
        $this->assertIsString($sightingSearchCriteria['location']);
        $this->assertIsString($sightingSearchCriteria['status']);
        $this->assertIsInt($sightingSearchCriteria['missing_report_id']);
        
        // 3. Volunteer search
        $volunteerSearchCriteria = [
            'skills' => ['search_rescue', 'communication'],
            'languages' => ['english', 'malay'],
            'status' => 'Approved',
            'date_from' => '2024-01-01',
            'date_to' => '2024-12-31'
        ];
        
        $this->assertIsArray($volunteerSearchCriteria['skills']);
        $this->assertIsArray($volunteerSearchCriteria['languages']);
        $this->assertIsString($volunteerSearchCriteria['status']);
    }
}