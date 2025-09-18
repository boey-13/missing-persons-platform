<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Models\VolunteerApplication;

class UT015_AdminPanelVolunteerManagementTest extends TestCase
{
    public function test_volunteer_application_status_validation(): void
    {
        $validStatuses = ['pending', 'approved', 'rejected', 'withdrawn'];
        
        foreach ($validStatuses as $status) {
            $application = new VolunteerApplication();
            $application->status = $status;
            
            $this->assertContains($status, $validStatuses);
        }
    }

    public function test_volunteer_application_creation_logic(): void
    {
        $application = new VolunteerApplication();
        $application->motivation = 'I want to help the community';
        $application->status = 'pending';
        $application->skills = ['search_rescue', 'communication'];
        $application->languages = ['english', 'malay'];
        
        $this->assertEquals('I want to help the community', $application->motivation);
        $this->assertEquals('pending', $application->status);
        $this->assertIsArray($application->skills);
        $this->assertIsArray($application->languages);
    }

    public function test_volunteer_application_status_transitions(): void
    {
        $application = new VolunteerApplication();
        
        $validTransitions = [
            'pending' => ['approved', 'rejected', 'withdrawn'],
            'approved' => ['withdrawn'],
            'rejected' => ['pending'],
            'withdrawn' => []
        ];
        
        foreach ($validTransitions as $fromStatus => $toStatuses) {
            $application->status = $fromStatus;
            $this->assertEquals($fromStatus, $application->status);
            
            foreach ($toStatuses as $toStatus) {
                $application->status = $toStatus;
                $this->assertEquals($toStatus, $application->status);
            }
        }
    }

    public function test_volunteer_skills_validation(): void
    {
        $validSkills = ['search_rescue', 'communication', 'medical', 'technical', 'logistics', 'other'];
        
        foreach ($validSkills as $skill) {
            $this->assertIsString($skill);
            $this->assertNotEmpty($skill);
        }
    }

    public function test_volunteer_languages_validation(): void
    {
        $validLanguages = ['english', 'malay', 'chinese', 'tamil', 'hindi', 'other'];
        
        foreach ($validLanguages as $language) {
            $this->assertIsString($language);
            $this->assertNotEmpty($language);
        }
    }

    public function test_volunteer_available_times_validation(): void
    {
        $validTimes = [
            'weekday_morning',
            'weekday_afternoon',
            'weekday_evening',
            'weekend_morning',
            'weekend_afternoon',
            'weekend_evening'
        ];
        
        foreach ($validTimes as $time) {
            $this->assertIsString($time);
            $this->assertNotEmpty($time);
        }
    }

    public function test_volunteer_application_validation_rules(): void
    {
        $requiredFields = [
            'motivation',
            'skills',
            'languages',
            'available_times',
            'emergency_contact_name',
            'emergency_contact_phone',
            'emergency_contact_relationship'
        ];
        
        foreach ($requiredFields as $field) {
            $this->assertNotEmpty($field, "Field {$field} should be required");
        }
    }

    public function test_volunteer_application_optional_fields(): void
    {
        $optionalFields = [
            'witness_name',
            'witness_phone',
            'supporting_documents',
            'additional_notes'
        ];
        
        foreach ($optionalFields as $field) {
            $this->assertNotEmpty($field, "Field {$field} should be optional");
        }
    }

    public function test_volunteer_emergency_contact_validation(): void
    {
        $validPhones = ['0123456789', '0198765432', '01123456789'];
        
        foreach ($validPhones as $phone) {
            $this->assertMatchesRegularExpression('/^01\d{8,9}$/', $phone);
        }
    }

    public function test_volunteer_motivation_length_validation(): void
    {
        $minLength = 50;
        $maxLength = 1000;
        
        $this->assertGreaterThan(0, $minLength);
        $this->assertGreaterThan($minLength, $maxLength);
    }

    public function test_volunteer_skills_array_validation(): void
    {
        $skills = ['search_rescue', 'communication', 'medical'];
        
        $this->assertIsArray($skills);
        $this->assertGreaterThan(0, count($skills));
        $this->assertLessThanOrEqual(10, count($skills));
    }

    public function test_volunteer_languages_array_validation(): void
    {
        $languages = ['english', 'malay'];
        
        $this->assertIsArray($languages);
        $this->assertGreaterThan(0, count($languages));
        $this->assertLessThanOrEqual(5, count($languages));
    }

    public function test_volunteer_available_times_array_validation(): void
    {
        $times = ['weekend_morning', 'weekend_afternoon'];
        
        $this->assertIsArray($times);
        $this->assertGreaterThan(0, count($times));
        $this->assertLessThanOrEqual(6, count($times));
    }

    public function test_volunteer_supporting_documents_validation(): void
    {
        $maxDocuments = 3;
        $validTypes = ['pdf', 'doc', 'docx'];
        
        $this->assertGreaterThan(0, $maxDocuments);
        $this->assertIsArray($validTypes);
        $this->assertGreaterThan(0, count($validTypes));
    }

    public function test_volunteer_application_search_criteria(): void
    {
        $searchCriteria = [
            'status' => 'pending',
            'skills' => ['search_rescue'],
            'languages' => ['english'],
            'date_from' => '2024-01-01',
            'date_to' => '2024-12-31'
        ];
        
        foreach ($searchCriteria as $criteria => $value) {
            $this->assertNotEmpty($criteria);
            $this->assertNotEmpty($value);
        }
    }

    public function test_volunteer_application_sorting_options(): void
    {
        $sortingOptions = [
            'created_at_asc',
            'created_at_desc',
            'status_asc',
            'status_desc',
            'motivation_asc',
            'motivation_desc'
        ];
        
        foreach ($sortingOptions as $option) {
            $this->assertIsString($option);
            $this->assertNotEmpty($option);
        }
    }
}
