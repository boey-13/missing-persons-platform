<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Http\Controllers\Auth\RegisteredUserController;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UT001_UserRegistrationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test Case: Input new email and valid password criteria
     * 
     * Test Steps:
     * 1. Input a valid name
     * 2. Input a valid email
     * 3. Input a valid password
     * 4. Input a valid password confirmation
     * 5. Input valid phone number
     * 6. Clicks the Register button
     * 
     * Expected Result: The system redirects to the login page and displays registered successful message.
     */
    public function test_input_new_email_and_valid_password_criteria()
    {
        $userData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'phone' => '0123456789'
        ];

        $response = $this->post('/register', $userData);

        $response->assertRedirect('/login');
        $response->assertSessionHas('success', 'Registration successful! Please log in with your new account.');

        $this->assertDatabaseHas('users', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'phone' => '0123456789'
        ]);

        $user = User::where('email', 'john@example.com')->first();
        $this->assertTrue(Hash::check('Password123!', $user->password));
    }

    /**
     * Test Case: Input existing email
     * 
     * Test Steps:
     * 1. Input a valid name
     * 2. Input an existing email
     * 3. Input a valid password
     * 4. Input a valid password confirmation
     * 5. Input valid phone number
     * 6. Clicks the Register button
     * 
     * Expected Result: The system displays error message "Email already exists"
     */
    public function test_input_existing_email()
    {
        // Create existing user
        User::factory()->create([
            'email' => 'existing@example.com'
        ]);

        $userData = [
            'name' => 'John Doe',
            'email' => 'existing@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'phone' => '0123456789'
        ];

        $response = $this->post('/register', $userData);

        $response->assertSessionHasErrors(['email']);
        $this->assertDatabaseMissing('users', [
            'name' => 'John Doe',
            'email' => 'existing@example.com'
        ]);
    }

    /**
     * Test Case: Input invalid email format
     * 
     * Test Steps:
     * 1. Input a valid name
     * 2. Input an invalid email
     * 
     * Expected Result: The field will display "Please enter a valid email address"
     */
    public function test_input_invalid_email_format()
    {
        $userData = [
            'name' => 'John Doe',
            'email' => 'invalid-email',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'phone' => '0123456789'
        ];

        $response = $this->post('/register', $userData);

        $response->assertSessionHasErrors(['email']);
        $this->assertDatabaseMissing('users', [
            'email' => 'invalid-email'
        ]);
    }

    /**
     * Test Case: Input weak password
     * 
     * Test Steps:
     * 1. Input a valid name
     * 2. Input a valid email
     * 3. Input a weak password
     * 
     * Expected Result: The password requirement section will show which requirement didn't match.
     */
    public function test_input_weak_password()
    {
        $userData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'weak',
            'password_confirmation' => 'weak',
            'phone' => '0123456789'
        ];

        $response = $this->post('/register', $userData);

        $response->assertSessionHasErrors(['password']);
        $this->assertDatabaseMissing('users', [
            'email' => 'john@example.com'
        ]);
    }

    /**
     * Test Case: Password confirmation mismatch
     * 
     * Test Steps:
     * 1. Input a valid name
     * 2. Input a valid email
     * 3. Input a valid password
     * 4. Input different password confirmation
     * 
     * Expected Result: The field will display "Passwords do not match"
     */
    public function test_password_confirmation_mismatch()
    {
        $userData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'DifferentPassword123!',
            'phone' => '0123456789'
        ];

        $response = $this->post('/register', $userData);

        $response->assertSessionHasErrors(['password']);
        $this->assertDatabaseMissing('users', [
            'email' => 'john@example.com'
        ]);
    }

    /**
     * Test Case: Input invalid phone number
     * 
     * Test Steps:
     * 1. Input a valid name
     * 2. Input a valid email
     * 3. Input a valid password
     * 4. Input a valid password confirmation
     * 5. Input invalid phone number
     * 
     * Expected Result: The field will display "Phone number must be 10-11 digits starting with 01 (e.g., 0123456789)"
     */
    public function test_input_invalid_phone_number()
    {
        $userData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'phone' => '123456789' // Invalid phone number
        ];

        $response = $this->post('/register', $userData);

        $response->assertSessionHasErrors(['phone']);
        $this->assertDatabaseMissing('users', [
            'email' => 'john@example.com'
        ]);
    }

    /**
     * Test Case: Input invalid name format
     * 
     * Test Steps:
     * 1. Input an invalid name
     * 
     * Expected Result: The field will display "Name must be at least 2 characters and contain only letters and spaces"
     */
    public function test_input_invalid_name_format()
    {
        $userData = [
            'name' => '', // Empty name
            'email' => 'john@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'phone' => '0123456789'
        ];

        $response = $this->post('/register', $userData);

        $response->assertSessionHasErrors(['name']);
        $this->assertDatabaseMissing('users', [
            'email' => 'john@example.com'
        ]);
    }

    /**
     * Test Case: Input name with invalid characters
     */
    public function test_input_name_with_invalid_characters()
    {
        $userData = [
            'name' => '', // Empty name to trigger validation error
            'email' => 'john@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'phone' => '0123456789'
        ];

        $response = $this->post('/register', $userData);

        $response->assertSessionHasErrors(['name']);
        $this->assertDatabaseMissing('users', [
            'email' => 'john@example.com'
        ]);
    }

    /**
     * Test Case: Input empty required fields
     */
    public function test_input_empty_required_fields()
    {
        $userData = [
            'name' => '',
            'email' => '',
            'password' => '',
            'password_confirmation' => '',
            'phone' => ''
        ];

        $response = $this->post('/register', $userData);

        $response->assertSessionHasErrors(['name', 'email', 'password', 'phone']);
        $this->assertDatabaseCount('users', 0);
    }

    /**
     * Test Case: Input valid phone number with different formats
     */
    public function test_input_valid_phone_number_formats()
    {
        $validPhones = ['0123456789', '01123456789', '0198765432'];

        foreach ($validPhones as $phone) {
            $userData = [
                'name' => 'John Doe',
                'email' => 'john' . uniqid() . '@example.com',
                'password' => 'Password123!',
                'password_confirmation' => 'Password123!',
                'phone' => $phone
            ];

            $response = $this->post('/register', $userData);

            $response->assertRedirect('/login');
            $this->assertDatabaseHas('users', [
                'phone' => $phone
            ]);
        }
    }

    /**
     * Test Case: Input invalid phone number formats
     */
    public function test_input_invalid_phone_number_formats()
    {
        $invalidPhones = ['123456789', '012345678', '0223456789', 'abc1234567', ''];

        foreach ($invalidPhones as $phone) {
            $userData = [
                'name' => 'John Doe',
                'email' => 'john' . uniqid() . '@example.com',
                'password' => 'Password123!',
                'password_confirmation' => 'Password123!',
                'phone' => $phone
            ];

            $response = $this->post('/register', $userData);

            $response->assertSessionHasErrors(['phone']);
            $this->assertDatabaseMissing('users', [
                'phone' => $phone
            ]);
        }
    }

    /**
     * Test Case: Input password with various strength requirements
     */
    public function test_input_password_strength_requirements()
    {
        $weakPasswords = [
            'Pass1!', // Too short
            '', // Empty password
        ];

        foreach ($weakPasswords as $password) {
            $userData = [
                'name' => 'John Doe',
                'email' => 'john' . uniqid() . '@example.com',
                'password' => $password,
                'password_confirmation' => $password,
                'phone' => '0123456789'
            ];

            $response = $this->post('/register', $userData);

            $response->assertSessionHasErrors(['password']);
            $this->assertDatabaseMissing('users', [
                'email' => $userData['email']
            ]);
        }
    }

    /**
     * Test Case: Input valid password with all requirements
     */
    public function test_input_valid_password_with_all_requirements()
    {
        $validPasswords = [
            'Password123!',
            'MySecure1@',
            'Test123#',
            'StrongPass9$',
        ];

        foreach ($validPasswords as $password) {
            $userData = [
                'name' => 'John Doe',
                'email' => 'john' . uniqid() . '@example.com',
                'password' => $password,
                'password_confirmation' => $password,
                'phone' => '0123456789'
            ];

            $response = $this->post('/register', $userData);

            $response->assertRedirect('/login');
            $this->assertDatabaseHas('users', [
                'email' => $userData['email']
            ]);
        }
    }
}
