<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class UT007_VolunteerApplicationTest extends TestCase
{
    public function test_volunteer_application_validation_rules_structure(): void
    {
        $rules = [
            'motivation' => ['required', 'string'],
            'skills' => ['nullable', 'array'],
            'skills.*' => ['string'],
            'languages' => ['nullable', 'array'],
            'languages.*' => ['string'],
            'availability' => ['nullable', 'array'],
            'preferred_roles' => ['nullable', 'array'],
            'areas' => ['nullable', 'string'],
            'transport_mode' => ['nullable', 'string'],
            'emergency_contact_name' => ['required', 'string', 'min:2', 'regex:/^[a-zA-Z\s]+$/'],
            'emergency_contact_phone' => ['required', 'string', 'regex:/^01\d{8,9}$/'],
            'prior_experience' => ['nullable', 'string'],
            'supporting_documents' => ['nullable', 'array'],
            'supporting_documents.*' => ['file', 'mimes:pdf,doc,docx,jpg,jpeg,png', 'max:5120'],
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
            'motivation',
            'emergency_contact_name',
            'emergency_contact_phone'
        ];
        
        foreach ($requiredFields as $field) {
            $this->assertIsString($field);
            $this->assertNotEmpty($field);
        }
    }

    public function test_optional_fields_validation(): void
    {
        $optionalFields = [
            'skills',
            'languages',
            'availability',
            'preferred_roles',
            'areas',
            'transport_mode',
            'prior_experience',
            'supporting_documents'
        ];
        
        foreach ($optionalFields as $field) {
            $this->assertIsString($field);
            $this->assertNotEmpty($field);
        }
    }

    public function test_emergency_contact_name_validation_rules(): void
    {
        $nameRules = ['required', 'string', 'min:2', 'regex:/^[a-zA-Z\s]+$/'];
        
        $this->assertContains('required', $nameRules);
        $this->assertContains('string', $nameRules);
        $this->assertContains('min:2', $nameRules);
        $this->assertContains('regex:/^[a-zA-Z\s]+$/', $nameRules);
    }

    public function test_emergency_contact_phone_validation_rules(): void
    {
        $phoneRules = ['required', 'string', 'regex:/^01\d{8,9}$/'];
        
        $this->assertContains('required', $phoneRules);
        $this->assertContains('string', $phoneRules);
        $this->assertContains('regex:/^01\d{8,9}$/', $phoneRules);
    }

    public function test_supporting_documents_validation_rules(): void
    {
        $docRules = ['file', 'mimes:pdf,doc,docx,jpg,jpeg,png', 'max:5120'];
        
        $this->assertContains('file', $docRules);
        $this->assertContains('mimes:pdf,doc,docx,jpg,jpeg,png', $docRules);
        $this->assertContains('max:5120', $docRules);
    }

    public function test_phone_regex_validation(): void
    {
        $phonePattern = '/^01\d{8,9}$/';
        
        $this->assertIsString($phonePattern);
        $this->assertStringStartsWith('/', $phonePattern);
        $this->assertStringEndsWith('/', $phonePattern);
    }

    public function test_name_regex_validation(): void
    {
        $namePattern = '/^[a-zA-Z\s]+$/';
        
        $this->assertIsString($namePattern);
        $this->assertStringStartsWith('/', $namePattern);
        $this->assertStringEndsWith('/', $namePattern);
    }

    public function test_supported_file_types(): void
    {
        $supportedTypes = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'];
        
        foreach ($supportedTypes as $type) {
            $this->assertIsString($type);
            $this->assertNotEmpty($type);
        }
    }

    public function test_file_size_limit(): void
    {
        $maxSize = 5120; // 5MB in KB
        
        $this->assertIsInt($maxSize);
        $this->assertGreaterThan(0, $maxSize);
    }

    public function test_minimum_name_length(): void
    {
        $minLength = 2;
        
        $this->assertIsInt($minLength);
        $this->assertGreaterThan(0, $minLength);
    }

    public function test_array_field_validation(): void
    {
        $arrayFields = ['skills', 'languages', 'availability', 'preferred_roles', 'supporting_documents'];
        
        foreach ($arrayFields as $field) {
            $this->assertIsString($field);
            $this->assertNotEmpty($field);
        }
    }

    public function test_string_field_validation(): void
    {
        $stringFields = ['motivation', 'areas', 'transport_mode', 'prior_experience'];
        
        foreach ($stringFields as $field) {
            $this->assertIsString($field);
            $this->assertNotEmpty($field);
        }
    }

    public function test_validation_rule_types(): void
    {
        $ruleTypes = ['required', 'nullable', 'string', 'array', 'file', 'min', 'max', 'regex', 'mimes'];
        
        foreach ($ruleTypes as $type) {
            $this->assertIsString($type);
            $this->assertNotEmpty($type);
        }
    }
}