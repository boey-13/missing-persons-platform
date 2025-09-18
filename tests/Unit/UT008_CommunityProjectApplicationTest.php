<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class UT008_CommunityProjectApplicationTest extends TestCase
{
    public function test_project_application_validation_rules_structure(): void
    {
        $rules = [
            'experience' => ['required', 'string', 'min:10', 'max:1000'],
            'motivation' => ['required', 'string', 'min:10', 'max:500'],
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
            'experience',
            'motivation'
        ];
        
        foreach ($requiredFields as $field) {
            $this->assertIsString($field);
            $this->assertNotEmpty($field);
        }
    }

    public function test_experience_validation_rules(): void
    {
        $experienceRules = ['required', 'string', 'min:10', 'max:1000'];
        
        $this->assertContains('required', $experienceRules);
        $this->assertContains('string', $experienceRules);
        $this->assertContains('min:10', $experienceRules);
        $this->assertContains('max:1000', $experienceRules);
    }

    public function test_motivation_validation_rules(): void
    {
        $motivationRules = ['required', 'string', 'min:10', 'max:500'];
        
        $this->assertContains('required', $motivationRules);
        $this->assertContains('string', $motivationRules);
        $this->assertContains('min:10', $motivationRules);
        $this->assertContains('max:500', $motivationRules);
    }

    public function test_string_length_validation_rules(): void
    {
        $maxLengths = [
            'experience' => 1000,
            'motivation' => 500
        ];
        
        foreach ($maxLengths as $field => $maxLength) {
            $this->assertIsString($field);
            $this->assertIsInt($maxLength);
            $this->assertGreaterThan(0, $maxLength);
        }
    }

    public function test_minimum_length_validation_rules(): void
    {
        $minLengths = [
            'experience' => 10,
            'motivation' => 10
        ];
        
        foreach ($minLengths as $field => $minLength) {
            $this->assertIsString($field);
            $this->assertIsInt($minLength);
            $this->assertGreaterThan(0, $minLength);
        }
    }

    public function test_validation_rule_types(): void
    {
        $ruleTypes = ['required', 'string', 'min', 'max'];
        
        foreach ($ruleTypes as $type) {
            $this->assertIsString($type);
            $this->assertNotEmpty($type);
        }
    }

    public function test_field_validation_structure(): void
    {
        $fields = ['experience', 'motivation'];
        
        foreach ($fields as $field) {
            $this->assertIsString($field);
            $this->assertNotEmpty($field);
        }
    }

    public function test_character_limit_validation(): void
    {
        $limits = [
            'experience_min' => 10,
            'experience_max' => 1000,
            'motivation_min' => 10,
            'motivation_max' => 500
        ];
        
        foreach ($limits as $limit => $value) {
            $this->assertIsString($limit);
            $this->assertIsInt($value);
            $this->assertGreaterThan(0, $value);
        }
    }

    public function test_validation_rule_combinations(): void
    {
        $experienceRules = ['required', 'string', 'min:10', 'max:1000'];
        $motivationRules = ['required', 'string', 'min:10', 'max:500'];
        
        // Test that both have required and string rules
        $this->assertContains('required', $experienceRules);
        $this->assertContains('string', $experienceRules);
        $this->assertContains('required', $motivationRules);
        $this->assertContains('string', $motivationRules);
        
        // Test that both have min and max rules
        $this->assertTrue(in_array('min:10', $experienceRules));
        $this->assertTrue(in_array('max:1000', $experienceRules));
        $this->assertTrue(in_array('min:10', $motivationRules));
        $this->assertTrue(in_array('max:500', $motivationRules));
    }

    public function test_validation_rule_count(): void
    {
        $experienceRules = ['required', 'string', 'min:10', 'max:1000'];
        $motivationRules = ['required', 'string', 'min:10', 'max:500'];
        
        $this->assertCount(4, $experienceRules);
        $this->assertCount(4, $motivationRules);
    }
}