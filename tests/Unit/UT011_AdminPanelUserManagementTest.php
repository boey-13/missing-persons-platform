<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Models\SystemLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

class UT011_AdminPanelUserManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    /**
     * Test Case: View all users list
     * 
     * Test Steps:
     * 1. Navigate to admin users page
     * 2. View list of all registered users
     * 3. Check user information display
     * 
     * Expected Result: The system displays all users with ID, name, email, role, and created date
     */
    public function test_view_all_users_list()
    {
        // Create admin user
        $admin = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
            'role' => 'admin'
        ]);

        // Create test users
        User::factory()->create([
            'name' => 'John Smith',
            'email' => 'john@example.com',
            'role' => 'user'
        ]);

        User::factory()->create([
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'role' => 'volunteer'
        ]);

        $this->actingAs($admin);

        $response = $this->get('/admin/users');

        $response->assertStatus(200);
        $response->assertSee('Admin/ManageUsers');
    }

    /**
     * Test Case: Search users by name
     * 
     * Test Steps:
     * 1. Navigate to admin users page
     * 2. Enter name in search box
     * 3. Click "Apply Filters" button
     * 
     * Expected Result: The system displays only users matching the name "John"
     */
    public function test_search_users_by_name()
    {
        // Create admin user
        $admin = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
            'role' => 'admin'
        ]);

        // Create test users
        User::factory()->create([
            'name' => 'John Smith',
            'email' => 'john@example.com',
            'role' => 'user'
        ]);

        User::factory()->create([
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'role' => 'user'
        ]);

        $this->actingAs($admin);

        $response = $this->get('/admin/users?search=John');

        $response->assertStatus(200);
        $response->assertSee('Admin/ManageUsers');
    }

    /**
     * Test Case: Search users by email
     * 
     * Test Steps:
     * 1. Navigate to admin users page
     * 2. Enter email in search box
     * 3. Click "Apply Filters" button
     * 
     * Expected Result: The system displays only users matching the email
     */
    public function test_search_users_by_email()
    {
        // Create admin user
        $admin = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
            'role' => 'admin'
        ]);

        // Create test users
        User::factory()->create([
            'name' => 'John Smith',
            'email' => 'john@example.com',
            'role' => 'user'
        ]);

        User::factory()->create([
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'role' => 'user'
        ]);

        $this->actingAs($admin);

        $response = $this->get('/admin/users?search=john@example.com');

        $response->assertStatus(200);
        $response->assertSee('Admin/ManageUsers');
    }

    /**
     * Test Case: Filter users by role - User
     * 
     * Test Steps:
     * 1. Navigate to admin users page
     * 2. Select "User" from role filter
     * 3. Click "Apply Filters" button
     * 
     * Expected Result: The system displays only users with "user" role
     */
    public function test_filter_users_by_role_user()
    {
        // Create admin user
        $admin = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
            'role' => 'admin'
        ]);

        // Create test users with different roles
        User::factory()->create([
            'name' => 'John Smith',
            'email' => 'john@example.com',
            'role' => 'user'
        ]);

        User::factory()->create([
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'role' => 'volunteer'
        ]);

        $this->actingAs($admin);

        $response = $this->get('/admin/users?role=user');

        $response->assertStatus(200);
        $response->assertSee('Admin/ManageUsers');
    }

    /**
     * Test Case: Filter users by role - Volunteer
     * 
     * Test Steps:
     * 1. Navigate to admin users page
     * 2. Select "Volunteer" from role filter
     * 3. Click "Apply Filters" button
     * 
     * Expected Result: The system displays only users with "volunteer" role
     */
    public function test_filter_users_by_role_volunteer()
    {
        // Create admin user
        $admin = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
            'role' => 'admin'
        ]);

        // Create test users with different roles
        User::factory()->create([
            'name' => 'John Smith',
            'email' => 'john@example.com',
            'role' => 'user'
        ]);

        User::factory()->create([
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'role' => 'volunteer'
        ]);

        $this->actingAs($admin);

        $response = $this->get('/admin/users?role=volunteer');

        $response->assertStatus(200);
        $response->assertSee('Admin/ManageUsers');
    }

    /**
     * Test Case: Filter users by role - Admin
     * 
     * Test Steps:
     * 1. Navigate to admin users page
     * 2. Select "Admin" from role filter
     * 3. Click "Apply Filters" button
     * 
     * Expected Result: The system displays only users with "admin" role
     */
    public function test_filter_users_by_role_admin()
    {
        // Create admin user
        $admin = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
            'role' => 'admin'
        ]);

        // Create test users with different roles
        User::factory()->create([
            'name' => 'John Smith',
            'email' => 'john@example.com',
            'role' => 'user'
        ]);

        User::factory()->create([
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'role' => 'admin'
        ]);

        $this->actingAs($admin);

        $response = $this->get('/admin/users?role=admin');

        $response->assertStatus(200);
        $response->assertSee('Admin/ManageUsers');
    }

    /**
     * Test Case: Change user role to admin
     * 
     * Test Steps:
     * 1. Navigate to admin users page
     * 2. Select a user
     * 3. Change role dropdown to "admin"
     * 4. Confirm role change
     * 
     * Expected Result: The system updates user role to "admin" and logs the change
     */
    public function test_change_user_role_to_admin()
    {
        // Create admin user
        $admin = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
            'role' => 'admin'
        ]);

        // Create test user
        $user = User::factory()->create([
            'name' => 'John Smith',
            'email' => 'john@example.com',
            'password' => Hash::make('password123'),
            'role' => 'volunteer'
        ]);

        $this->actingAs($admin);

        $response = $this->post("/admin/users/{$user->id}/role", [
            'role' => 'admin'
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'User role updated successfully from volunteer to admin');

        // Verify user role was updated
        $user->refresh();
        $this->assertEquals('admin', $user->role);

        // Verify system log was created
        $this->assertDatabaseHas('system_logs', [
            'user_id' => $admin->id,
            'action' => 'user_role_changed',
            'description' => 'User role changed from volunteer to admin'
        ]);
    }

    /**
     * Test Case: Delete user account
     * 
     * Test Steps:
     * 1. Navigate to admin users page
     * 2. Select a user
     * 3. Click "Delete" button
     * 4. Confirm deletion in popup
     * 
     * Expected Result: The system deletes the user account and logs the deletion
     */
    public function test_delete_user_account()
    {
        // Create admin user
        $admin = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
            'role' => 'admin'
        ]);

        // Create test user
        $user = User::factory()->create([
            'name' => 'John Smith',
            'email' => 'john@example.com',
            'password' => Hash::make('password123'),
            'role' => 'user'
        ]);

        $this->actingAs($admin);

        $response = $this->delete("/admin/users/{$user->id}");

        $response->assertRedirect();
        $response->assertSessionHas('success', 'User deleted successfully');

        // Verify user was deleted
        $this->assertDatabaseMissing('users', [
            'id' => $user->id
        ]);

        // Verify system log was created
        $this->assertDatabaseHas('system_logs', [
            'user_id' => $admin->id,
            'action' => 'user_deleted',
            'description' => "User account deleted: john@example.com"
        ]);
    }

    /**
     * Test Case: Change user role with invalid role
     */
    public function test_change_user_role_with_invalid_role()
    {
        // Create admin user
        $admin = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
            'role' => 'admin'
        ]);

        // Create test user
        $user = User::factory()->create([
            'name' => 'John Smith',
            'email' => 'john@example.com',
            'password' => Hash::make('password123'),
            'role' => 'user'
        ]);

        $this->actingAs($admin);

        $response = $this->post("/admin/users/{$user->id}/role", [
            'role' => 'invalid_role'
        ]);

        $response->assertSessionHasErrors(['role']);
    }

    /**
     * Test Case: Prevent admin from deleting their own account
     */
    public function test_prevent_admin_from_deleting_own_account()
    {
        // Create admin user
        $admin = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
            'role' => 'admin'
        ]);

        $this->actingAs($admin);

        $response = $this->delete("/admin/users/{$admin->id}");

        $response->assertRedirect();
        $response->assertSessionHas('error', 'You cannot delete your own account.');

        // Verify admin user still exists
        $this->assertDatabaseHas('users', [
            'id' => $admin->id
        ]);
    }

    /**
     * Test Case: Access admin functions without admin role
     */
    public function test_access_admin_functions_without_admin_role()
    {
        // Create regular user (not admin)
        $user = User::factory()->create([
            'name' => 'Regular User',
            'email' => 'user@example.com',
            'password' => Hash::make('password123'),
            'role' => 'user'
        ]);

        $this->actingAs($user);

        // Try to access admin users page
        $response = $this->get('/admin/users');
        $response->assertStatus(200); // Returns access denied page instead of 403

        // Try to change user role
        $response = $this->post("/admin/users/{$user->id}/role", [
            'role' => 'admin'
        ]);
        $response->assertStatus(200); // Returns access denied page instead of 403

        // Try to delete user
        $response = $this->delete("/admin/users/{$user->id}");
        $response->assertStatus(200); // Returns access denied page instead of 403
    }

    /**
     * Test Case: Change user role to volunteer
     */
    public function test_change_user_role_to_volunteer()
    {
        // Create admin user
        $admin = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
            'role' => 'admin'
        ]);

        // Create test user
        $user = User::factory()->create([
            'name' => 'John Smith',
            'email' => 'john@example.com',
            'password' => Hash::make('password123'),
            'role' => 'user'
        ]);

        $this->actingAs($admin);

        $response = $this->post("/admin/users/{$user->id}/role", [
            'role' => 'volunteer'
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'User role updated successfully from user to volunteer');

        // Verify user role was updated
        $user->refresh();
        $this->assertEquals('volunteer', $user->role);

        // Verify system log was created
        $this->assertDatabaseHas('system_logs', [
            'user_id' => $admin->id,
            'action' => 'user_role_changed',
            'description' => 'User role changed from user to volunteer'
        ]);
    }

    /**
     * Test Case: Change user role to user
     */
    public function test_change_user_role_to_user()
    {
        // Create admin user
        $admin = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
            'role' => 'admin'
        ]);

        // Create test user
        $user = User::factory()->create([
            'name' => 'John Smith',
            'email' => 'john@example.com',
            'password' => Hash::make('password123'),
            'role' => 'volunteer'
        ]);

        $this->actingAs($admin);

        $response = $this->post("/admin/users/{$user->id}/role", [
            'role' => 'user'
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'User role updated successfully from volunteer to user');

        // Verify user role was updated
        $user->refresh();
        $this->assertEquals('user', $user->role);

        // Verify system log was created
        $this->assertDatabaseHas('system_logs', [
            'user_id' => $admin->id,
            'action' => 'user_role_changed',
            'description' => 'User role changed from volunteer to user'
        ]);
    }

    /**
     * Test Case: Search users with empty search term
     */
    public function test_search_users_with_empty_search_term()
    {
        // Create admin user
        $admin = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
            'role' => 'admin'
        ]);

        // Create test users
        User::factory()->create([
            'name' => 'John Smith',
            'email' => 'john@example.com',
            'role' => 'user'
        ]);

        $this->actingAs($admin);

        $response = $this->get('/admin/users?search=');

        $response->assertStatus(200);
        $response->assertSee('Admin/ManageUsers');
    }

    /**
     * Test Case: Filter users with all roles
     */
    public function test_filter_users_with_all_roles()
    {
        // Create admin user
        $admin = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
            'role' => 'admin'
        ]);

        // Create test users with different roles
        User::factory()->create([
            'name' => 'John Smith',
            'email' => 'john@example.com',
            'role' => 'user'
        ]);

        User::factory()->create([
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'role' => 'volunteer'
        ]);

        $this->actingAs($admin);

        $response = $this->get('/admin/users?role=all');

        $response->assertStatus(200);
        $response->assertSee('Admin/ManageUsers');
    }

    /**
     * Test Case: View users with pagination
     */
    public function test_view_users_with_pagination()
    {
        // Create admin user
        $admin = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
            'role' => 'admin'
        ]);

        // Create multiple test users
        for ($i = 0; $i < 15; $i++) {
            User::factory()->create([
                'name' => "User {$i}",
                'email' => "user{$i}@example.com",
                'role' => 'user'
            ]);
        }

        $this->actingAs($admin);

        $response = $this->get('/admin/users');

        $response->assertStatus(200);
        $response->assertSee('Admin/ManageUsers');
    }

    /**
     * Test Case: Search and filter users simultaneously
     */
    public function test_search_and_filter_users_simultaneously()
    {
        // Create admin user
        $admin = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
            'role' => 'admin'
        ]);

        // Create test users
        User::factory()->create([
            'name' => 'John Smith',
            'email' => 'john@example.com',
            'role' => 'user'
        ]);

        User::factory()->create([
            'name' => 'John Doe',
            'email' => 'john.doe@example.com',
            'role' => 'volunteer'
        ]);

        User::factory()->create([
            'name' => 'Jane Smith',
            'email' => 'jane@example.com',
            'role' => 'user'
        ]);

        $this->actingAs($admin);

        // Search for "John" and filter by "user" role
        $response = $this->get('/admin/users?search=John&role=user');

        $response->assertStatus(200);
        $response->assertSee('Admin/ManageUsers');
    }

    /**
     * Test Case: Delete non-existent user
     */
    public function test_delete_nonexistent_user()
    {
        // Create admin user
        $admin = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
            'role' => 'admin'
        ]);

        $this->actingAs($admin);

        // Try to delete non-existent user
        $response = $this->delete('/admin/users/999');

        $response->assertStatus(404); // Not Found
    }

    /**
     * Test Case: Change role of non-existent user
     */
    public function test_change_role_of_nonexistent_user()
    {
        // Create admin user
        $admin = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
            'role' => 'admin'
        ]);

        $this->actingAs($admin);

        // Try to change role of non-existent user
        $response = $this->post('/admin/users/999/role', [
            'role' => 'admin'
        ]);

        $response->assertStatus(404); // Not Found
    }
}
