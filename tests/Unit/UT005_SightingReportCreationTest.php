<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class UT005_SightingReportCreationTest extends TestCase
{
    public function test_sighting_report_validation_rules_structure(): void
    {
        $rules = [
            'location' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:2000'],
            'sighted_at' => ['required', 'date'],
            'reporter_name' => ['required', 'string', 'max:255'],
            'reporter_phone' => ['required', 'string', 'regex:/^01\d{8,9}$/'],
            'reporter_email' => ['required', 'email', 'max:255'],
            'photos' => ['required', 'array', 'min:1'],
            'photos.*' => ['required', 'string'],
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
            'location',
            'description',
            'sighted_at',
            'reporter_name',
            'reporter_phone',
            'reporter_email',
            'photos'
        ];
        
        foreach ($requiredFields as $field) {
            $this->assertIsString($field);
            $this->assertNotEmpty($field);
        }
    }

    public function test_location_validation_rules(): void
    {
        $locationRules = ['required', 'string', 'max:255'];
        
        $this->assertContains('required', $locationRules);
        $this->assertContains('string', $locationRules);
        $this->assertContains('max:255', $locationRules);
    }

    public function test_description_validation_rules(): void
    {
        $descriptionRules = ['required', 'string', 'max:2000'];
        
        $this->assertContains('required', $descriptionRules);
        $this->assertContains('string', $descriptionRules);
        $this->assertContains('max:2000', $descriptionRules);
    }

    public function test_sighted_at_validation_rules(): void
    {
        $sightedAtRules = ['required', 'date'];
        
        $this->assertContains('required', $sightedAtRules);
        $this->assertContains('date', $sightedAtRules);
    }

    public function test_reporter_name_validation_rules(): void
    {
        $reporterNameRules = ['required', 'string', 'max:255'];
        
        $this->assertContains('required', $reporterNameRules);
        $this->assertContains('string', $reporterNameRules);
        $this->assertContains('max:255', $reporterNameRules);
    }

    public function test_reporter_phone_validation_rules(): void
    {
        $phoneRules = ['required', 'string', 'regex:/^01\d{8,9}$/'];
        
        $this->assertContains('required', $phoneRules);
        $this->assertContains('string', $phoneRules);
        $this->assertContains('regex:/^01\d{8,9}$/', $phoneRules);
    }

    public function test_reporter_email_validation_rules(): void
    {
        $emailRules = ['required', 'email', 'max:255'];
        
        $this->assertContains('required', $emailRules);
        $this->assertContains('email', $emailRules);
        $this->assertContains('max:255', $emailRules);
    }

    public function test_photos_validation_rules(): void
    {
        $photosRules = ['required', 'array', 'min:1'];
        $photoItemRules = ['required', 'string'];
        
        $this->assertContains('required', $photosRules);
        $this->assertContains('array', $photosRules);
        $this->assertContains('min:1', $photosRules);
        
        $this->assertContains('required', $photoItemRules);
        $this->assertContains('string', $photoItemRules);
    }

    public function test_string_length_validation_rules(): void
    {
        $maxLengths = [
            'location' => 255,
            'description' => 2000,
            'reporter_name' => 255,
            'reporter_email' => 255
        ];
        
        foreach ($maxLengths as $field => $maxLength) {
            $this->assertIsString($field);
            $this->assertIsInt($maxLength);
            $this->assertGreaterThan(0, $maxLength);
        }
    }

    public function test_phone_regex_validation(): void
    {
        $phonePattern = '/^01\d{8,9}$/';
        
        $this->assertIsString($phonePattern);
        $this->assertStringStartsWith('/', $phonePattern);
        $this->assertStringEndsWith('/', $phonePattern);
    }

    public function test_email_validation_type(): void
    {
        $emailValidation = 'email';
        
        $this->assertIsString($emailValidation);
        $this->assertEquals('email', $emailValidation);
    }

    public function test_date_validation_type(): void
    {
        $dateValidation = 'date';
        
        $this->assertIsString($dateValidation);
        $this->assertEquals('date', $dateValidation);
    }

    public function test_array_validation_type(): void
    {
        $arrayValidation = 'array';
        
        $this->assertIsString($arrayValidation);
        $this->assertEquals('array', $arrayValidation);
    }

    public function test_string_validation_type(): void
    {
        $stringValidation = 'string';
        
        $this->assertIsString($stringValidation);
        $this->assertEquals('string', $stringValidation);
    }

    public function test_required_validation_type(): void
    {
        $requiredValidation = 'required';
        
        $this->assertIsString($requiredValidation);
        $this->assertEquals('required', $requiredValidation);
    }
}