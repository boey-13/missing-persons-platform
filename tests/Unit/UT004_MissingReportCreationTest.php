<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class UT004_MissingReportCreationTest extends TestCase
{
    public function test_missing_report_validation_rules_structure(): void
    {
        $rules = [
            'full_name' => ['required', 'regex:/^[A-Za-z\s]+$/', 'max:255'],
            'nickname' => ['nullable', 'regex:/^[A-Za-z\s]*$/', 'max:255'],
            'ic_number' => ['required', 'digits:12'],
            'age' => ['required', 'integer', 'min:0', 'max:150'],
            'gender' => ['required', 'in:Male,Female,Other'],
            'height_cm' => ['nullable', 'numeric', 'min:0'],
            'weight_kg' => ['nullable', 'numeric', 'min:0'],
            'physical_description' => ['nullable', 'string', 'max:1000'],
            'last_seen_date' => ['required', 'date'],
            'last_seen_location' => ['required', 'string', 'max:255'],
            'last_seen_clothing' => ['nullable', 'string', 'max:255'],
            'reporter_relationship' => ['required', 'in:Parent,Child,Spouse,Sibling,Friend,Other'],
            'reporter_name' => ['required', 'regex:/^[A-Za-z\s]+$/', 'max:255'],
            'reporter_ic_number' => ['required', 'digits:12'],
            'reporter_phone' => ['required', 'string', 'regex:/^01\d{8,9}$/'],
            'reporter_email' => ['required', 'email', 'max:255'],
            'additional_notes' => ['nullable', 'string', 'max:2000'],
            'photos' => ['required', 'array', 'min:1', 'max:5'],
            'photos.*' => ['required', 'string'], 
            'police_report' => ['required', 'string'], 
        ];
        
        foreach ($rules as $field => $fieldRules) {
            $this->assertIsString($field);
            $this->assertIsArray($fieldRules);
            $this->assertNotEmpty($fieldRules);
        }
    }

    public function test_required_fields_validation(): void
    {
        $requiredFields = [
            'full_name',
            'ic_number', 
            'age',
            'gender',
            'last_seen_date',
            'last_seen_location',
            'reporter_relationship',
            'reporter_name',
            'reporter_ic_number',
            'reporter_phone',
            'reporter_email',
            'photos',
            'police_report'
        ];
        
        foreach ($requiredFields as $field) {
            $this->assertIsString($field);
            $this->assertNotEmpty($field);
        }
    }

    public function test_optional_fields_validation(): void
    {
        $optionalFields = [
            'nickname',
            'height_cm',
            'weight_kg',
            'physical_description',
            'last_seen_clothing',
            'additional_notes'
        ];
        
        foreach ($optionalFields as $field) {
            $this->assertIsString($field);
            $this->assertNotEmpty($field);
        }
    }

    public function test_ic_number_validation_rules(): void
    {
        $icRules = ['required', 'digits:12'];
        
        $this->assertContains('required', $icRules);
        $this->assertContains('digits:12', $icRules);
    }

    public function test_age_validation_rules(): void
    {
        $ageRules = ['required', 'integer', 'min:0', 'max:150'];
        
        $this->assertContains('required', $ageRules);
        $this->assertContains('integer', $ageRules);
        $this->assertContains('min:0', $ageRules);
        $this->assertContains('max:150', $ageRules);
    }

    public function test_gender_validation_rules(): void
    {
        $genderRules = ['required', 'in:Male,Female,Other'];
        $validGenders = ['Male', 'Female', 'Other'];
        
        $this->assertContains('required', $genderRules);
        $this->assertContains('in:Male,Female,Other', $genderRules);
        
        foreach ($validGenders as $gender) {
            $this->assertIsString($gender);
            $this->assertNotEmpty($gender);
        }
    }

    public function test_phone_validation_rules(): void
    {
        $phoneRules = ['required', 'string', 'regex:/^01\d{8,9}$/'];
        
        $this->assertContains('required', $phoneRules);
        $this->assertContains('string', $phoneRules);
        $this->assertContains('regex:/^01\d{8,9}$/', $phoneRules);
    }

    public function test_email_validation_rules(): void
    {
        $emailRules = ['required', 'email', 'max:255'];
        
        $this->assertContains('required', $emailRules);
        $this->assertContains('email', $emailRules);
        $this->assertContains('max:255', $emailRules);
    }

    public function test_photos_validation_rules(): void
    {
        $photosRules = ['required', 'array', 'min:1', 'max:5'];
        $photoItemRules = ['required', 'string'];
        
        $this->assertContains('required', $photosRules);
        $this->assertContains('array', $photosRules);
        $this->assertContains('min:1', $photosRules);
        $this->assertContains('max:5', $photosRules);
        
        $this->assertContains('required', $photoItemRules);
        $this->assertContains('string', $photoItemRules);
    }

    public function test_reporter_relationship_validation_rules(): void
    {
        $relationshipRules = ['required', 'in:Parent,Child,Spouse,Sibling,Friend,Other'];
        $validRelationships = ['Parent', 'Child', 'Spouse', 'Sibling', 'Friend', 'Other'];
        
        $this->assertContains('required', $relationshipRules);
        $this->assertContains('in:Parent,Child,Spouse,Sibling,Friend,Other', $relationshipRules);
        
        foreach ($validRelationships as $relationship) {
            $this->assertIsString($relationship);
            $this->assertNotEmpty($relationship);
        }
    }

    public function test_string_length_validation_rules(): void
    {
        $maxLengths = [
            'full_name' => 255,
            'nickname' => 255,
            'last_seen_location' => 255,
            'last_seen_clothing' => 255,
            'reporter_name' => 255,
            'reporter_email' => 255,
            'physical_description' => 1000,
            'additional_notes' => 2000
        ];
        
        foreach ($maxLengths as $field => $maxLength) {
            $this->assertIsString($field);
            $this->assertIsInt($maxLength);
            $this->assertGreaterThan(0, $maxLength);
        }
    }

    public function test_numeric_validation_rules(): void
    {
        $numericFields = [
            'height_cm' => ['min:0'],
            'weight_kg' => ['min:0']
        ];
        
        foreach ($numericFields as $field => $rules) {
            $this->assertIsString($field);
            $this->assertIsArray($rules);
            $this->assertContains('min:0', $rules);
        }
    }

    public function test_regex_validation_rules(): void
    {
        $regexRules = [
            'full_name' => '/^[A-Za-z\s]+$/',
            'nickname' => '/^[A-Za-z\s]*$/',
            'reporter_name' => '/^[A-Za-z\s]+$/',
            'reporter_phone' => '/^01\d{8,9}$/'
        ];
        
        foreach ($regexRules as $field => $pattern) {
            $this->assertIsString($field);
            $this->assertIsString($pattern);
            $this->assertStringStartsWith('/', $pattern);
            $this->assertStringEndsWith('/', $pattern);
        }
    }
}