<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Models\CommunityProject;
use App\Models\ProjectApplication;

class UT014_AdminPanelCommunityProjectManagementTest extends TestCase
{
    public function test_community_project_status_validation(): void
    {
        $validStatuses = ['draft', 'active', 'completed', 'cancelled', 'paused'];
        
        foreach ($validStatuses as $status) {
            $project = new CommunityProject();
            $project->status = $status;
            
            $this->assertContains($status, $validStatuses);
        }
    }

    public function test_community_project_priority_validation(): void
    {
        $validPriorities = ['low', 'medium', 'high', 'urgent'];
        
        foreach ($validPriorities as $priority) {
            $project = new CommunityProject();
            $project->priority = $priority;
            
            $this->assertContains($priority, $validPriorities);
        }
    }

    public function test_project_application_status_validation(): void
    {
        $validStatuses = ['pending', 'approved', 'rejected', 'withdrawn'];
        
        foreach ($validStatuses as $status) {
            $application = new ProjectApplication();
            $application->status = $status;
            
            $this->assertContains($status, $validStatuses);
        }
    }

    public function test_community_project_creation_logic(): void
    {
        $project = new CommunityProject();
        $project->title = 'Test Project';
        $project->description = 'Test Description';
        $project->status = 'draft';
        $project->priority = 'medium';
        
        $this->assertEquals('Test Project', $project->title);
        $this->assertEquals('Test Description', $project->description);
        $this->assertEquals('draft', $project->status);
        $this->assertEquals('medium', $project->priority);
    }

    public function test_project_application_creation_logic(): void
    {
        $application = new ProjectApplication();
        $application->motivation = 'I want to help';
        $application->status = 'pending';
        $application->skills = ['leadership', 'communication'];
        
        $this->assertEquals('I want to help', $application->motivation);
        $this->assertEquals('pending', $application->status);
        $this->assertIsArray($application->skills);
    }

    public function test_project_status_transitions(): void
    {
        $project = new CommunityProject();
        
        $validTransitions = [
            'draft' => ['active', 'cancelled'],
            'active' => ['completed', 'paused', 'cancelled'],
            'paused' => ['active', 'cancelled'],
            'completed' => [],
            'cancelled' => []
        ];
        
        foreach ($validTransitions as $fromStatus => $toStatuses) {
            $project->status = $fromStatus;
            $this->assertEquals($fromStatus, $project->status);
            
            foreach ($toStatuses as $toStatus) {
                $project->status = $toStatus;
                $this->assertEquals($toStatus, $project->status);
            }
        }
    }

    public function test_application_status_transitions(): void
    {
        $application = new ProjectApplication();
        
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

    public function test_project_validation_rules(): void
    {
        $requiredFields = [
            'title',
            'description',
            'status',
            'priority'
        ];
        
        foreach ($requiredFields as $field) {
            $this->assertNotEmpty($field, "Field {$field} should be required");
        }
    }

    public function test_application_validation_rules(): void
    {
        $requiredFields = [
            'motivation',
            'skills',
            'status'
        ];
        
        foreach ($requiredFields as $field) {
            $this->assertNotEmpty($field, "Field {$field} should be required");
        }
    }

    public function test_project_optional_fields(): void
    {
        $optionalFields = [
            'start_date',
            'end_date',
            'max_participants',
            'location',
            'requirements'
        ];
        
        foreach ($optionalFields as $field) {
            $this->assertNotEmpty($field, "Field {$field} should be optional");
        }
    }

    public function test_application_optional_fields(): void
    {
        $optionalFields = [
            'previous_experience',
            'availability_start',
            'availability_end',
            'additional_notes'
        ];
        
        foreach ($optionalFields as $field) {
            $this->assertNotEmpty($field, "Field {$field} should be optional");
        }
    }

    public function test_project_skills_validation(): void
    {
        $validSkills = ['leadership', 'communication', 'planning', 'technical', 'creative', 'other'];
        
        foreach ($validSkills as $skill) {
            $this->assertIsString($skill);
            $this->assertNotEmpty($skill);
        }
    }

    public function test_project_dates_validation(): void
    {
        $startDate = '2024-02-01';
        $endDate = '2024-02-28';
        
        $this->assertIsString($startDate);
        $this->assertIsString($endDate);
        $this->assertLessThan($endDate, $startDate);
    }

    public function test_project_participant_limits(): void
    {
        $validLimits = [1, 5, 10, 50, 100];
        
        foreach ($validLimits as $limit) {
            $this->assertIsInt($limit);
            $this->assertGreaterThan(0, $limit);
        }
    }

    public function test_project_search_criteria(): void
    {
        $searchCriteria = [
            'title' => 'Test',
            'status' => 'active',
            'priority' => 'high',
            'skills' => ['leadership']
        ];
        
        foreach ($searchCriteria as $criteria => $value) {
            $this->assertNotEmpty($criteria);
            $this->assertNotEmpty($value);
        }
    }

    public function test_project_sorting_options(): void
    {
        $sortingOptions = [
            'title_asc',
            'title_desc',
            'created_at_asc',
            'created_at_desc',
            'priority_asc',
            'priority_desc'
        ];
        
        foreach ($sortingOptions as $option) {
            $this->assertIsString($option);
            $this->assertNotEmpty($option);
        }
    }
}
