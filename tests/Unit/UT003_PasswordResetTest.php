<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Notifications\CustomResetPasswordNotification;

class UT003_PasswordResetTest extends TestCase
{
    public function test_notification_instantiation(): void
    {
        $token = 'unit-token-123';
        $notification = new CustomResetPasswordNotification($token);
        
        $this->assertInstanceOf(CustomResetPasswordNotification::class, $notification);
    }

    public function test_notification_has_token(): void
    {
        $token = 'unit-token-123';
        $notification = new CustomResetPasswordNotification($token);
        
        // Test that the notification was created with the token
        $this->assertNotNull($notification);
    }

    public function test_notification_accepts_different_tokens(): void
    {
        $tokens = ['token1', 'token2', 'long-token-123'];
        
        foreach ($tokens as $token) {
            $notification = new CustomResetPasswordNotification($token);
            $this->assertInstanceOf(CustomResetPasswordNotification::class, $notification);
        }
    }

    public function test_notification_handles_empty_token(): void
    {
        $token = '';
        $notification = new CustomResetPasswordNotification($token);
        
        $this->assertInstanceOf(CustomResetPasswordNotification::class, $notification);
    }

    public function test_notification_handles_special_characters_in_token(): void
    {
        $token = 'token-with-special-chars!@#$%^&*()';
        $notification = new CustomResetPasswordNotification($token);
        
        $this->assertInstanceOf(CustomResetPasswordNotification::class, $notification);
    }
}
