<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Illuminate\Support\Facades\Validator;

class UT002_UserLoginTest extends TestCase
{
    private function getLoginRules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    public function test_valid_login_data_passes(): void
    {
        $data = [
            'email' => 'test@example.com',
            'password' => 'password123',
        ];

        $validator = Validator::make($data, $this->getLoginRules());
        $this->assertTrue($validator->passes());
    }

    public function test_empty_email_field_fails(): void
    {
        $data = [
            'email' => '',
            'password' => 'password123',
        ];

        $validator = Validator::make($data, $this->getLoginRules());
        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('email', $validator->errors()->toArray());
    }

    public function test_empty_password_field_fails(): void
    {
        $data = [
            'email' => 'test@example.com',
            'password' => '',
        ];

        $validator = Validator::make($data, $this->getLoginRules());
        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('password', $validator->errors()->toArray());
    }

    public function test_invalid_email_format_fails(): void
    {
        $data = [
            'email' => 'test@',
            'password' => 'password123',
        ];

        $validator = Validator::make($data, $this->getLoginRules());
        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('email', $validator->errors()->toArray());
    }

    public function test_empty_inputs_fail(): void
    {
        $data = [
            'email' => '',
            'password' => '',
        ];

        $validator = Validator::make($data, $this->getLoginRules());
        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('email', $validator->errors()->toArray());
        $this->assertArrayHasKey('password', $validator->errors()->toArray());
    }

    public function test_login_validation_with_various_invalid_email_formats(): void
    {
        $invalidEmails = [
            'test@',
            '@example.com',
            'test.example.com',
            'test@.com',
            'test@example.',
            'test space@example.com',
            'test@example .com'
        ];

        foreach ($invalidEmails as $email) {
            $data = [
                'email' => $email,
                'password' => 'password123'
            ];

            $validator = Validator::make($data, $this->getLoginRules());
            $this->assertFalse($validator->passes(), "Email '{$email}' should fail validation.");
            $this->assertArrayHasKey('email', $validator->errors()->toArray());
        }
    }

    public function test_valid_email_formats_pass(): void
    {
        $validEmails = [
            'test@example.com',
            'user.name@domain.co.uk',
            'user+tag@example.org',
            'test123@test-domain.com'
        ];

        foreach ($validEmails as $email) {
            $data = [
                'email' => $email,
                'password' => 'password123'
            ];

            $validator = Validator::make($data, $this->getLoginRules());
            $this->assertTrue($validator->passes(), "Email '{$email}' should pass validation.");
        }
    }

    public function test_password_accepts_various_strings(): void
    {
        $passwords = [
            'password123',
            'simple',
            'complex!@#$%^&*()',
            '123456789',
            'a',
            'very_long_password_with_many_characters_that_should_still_be_valid'
        ];

        foreach ($passwords as $password) {
            $data = [
                'email' => 'test@example.com',
                'password' => $password
            ];

            $validator = Validator::make($data, $this->getLoginRules());
            $this->assertTrue($validator->passes(), "Password '{$password}' should pass validation.");
        }
    }

    public function test_email_trimming_validation(): void
    {
        $data = [
            'email' => '  test@example.com  ', // Email with whitespace
            'password' => 'password123'
        ];

        // Apply trimming before validation
        $data['email'] = trim($data['email']);
        $validator = Validator::make($data, $this->getLoginRules());
        $this->assertTrue($validator->passes());
    }

    public function test_null_values_fail(): void
    {
        $data = [
            'email' => null,
            'password' => null,
        ];

        $validator = Validator::make($data, $this->getLoginRules());
        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('email', $validator->errors()->toArray());
        $this->assertArrayHasKey('password', $validator->errors()->toArray());
    }

    public function test_non_string_values_fail(): void
    {
        $data = [
            'email' => 123, // Non-string email
            'password' => 456, // Non-string password
        ];

        $validator = Validator::make($data, $this->getLoginRules());
        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('email', $validator->errors()->toArray());
        $this->assertArrayHasKey('password', $validator->errors()->toArray());
    }
}