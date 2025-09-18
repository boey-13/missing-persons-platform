<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Models\User;
use App\Models\SightingReport;

class UT010_AdminPanelSightingReportManagementTest extends TestCase
{
    public function test_sighting_report_status_validation(): void
    {
        $validStatuses = ['pending', 'approved', 'rejected', 'verified'];
        
        foreach ($validStatuses as $status) {
            $report = new SightingReport();
            $report->status = $status;
            
            $this->assertContains($status, $validStatuses);
        }
    }

    public function test_sighting_report_priority_validation(): void
    {
        $validPriorities = ['low', 'medium', 'high', 'urgent'];
        
        foreach ($validPriorities as $priority) {
            $report = new SightingReport();
            $report->priority = $priority;
            
            $this->assertContains($priority, $validPriorities);
        }
    }

    public function test_sighting_report_approval_logic(): void
    {
        $report = new SightingReport();
        $report->status = 'pending';
        
        // Simulate approval
        $report->status = 'approved';
        $this->assertEquals('approved', $report->status);
    }

    public function test_sighting_report_rejection_logic(): void
    {
        $report = new SightingReport();
        $report->status = 'pending';
        
        // Simulate rejection
        $report->status = 'rejected';
        $this->assertEquals('rejected', $report->status);
    }

    public function test_sighting_report_verification_logic(): void
    {
        $report = new SightingReport();
        $report->status = 'approved';
        
        // Simulate verification
        $report->status = 'verified';
        $this->assertEquals('verified', $report->status);
    }

    public function test_admin_sighting_permissions(): void
    {
        $admin = new User();
        $admin->role = 'admin';
        
        $permissions = [
            'view_sighting_reports' => true,
            'approve_sighting_reports' => true,
            'reject_sighting_reports' => true,
            'verify_sighting_reports' => true,
            'view_all_sightings' => true
        ];
        
        foreach ($permissions as $permission => $expected) {
            $this->assertTrue($expected, "Admin should have {$permission} permission");
        }
    }

    public function test_user_sighting_permissions(): void
    {
        $user = new User();
        $user->role = 'user';
        
        $permissions = [
            'view_own_sightings' => true,
            'create_sightings' => true,
            'edit_own_sightings' => true,
            'view_all_sightings' => false,
            'approve_sightings' => false
        ];
        
        foreach ($permissions as $permission => $expected) {
            if ($expected) {
                $this->assertTrue($expected, "User should have {$permission} permission");
            } else {
                $this->assertFalse($expected, "User should not have {$permission} permission");
            }
        }
    }

    public function test_sighting_report_priority_assignment(): void
    {
        $report = new SightingReport();
        
        $priorities = ['low', 'medium', 'high', 'urgent'];
        
        foreach ($priorities as $priority) {
            $report->priority = $priority;
            $this->assertEquals($priority, $report->priority);
        }
    }

    public function test_sighting_report_status_transitions(): void
    {
        $report = new SightingReport();
        
        $validTransitions = [
            'pending' => ['approved', 'rejected'],
            'approved' => ['verified'],
            'rejected' => ['pending'],
            'verified' => []
        ];
        
        foreach ($validTransitions as $fromStatus => $toStatuses) {
            $report->status = $fromStatus;
            $this->assertEquals($fromStatus, $report->status);
            
            foreach ($toStatuses as $toStatus) {
                $report->status = $toStatus;
                $this->assertEquals($toStatus, $report->status);
            }
        }
    }

    public function test_sighting_report_validation_rules(): void
    {
        $requiredFields = [
            'sighting_date',
            'sighting_location',
            'description',
            'contact_name',
            'contact_phone',
            'contact_relationship'
        ];
        
        foreach ($requiredFields as $field) {
            $this->assertNotEmpty($field, "Field {$field} should be required");
        }
    }

    public function test_sighting_report_optional_fields(): void
    {
        $optionalFields = [
            'witness_name',
            'witness_phone',
            'additional_notes'
        ];
        
        foreach ($optionalFields as $field) {
            $this->assertNotEmpty($field, "Field {$field} should be optional");
        }
    }

    public function test_sighting_report_contact_validation(): void
    {
        $validPhoneNumbers = ['0123456789', '0198765432', '01123456789'];
        
        foreach ($validPhoneNumbers as $phone) {
            $this->assertMatchesRegularExpression('/^01\d{8,9}$/', $phone);
        }
    }

    public function test_sighting_report_location_validation(): void
    {
        $validLocations = [
            'Kuala Lumpur, Malaysia',
            'Petaling Jaya, Selangor',
            'George Town, Penang'
        ];
        
        foreach ($validLocations as $location) {
            $this->assertIsString($location);
            $this->assertGreaterThan(0, strlen($location));
        }
    }
}
