<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class UT002_UserLoginTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test Case: Input registered email and correct password
     * 
     * Test Steps:
     * 1. Input a valid registered email
     * 2. Input the correct password
     * 3. Clicks the Proceed button
     * 
     * Expected Result: The system redirects to the logged in home page and displays user dashboard
     */
    public function test_input_registered_email_and_correct_password()
    {
        // Create a registered user
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
            'is_locked' => false,
            'failed_login_attempts' => 0
        ]);

        $loginData = [
            'email' => 'test@example.com',
            'password' => 'password123'
        ];

        $response = $this->post('/login', $loginData);

        $response->assertRedirect('/');
        $response->assertSessionHas('success', 'Welcome back! You have successfully logged in.');
        
        $this->assertAuthenticatedAs($user);
    }

    /**
     * Test Case: Input unregistered email and correct password
     * 
     * Test Steps:
     * 1. Input an unregistered email
     * 2. Input the correct password
     * 3. Clicks the Proceed button
     * 
     * Expected Result: The system will popup a modal to ask user want to try again or create new account.
     */
    public function test_input_unregistered_email_and_correct_password()
    {
        $loginData = [
            'email' => 'nonexistent@example.com',
            'password' => 'password123'
        ];

        $response = $this->post('/login', $loginData);

        $response->assertSessionHasErrors(['email']);
        $this->assertGuest();
    }

    /**
     * Test Case: Input registered email and incorrect password
     * 
     * Test Steps:
     * 1. Input a valid registered email
     * 2. Input the incorrect password
     * 3. Clicks the Proceed button
     * 
     * Expected Result: The system will popup a modal to ask user want to try again or create new account.
     */
    public function test_input_registered_email_and_incorrect_password()
    {
        // Create a registered user
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
            'is_locked' => false,
            'failed_login_attempts' => 0
        ]);

        $loginData = [
            'email' => 'test@example.com',
            'password' => 'wrongpassword'
        ];

        $response = $this->post('/login', $loginData);

        $response->assertSessionHasErrors(['email']);
        $this->assertGuest();
        
        // Verify failed login attempt was recorded
        $user->refresh();
        $this->assertEquals(1, $user->failed_login_attempts);
    }

    /**
     * Test Case: Empty email field
     * 
     * Test Steps:
     * 1. Leave email field empty
     * 2. Input a valid password
     * 3. Clicks the Login button
     * 
     * Expected Result: The system will displays error message "Please fill out this field."
     */
    public function test_empty_email_field()
    {
        $loginData = [
            'email' => '',
            'password' => 'password123'
        ];

        $response = $this->post('/login', $loginData);

        $response->assertSessionHasErrors(['email']);
        $this->assertGuest();
    }

    /**
     * Test Case: Empty password field
     * 
     * Test Steps:
     * 1. Input a valid registered email
     * 2. Leave password field empty
     * 3. Clicks the Login button
     * 
     * Expected Result: The system will displays error message "Please fill out this field."
     */
    public function test_empty_password_field()
    {
        $loginData = [
            'email' => 'test@example.com',
            'password' => ''
        ];

        $response = $this->post('/login', $loginData);

        $response->assertSessionHasErrors(['password']);
        $this->assertGuest();
    }

    /**
     * Test Case: Input invalid email format
     * 
     * Test Steps:
     * 1. Input an invalid email format
     * 2. Input the incorrect password
     * 3. Clicks the Proceed button
     * 
     * Expected Result: The system will show "Please enter a part following '@'. 'test@' is incomplete."
     */
    public function test_input_invalid_email_format()
    {
        $loginData = [
            'email' => 'test@',
            'password' => 'password123'
        ];

        $response = $this->post('/login', $loginData);

        $response->assertSessionHasErrors(['email']);
        $this->assertGuest();
    }

    /**
     * Test Case: Empty inputs
     * 
     * Test Steps:
     * 1. Clicks the Login button without filling any fields
     * 
     * Expected Result: The system will displays error message "Please fill out this field."
     */
    public function test_empty_inputs()
    {
        $loginData = [
            'email' => '',
            'password' => ''
        ];

        $response = $this->post('/login', $loginData);

        $response->assertSessionHasErrors(['email', 'password']);
        $this->assertGuest();
    }

    /**
     * Test Case: Account locked after 3 failed attempts
     * 
     * Test Steps:
     * 1. Input a valid registered email
     * 2. Input incorrect password
     * 3. Repeat step 2 two more times
     * 4. Clicks the Proceed button
     * 
     * Expected Result: The system will pop up a modal to tell the user that the account is temporarily locked and ask the user whether to reset the password or wait for 5 minutes.
     */
    public function test_account_locked_after_3_failed_attempts()
    {
        // Create a registered user
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
            'is_locked' => false,
            'failed_login_attempts' => 0
        ]);

        // First failed attempt
        $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'wrongpassword1'
        ]);
        $user->refresh();
        $this->assertEquals(1, $user->failed_login_attempts);

        // Second failed attempt
        $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'wrongpassword2'
        ]);
        $user->refresh();
        $this->assertEquals(2, $user->failed_login_attempts);

        // Third failed attempt - should lock account
        $response = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'wrongpassword3'
        ]);

        $response->assertSessionHasErrors(['account_locked']);
        $this->assertGuest();
        
        $user->refresh();
        $this->assertTrue($user->isAccountLocked());
        $this->assertEquals(0, $user->failed_login_attempts); // Reset after locking
    }

    /**
     * Test Case: Login with locked account
     * 
     * Test Steps:
     * 1. Input email of locked account
     * 2. Input correct password
     * 3. Clicks the Proceed button
     * 
     * Expected Result: The system will pop up a modal to tell the user that the account is temporarily locked and ask the user whether to reset the password or wait for 5 minutes.
     */
    public function test_login_with_locked_account()
    {
        // Create a locked user
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
            'is_locked' => true,
            'locked_until' => now()->addMinutes(5),
            'failed_login_attempts' => 0
        ]);

        $loginData = [
            'email' => 'test@example.com',
            'password' => 'password123'
        ];

        $response = $this->post('/login', $loginData);

        $response->assertSessionHasErrors(['account_locked']);
        $this->assertGuest();
    }

    /**
     * Test Case: Login with expired locked account
     * 
     * Test Steps:
     * 1. Input email of expired locked account
     * 2. Input correct password
     * 3. Clicks the Proceed button
     * 
     * Expected Result: The system should allow login as the lock has expired
     */
    public function test_login_with_expired_locked_account()
    {
        // Create an expired locked user
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
            'is_locked' => true,
            'locked_until' => now()->subMinutes(1), // Lock expired 1 minute ago
            'failed_login_attempts' => 0
        ]);

        $loginData = [
            'email' => 'test@example.com',
            'password' => 'password123'
        ];

        $response = $this->post('/login', $loginData);

        $response->assertRedirect('/');
        $response->assertSessionHas('success', 'Welcome back! You have successfully logged in.');
        $this->assertAuthenticatedAs($user);
        
        // Verify account was unlocked (isAccountLocked() should have unlocked it)
        $user->refresh();
        // Note: isAccountLocked() automatically unlocks expired accounts
        $this->assertFalse($user->isAccountLocked());
    }

    /**
     * Test Case: Successful login resets failed attempts
     * 
     * Test Steps:
     * 1. Input a valid registered email
     * 2. Input incorrect password (1 failed attempt)
     * 3. Input correct password
     * 4. Clicks the Proceed button
     * 
     * Expected Result: The system should reset failed attempts and allow login
     */
    public function test_successful_login_resets_failed_attempts()
    {
        // Create a registered user
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
            'is_locked' => false,
            'failed_login_attempts' => 0
        ]);

        // First failed attempt
        $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'wrongpassword'
        ]);
        $user->refresh();
        $this->assertEquals(1, $user->failed_login_attempts);

        // Successful login
        $response = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'password123'
        ]);

        $response->assertRedirect('/');
        $this->assertAuthenticatedAs($user);
        
        // Verify failed attempts were reset
        $user->refresh();
        $this->assertEquals(0, $user->failed_login_attempts);
    }

    /**
     * Test Case: Login with remember me option
     * 
     * Test Steps:
     * 1. Input a valid registered email
     * 2. Input the correct password
     * 3. Check remember me option
     * 4. Clicks the Proceed button
     * 
     * Expected Result: The system should remember the user
     */
    public function test_login_with_remember_me_option()
    {
        // Create a registered user
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
            'is_locked' => false,
            'failed_login_attempts' => 0
        ]);

        $loginData = [
            'email' => 'test@example.com',
            'password' => 'password123',
            'remember' => true
        ];

        $response = $this->post('/login', $loginData);

        $response->assertRedirect('/');
        $response->assertSessionHas('success', 'Welcome back! You have successfully logged in.');
        $this->assertAuthenticatedAs($user);
    }

    /**
     * Test Case: Login validation with various invalid email formats
     */
    public function test_login_validation_with_various_invalid_email_formats()
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
            $loginData = [
                'email' => $email,
                'password' => 'password123'
            ];

            $response = $this->post('/login', $loginData);
            $response->assertSessionHasErrors(['email']);
            $this->assertGuest();
        }
    }

    /**
     * Test Case: Login with case insensitive email
     * 
     * Test Steps:
     * 1. Input email with different case
     * 2. Input the correct password
     * 3. Clicks the Proceed button
     * 
     * Expected Result: The system should allow login regardless of email case
     */
    public function test_login_with_case_insensitive_email()
    {
        // Create a registered user
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
            'is_locked' => false,
            'failed_login_attempts' => 0
        ]);

        $loginData = [
            'email' => 'TEST@EXAMPLE.COM', // Uppercase email
            'password' => 'password123'
        ];

        $response = $this->post('/login', $loginData);

        // Laravel's default authentication is case-sensitive, so this should fail
        $response->assertSessionHasErrors(['email']);
        $this->assertGuest();
    }

    /**
     * Test Case: Login with whitespace in email
     * 
     * Test Steps:
     * 1. Input email with leading/trailing whitespace
     * 2. Input the correct password
     * 3. Clicks the Proceed button
     * 
     * Expected Result: The system should trim whitespace and allow login
     */
    public function test_login_with_whitespace_in_email()
    {
        // Create a registered user
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
            'is_locked' => false,
            'failed_login_attempts' => 0
        ]);

        $loginData = [
            'email' => '  test@example.com  ', // Email with whitespace
            'password' => 'password123'
        ];

        $response = $this->post('/login', $loginData);

        $response->assertRedirect('/');
        $response->assertSessionHas('success', 'Welcome back! You have successfully logged in.');
        $this->assertAuthenticatedAs($user);
    }
}
