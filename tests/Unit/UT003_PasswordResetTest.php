<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Notification;
use Illuminate\Auth\Notifications\ResetPassword;

class UT003_PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test Case: Request password reset with valid email
     * 
     * Test Steps:
     * 1. Click "Forgot Password" link
     * 2. Input valid registered email
     * 3. Click "Send Reset Link" button
     * 
     * Expected Result: The system displays success message "Password reset link sent to your email"
     */
    public function test_request_password_reset_with_valid_email()
    {
        // Create a registered user
        $user = User::factory()->create([
            'email' => 'john.doe@example.com',
            'password' => Hash::make('password123')
        ]);

        Notification::fake();

        $response = $this->post('/forgot-password', [
            'email' => 'john.doe@example.com'
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Password reset link sent to your email!');

        // Verify notification was sent
        Notification::assertSentTo($user, \App\Notifications\CustomResetPasswordNotification::class);
    }

    /**
     * Test Case: Request password reset with unregistered email
     * 
     * Test Steps:
     * 1. Click "Forgot Password" link
     * 2. Input unregistered email
     * 3. Click "Send Reset Link" button
     * 
     * Expected Result: The system displays error message "We can't find a user with that email address."
     */
    public function test_request_password_reset_with_unregistered_email()
    {
        $response = $this->post('/forgot-password', [
            'email' => 'unregistered@example.com'
        ]);

        $response->assertSessionHasErrors(['email']);
        $this->assertStringContainsString("We can't find a user with that email address", $response->getSession()->get('errors')->first('email'));
    }

    /**
     * Test Case: Request password reset with invalid email format
     * 
     * Test Steps:
     * 1. Click "Forgot Password" link
     * 2. Input invalid email format
     * 3. Click "Send Reset Link" button
     * 
     * Expected Result: The system will display error message "Please enter a part following '@'. 'test@' is incomplete."
     */
    public function test_request_password_reset_with_invalid_email_format()
    {
        $response = $this->post('/forgot-password', [
            'email' => 'john.doe@'
        ]);

        $response->assertSessionHasErrors(['email']);
    }

    /**
     * Test Case: Request password reset with empty email
     * 
     * Test Steps:
     * 1. Click "Forgot Password" link
     * 2. Leave email field empty
     * 3. Click "Send Reset Link" button
     * 
     * Expected Result: The system will displays error message "Please fill out this field."
     */
    public function test_request_password_reset_with_empty_email()
    {
        $response = $this->post('/forgot-password', [
            'email' => ''
        ]);

        $response->assertSessionHasErrors(['email']);
    }

    /**
     * Test Case: Reset password with valid token
     * 
     * Test Steps:
     * 1. Click reset link from email
     * 2. Input new password: "NewPass123!"
     * 3. Input password confirmation: "NewPass123!"
     * 4. Click "Reset Password" button
     * 
     * Expected Result: The system displays success message "Password reset successfully" and redirects to login page
     */
    public function test_reset_password_with_valid_token()
    {
        // Create a registered user
        $user = User::factory()->create([
            'email' => 'john.doe@example.com',
            'password' => Hash::make('oldpassword123')
        ]);

        // Create a valid reset token
        $token = Password::createToken($user);

        $response = $this->post('/reset-password', [
            'token' => $token,
            'email' => 'john.doe@example.com',
            'password' => 'NewPass123!',
            'password_confirmation' => 'NewPass123!'
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHas('success', 'Password reset successfully! You can now log in with your new password.');

        // Verify password was updated in database
        $user->refresh();
        $this->assertTrue(Hash::check('NewPass123!', $user->password));
        $this->assertFalse(Hash::check('oldpassword123', $user->password));
    }

    /**
     * Test Case: Reset password with expired token
     * 
     * Test Steps:
     * 1. Click expired reset link from email
     * 2. Input new password: "NewPass123!"
     * 3. Input password confirmation: "NewPass123!"
     * 4. Click "Reset Password" button
     * 
     * Expected Result: The system displays error message "Reset token has expired"
     */
    public function test_reset_password_with_expired_token()
    {
        // Create a registered user
        $user = User::factory()->create([
            'email' => 'john.doe@example.com',
            'password' => Hash::make('oldpassword123')
        ]);

        // Create a token and then manually expire it by updating the database
        $token = Password::createToken($user);
        
        // Manually expire the token by updating the password_reset_tokens table
        \DB::table('password_reset_tokens')
            ->where('email', $user->email)
            ->update(['created_at' => now()->subHours(2)]); // Expire token (default is 1 hour)

        $response = $this->post('/reset-password', [
            'token' => $token,
            'email' => 'john.doe@example.com',
            'password' => 'NewPass123!',
            'password_confirmation' => 'NewPass123!'
        ]);

        $response->assertSessionHasErrors(['email']);
        $this->assertStringContainsString('This password reset token is invalid', $response->getSession()->get('errors')->first('email'));
    }

    /**
     * Test Case: Reset password with weak new password
     * 
     * Test Steps:
     * 1. Click reset link from email
     * 2. Input weak new password: "123"
     * 3. Input password confirmation: "123"
     * 4. Click "Reset Password" button
     * 
     * Expected Result: The password requirement section will show which requirement didn't match.
     */
    public function test_reset_password_with_weak_new_password()
    {
        // Create a registered user
        $user = User::factory()->create([
            'email' => 'john.doe@example.com',
            'password' => Hash::make('oldpassword123')
        ]);

        // Create a valid reset token
        $token = Password::createToken($user);

        $response = $this->post('/reset-password', [
            'token' => $token,
            'email' => 'john.doe@example.com',
            'password' => '123',
            'password_confirmation' => '123'
        ]);

        $response->assertSessionHasErrors(['password']);
    }

    /**
     * Test Case: Reset password with mismatched confirmation
     * 
     * Test Steps:
     * 1. Click reset link from email
     * 2. Input new password: "NewPass123!"
     * 3. Input different password confirmation: "DifferentPass123!"
     * 4. Click "Reset Password" button
     * 
     * Expected Result: The system displays error message "Passwords do not match"
     */
    public function test_reset_password_with_mismatched_confirmation()
    {
        // Create a registered user
        $user = User::factory()->create([
            'email' => 'john.doe@example.com',
            'password' => Hash::make('oldpassword123')
        ]);

        // Create a valid reset token
        $token = Password::createToken($user);

        $response = $this->post('/reset-password', [
            'token' => $token,
            'email' => 'john.doe@example.com',
            'password' => 'NewPass123!',
            'password_confirmation' => 'DifferentPass123!'
        ]);

        $response->assertSessionHasErrors(['password']);
    }

    /**
     * Test Case: Reset password with empty new password
     * 
     * Test Steps:
     * 1. Click reset link from email
     * 2. Leave new password field empty
     * 3. Input password confirmation: "NewPass123!"
     * 
     * Expected Result: button cannot be pressed
     */
    public function test_reset_password_with_empty_new_password()
    {
        // Create a registered user
        $user = User::factory()->create([
            'email' => 'john.doe@example.com',
            'password' => Hash::make('oldpassword123')
        ]);

        // Create a valid reset token
        $token = Password::createToken($user);

        $response = $this->post('/reset-password', [
            'token' => $token,
            'email' => 'john.doe@example.com',
            'password' => '',
            'password_confirmation' => 'NewPass123!'
        ]);

        $response->assertSessionHasErrors(['password']);
    }

    /**
     * Test Case: Reset password with invalid token
     * 
     * Test Steps:
     * 1. Click reset link from email with invalid token
     * 2. Input new password: "NewPass123!"
     * 3. Input password confirmation: "NewPass123!"
     * 4. Click "Reset Password" button
     * 
     * Expected Result: The system displays error message about invalid token
     */
    public function test_reset_password_with_invalid_token()
    {
        // Create a registered user
        $user = User::factory()->create([
            'email' => 'john.doe@example.com',
            'password' => Hash::make('oldpassword123')
        ]);

        $response = $this->post('/reset-password', [
            'token' => 'invalid_token_123',
            'email' => 'john.doe@example.com',
            'password' => 'NewPass123!',
            'password_confirmation' => 'NewPass123!'
        ]);

        $response->assertSessionHasErrors(['email']);
        $this->assertStringContainsString('This password reset token is invalid', $response->getSession()->get('errors')->first('email'));
    }

    /**
     * Test Case: Reset password with non-existent email
     * 
     * Test Steps:
     * 1. Click reset link from email
     * 2. Input new password: "NewPass123!"
     * 3. Input password confirmation: "NewPass123!"
     * 4. Click "Reset Password" button
     * 
     * Expected Result: The system displays error message about invalid email
     */
    public function test_reset_password_with_nonexistent_email()
    {
        $response = $this->post('/reset-password', [
            'token' => 'valid_token_123',
            'email' => 'nonexistent@example.com',
            'password' => 'NewPass123!',
            'password_confirmation' => 'NewPass123!'
        ]);

        $response->assertSessionHasErrors(['email']);
    }

    /**
     * Test Case: Reset password form validation with empty fields
     */
    public function test_reset_password_form_validation_with_empty_fields()
    {
        $response = $this->post('/reset-password', [
            'token' => '',
            'email' => '',
            'password' => '',
            'password_confirmation' => ''
        ]);

        $response->assertSessionHasErrors(['token', 'email', 'password']);
    }

    /**
     * Test Case: Reset password with various weak passwords
     */
    public function test_reset_password_with_various_weak_passwords()
    {
        // Create a registered user
        $user = User::factory()->create([
            'email' => 'john.doe@example.com',
            'password' => Hash::make('oldpassword123')
        ]);

        // Create a valid reset token
        $token = Password::createToken($user);

        $weakPasswords = [
            '123',           // Too short
            'Pass1!',        // Too short
        ];

        foreach ($weakPasswords as $weakPassword) {
            $response = $this->post('/reset-password', [
                'token' => $token,
                'email' => 'john.doe@example.com',
                'password' => $weakPassword,
                'password_confirmation' => $weakPassword
            ]);

            $response->assertSessionHasErrors(['password']);
        }
    }

    /**
     * Test Case: Reset password with valid strong passwords
     */
    public function test_reset_password_with_valid_strong_passwords()
    {
        // Create a registered user
        $user = User::factory()->create([
            'email' => 'john.doe@example.com',
            'password' => Hash::make('oldpassword123')
        ]);

        $validPasswords = [
            'NewPass123!',
            'MySecure1@',
            'Test123#',
            'StrongPass9$',
        ];

        foreach ($validPasswords as $validPassword) {
            // Create a new token for each test
            $token = Password::createToken($user);

            $response = $this->post('/reset-password', [
                'token' => $token,
                'email' => 'john.doe@example.com',
                'password' => $validPassword,
                'password_confirmation' => $validPassword
            ]);

            $response->assertRedirect('/login');
            $response->assertSessionHas('success', 'Password reset successfully! You can now log in with your new password.');

            // Verify password was updated
            $user->refresh();
            $this->assertTrue(Hash::check($validPassword, $user->password));
        }
    }

    /**
     * Test Case: Forgot password page accessibility
     */
    public function test_forgot_password_page_accessibility()
    {
        $response = $this->get('/forgot-password');
        $response->assertStatus(200);
        $response->assertSee('Auth/ForgotPassword');
    }

    /**
     * Test Case: Reset password page accessibility with token
     */
    public function test_reset_password_page_accessibility_with_token()
    {
        // Create a registered user
        $user = User::factory()->create([
            'email' => 'john.doe@example.com',
            'password' => Hash::make('oldpassword123')
        ]);

        // Create a valid reset token
        $token = Password::createToken($user);

        $response = $this->get('/reset-password/' . $token);
        $response->assertStatus(200);
        $response->assertSee('Auth/ResetPassword');
    }

    /**
     * Test Case: Reset password with whitespace in email
     */
    public function test_reset_password_with_whitespace_in_email()
    {
        // Create a registered user
        $user = User::factory()->create([
            'email' => 'john.doe@example.com',
            'password' => Hash::make('oldpassword123')
        ]);

        // Create a valid reset token
        $token = Password::createToken($user);

        $response = $this->post('/reset-password', [
            'token' => $token,
            'email' => '  john.doe@example.com  ', // Email with whitespace
            'password' => 'NewPass123!',
            'password_confirmation' => 'NewPass123!'
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHas('success', 'Password reset successfully! You can now log in with your new password.');

        // Verify password was updated
        $user->refresh();
        $this->assertTrue(Hash::check('NewPass123!', $user->password));
    }
}
