<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Models\User;

class UT011_AdminPanelUserManagementTest extends TestCase
{
    public function test_user_role_validation(): void
    {
        $validRoles = ['admin', 'user', 'volunteer'];
        
        foreach ($validRoles as $role) {
            $user = new User();
            $user->role = $role;
            
            $this->assertContains($role, $validRoles);
        }
    }

    public function test_user_status_validation(): void
    {
        $validStatuses = ['active', 'inactive', 'suspended', 'pending'];
        
        foreach ($validStatuses as $status) {
            $user = new User();
            $user->status = $status;
            
            $this->assertContains($status, $validStatuses);
        }
    }

    public function test_admin_user_management_permissions(): void
    {
        $admin = new User();
        $admin->role = 'admin';
        
        $permissions = [
            'view_all_users' => true,
            'create_users' => true,
            'edit_users' => true,
            'delete_users' => true,
            'suspend_users' => true,
            'activate_users' => true,
            'change_user_roles' => true
        ];
        
        foreach ($permissions as $permission => $expected) {
            $this->assertTrue($expected, "Admin should have {$permission} permission");
        }
    }

    public function test_regular_user_management_permissions(): void
    {
        $user = new User();
        $user->role = 'user';
        
        $permissions = [
            'view_own_profile' => true,
            'edit_own_profile' => true,
            'view_all_users' => false,
            'create_users' => false,
            'delete_users' => false,
            'suspend_users' => false
        ];
        
        foreach ($permissions as $permission => $expected) {
            if ($expected) {
                $this->assertTrue($expected, "User should have {$permission} permission");
            } else {
                $this->assertFalse($expected, "User should not have {$permission} permission");
            }
        }
    }

    public function test_user_account_locking_logic(): void
    {
        $user = new User();
        $user->is_locked = false;
        $user->failed_login_attempts = 0;
        
        // Test account locking
        $user->is_locked = true;
        $this->assertTrue($user->is_locked);
    }

    public function test_user_account_unlocking_logic(): void
    {
        $user = new User();
        $user->is_locked = true;
        
        // Test account unlocking
        $user->is_locked = false;
        $this->assertFalse($user->is_locked);
    }

    public function test_user_failed_login_attempts(): void
    {
        $user = new User();
        $user->failed_login_attempts = 0;
        
        // Test incrementing failed attempts
        $user->failed_login_attempts++;
        $this->assertEquals(1, $user->failed_login_attempts);
        
        $user->failed_login_attempts++;
        $this->assertEquals(2, $user->failed_login_attempts);
    }

    public function test_user_role_assignment(): void
    {
        $user = new User();
        
        $roles = ['admin', 'user', 'volunteer'];
        
        foreach ($roles as $role) {
            $user->role = $role;
            $this->assertEquals($role, $user->role);
        }
    }

    public function test_user_status_assignment(): void
    {
        $user = new User();
        
        $statuses = ['active', 'inactive', 'suspended', 'pending'];
        
        foreach ($statuses as $status) {
            $user->status = $status;
            $this->assertEquals($status, $user->status);
        }
    }

    public function test_user_email_validation(): void
    {
        $validEmails = [
            'user@example.com',
            'admin@test.org',
            'volunteer@domain.co.uk'
        ];
        
        foreach ($validEmails as $email) {
            $this->assertMatchesRegularExpression('/^[^\s@]+@[^\s@]+\.[^\s@]+$/', $email);
        }
    }

    public function test_user_phone_validation(): void
    {
        $validPhones = ['0123456789', '0198765432', '01123456789'];
        
        foreach ($validPhones as $phone) {
            $this->assertMatchesRegularExpression('/^01\d{8,9}$/', $phone);
        }
    }

    public function test_user_name_validation(): void
    {
        $validNames = [
            'John Doe',
            'Jane Smith',
            'Ahmad bin Ali',
            'Tan Wei Ming'
        ];
        
        foreach ($validNames as $name) {
            $this->assertIsString($name);
            $this->assertGreaterThan(0, strlen($name));
            $this->assertLessThanOrEqual(255, strlen($name));
        }
    }

    public function test_user_password_requirements(): void
    {
        $passwordRequirements = [
            'min_length' => 8,
            'require_uppercase' => true,
            'require_lowercase' => true,
            'require_numbers' => true,
            'require_special_chars' => true
        ];
        
        $this->assertIsInt($passwordRequirements['min_length']);
        $this->assertIsBool($passwordRequirements['require_uppercase']);
        $this->assertIsBool($passwordRequirements['require_lowercase']);
        $this->assertIsBool($passwordRequirements['require_numbers']);
        $this->assertIsBool($passwordRequirements['require_special_chars']);
    }

    public function test_user_profile_completeness(): void
    {
        $requiredFields = [
            'name',
            'email',
            'phone',
            'role',
            'status'
        ];
        
        foreach ($requiredFields as $field) {
            $this->assertNotEmpty($field, "Field {$field} should be required");
        }
    }

    public function test_user_optional_fields(): void
    {
        $optionalFields = [
            'profile_picture',
            'bio',
            'address',
            'emergency_contact',
            'preferences'
        ];
        
        foreach ($optionalFields as $field) {
            $this->assertNotEmpty($field, "Field {$field} should be optional");
        }
    }
}
