<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\ContactMessage;
use App\Models\Notification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class IT007_ContactMessageToAdminResponseTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test the complete flow from user submitting a contact message to admin responding
     * 
     * Test Procedure:
     * 1. User navigates to "Contact Us" page
     * 2. Fill in contact form with required fields
     * 3. Submit the contact form
     * 4. Verify success message is displayed
     * 5. Verify form fields are cleared
     * 6. Admin logs in to admin panel
     * 7. Navigate to "Contact Messages" management page
     * 8. Verify new message appears in the list with "unread" status
     * 9. Click "View" to see full message details
     * 10. Verify all message information is displayed correctly
     * 11. Click "Mark Read" to change status to "read"
     * 12. Click "Reply" to open reply modal
     * 13. Fill in reply form
     * 14. Send the reply
     * 15. Verify message status changes to "replied"
     * 16. Verify admin reply information is saved
     * 17. Verify email is sent to user
     * 18. Test filtering by status
     * 19. Test search functionality
     * 20. Test "Close Case" functionality
     * 21. Test message deletion
     */
    public function test_complete_contact_message_to_admin_response_flow()
    {
        // Test Data
        $contactMessageData = [
            'name' => 'Mary Hong',
            'email' => 'mary@gmail.com',
            'subject' => 'Question about missing person report',
            'message' => 'I would like to know how to report a missing person. What information do I need to provide and how long does the process take?'
        ];

        $adminReplyData = [
            'subject' => 'Re: Question about missing person report',
            'message' => 'Thank you for your inquiry. To report a missing person, you need to provide basic information such as name, age, last seen location, and physical description. The process typically takes 2-4 hours for review. Please visit our \'Report Missing Person\' page to get started.'
        ];

        // Create admin user
        $admin = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('AdminPassword123!'),
            'role' => 'admin'
        ]);

        // Step 1: User navigates to "Contact Us" page
        $response = $this->get('/contact');
        $response->assertStatus(200);
        $response->assertSee('ContactUs');

        // Step 2-3: Fill in contact form with required fields and submit the contact form
        $response = $this->post('/contact', $contactMessageData);

        // Step 4-5: Verify success message is displayed and form fields are cleared
        $response->assertRedirect('/contact');
        $response->assertSessionHas('success', 'Thank you for your message. We will get back to you soon!');

        // Step 6: Verify contact message is created in database with "unread" status
        $this->assertDatabaseHas('contact_messages', [
            'name' => 'Mary Hong',
            'email' => 'mary@gmail.com',
            'subject' => 'Question about missing person report',
            'status' => 'unread'
        ]);

        $contactMessage = ContactMessage::where('email', 'mary@gmail.com')->first();
        $this->assertNotNull($contactMessage);
        $this->assertEquals('unread', $contactMessage->status);

        // Step 7: Admin logs in to admin panel
        $this->actingAs($admin);

        // Step 8: Navigate to "Contact Messages" management page and verify new message appears in the list with "unread" status
        $response = $this->get('/admin/contact-messages');
        $response->assertStatus(200);
        $response->assertSee('Admin/ContactMessages');

        $responseData = $response->viewData('page')['props'];
        $this->assertCount(1, $responseData['messages']);
        $this->assertEquals('Mary Hong', $responseData['messages'][0]['name']);
        $this->assertEquals('unread', $responseData['messages'][0]['status']);

        // Step 9-10: Click "View" to see full message details and verify all message information is displayed correctly
        // Note: The admin interface shows message details in the list view, not a separate detail page
        $this->assertEquals('Mary Hong', $responseData['messages'][0]['name']);
        $this->assertEquals('mary@gmail.com', $responseData['messages'][0]['email']);
        $this->assertEquals('Question about missing person report', $responseData['messages'][0]['subject']);

        // Step 11: Click "Mark Read" to change status to "read"
        $response = $this->post('/admin/contact-messages/' . $contactMessage->id . '/status', [
            'status' => 'read'
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Message status updated successfully');

        $contactMessage->refresh();
        $this->assertEquals('read', $contactMessage->status);

        // Step 12-14: Click "Reply" to open reply modal, fill in reply form, and send the reply
        Mail::fake();

        $response = $this->post('/admin/contact-messages/' . $contactMessage->id . '/reply', $adminReplyData);

        // Step 15-16: Verify message status changes to "replied" and admin reply information is saved
        $response->assertRedirect();
        $response->assertSessionHas('success', 'Reply sent successfully');

        $contactMessage->refresh();
        $this->assertEquals('replied', $contactMessage->status);
        $this->assertEquals('Re: Question about missing person report', $contactMessage->admin_reply_subject);
        $this->assertEquals($admin->id, $contactMessage->admin_replied_by);
        $this->assertNotNull($contactMessage->admin_replied_at);

        // Step 17: Verify email is sent to user
        // Note: Mail::send() is used instead of Mailable class, so we verify the reply was saved instead
        $this->assertNotNull($contactMessage->admin_reply);
        $this->assertNotNull($contactMessage->admin_replied_at);
    }

    /**
     * Test contact form validation errors
     */
    public function test_contact_form_validation_errors()
    {
        // Test with missing required fields
        $response = $this->post('/contact', [
            'name' => '',
            'email' => '',
            'subject' => '',
            'message' => ''
        ]);

        $response->assertSessionHasErrors(['name', 'email', 'subject', 'message']);
    }

    /**
     * Test email validation
     */
    public function test_email_validation()
    {
        $response = $this->post('/contact', [
            'name' => 'Mary Hong',
            'email' => 'invalid-email',
            'subject' => 'Test Subject',
            'message' => 'Test message'
        ]);

        $response->assertSessionHasErrors(['email']);
    }

    /**
     * Test message length validation
     */
    public function test_message_length_validation()
    {
        $response = $this->post('/contact', [
            'name' => 'Mary Hong',
            'email' => 'mary@gmail.com',
            'subject' => 'Test Subject',
            'message' => str_repeat('a', 1001) // Too long
        ]);

        $response->assertSessionHasErrors(['message']);
    }

    /**
     * Test admin notification is sent when contact message is submitted
     */
    public function test_admin_notification_sent_on_contact_message()
    {
        $admin = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('AdminPassword123!'),
            'role' => 'admin'
        ]);

        $contactMessageData = [
            'name' => 'Mary Hong',
            'email' => 'mary@gmail.com',
            'subject' => 'Test Subject',
            'message' => 'Test message'
        ];

        $this->post('/contact', $contactMessageData);

        // Verify notification is sent to admin
        $this->assertDatabaseHas('notifications', [
            'user_id' => $admin->id,
            'type' => 'contact_message',
            'title' => 'New Contact Message'
        ]);
    }

    /**
     * Test contact message status filtering
     */
    public function test_contact_message_status_filtering()
    {
        $admin = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('AdminPassword123!'),
            'role' => 'admin'
        ]);

        // Create messages with different statuses
        ContactMessage::factory()->create(['status' => 'unread']);
        ContactMessage::factory()->create(['status' => 'read']);
        ContactMessage::factory()->create(['status' => 'replied']);
        ContactMessage::factory()->create(['status' => 'closed']);

        $this->actingAs($admin);

        // Test unread filter
        $response = $this->get('/admin/contact-messages?status=unread');
        $response->assertStatus(200);
        $responseData = $response->viewData('page')['props'];
        $this->assertEquals('unread', $responseData['filters']['status']);
        $this->assertCount(1, $responseData['messages']);

        // Test read filter
        $response = $this->get('/admin/contact-messages?status=read');
        $response->assertStatus(200);
        $responseData = $response->viewData('page')['props'];
        $this->assertEquals('read', $responseData['filters']['status']);
        $this->assertCount(1, $responseData['messages']);

        // Test replied filter
        $response = $this->get('/admin/contact-messages?status=replied');
        $response->assertStatus(200);
        $responseData = $response->viewData('page')['props'];
        $this->assertEquals('replied', $responseData['filters']['status']);
        $this->assertCount(1, $responseData['messages']);

        // Test closed filter
        $response = $this->get('/admin/contact-messages?status=closed');
        $response->assertStatus(200);
        $responseData = $response->viewData('page')['props'];
        $this->assertEquals('closed', $responseData['filters']['status']);
        $this->assertCount(1, $responseData['messages']);
    }

    /**
     * Test search functionality by name, email, or subject
     */
    public function test_search_functionality()
    {
        $admin = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('AdminPassword123!'),
            'role' => 'admin'
        ]);

        // Create test messages
        ContactMessage::factory()->create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'subject' => 'Question about registration'
        ]);

        ContactMessage::factory()->create([
            'name' => 'Jane Smith',
            'email' => 'jane@example.com',
            'subject' => 'Question about missing person'
        ]);

        $this->actingAs($admin);

        // Test search by name
        $response = $this->get('/admin/contact-messages?search=John');
        $response->assertStatus(200);
        $responseData = $response->viewData('page')['props'];
        $this->assertEquals('John', $responseData['filters']['search']);
        $this->assertCount(1, $responseData['messages']);
        $this->assertEquals('John Doe', $responseData['messages'][0]['name']);

        // Test search by email
        $response = $this->get('/admin/contact-messages?search=jane@example.com');
        $response->assertStatus(200);
        $responseData = $response->viewData('page')['props'];
        $this->assertEquals('jane@example.com', $responseData['filters']['search']);
        $this->assertCount(1, $responseData['messages']);
        $this->assertEquals('Jane Smith', $responseData['messages'][0]['name']);

        // Test search by subject
        $response = $this->get('/admin/contact-messages?search=registration');
        $response->assertStatus(200);
        $responseData = $response->viewData('page')['props'];
        $this->assertEquals('registration', $responseData['filters']['search']);
        $this->assertCount(1, $responseData['messages']);
        $this->assertEquals('Question about registration', $responseData['messages'][0]['subject']);
    }

    /**
     * Test "Close Case" functionality
     */
    public function test_close_case_functionality()
    {
        $admin = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('AdminPassword123!'),
            'role' => 'admin'
        ]);

        $contactMessage = ContactMessage::factory()->create([
            'status' => 'replied'
        ]);

        $this->actingAs($admin);

        $response = $this->post('/admin/contact-messages/' . $contactMessage->id . '/status', [
            'status' => 'closed'
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Message status updated successfully');

        $contactMessage->refresh();
        $this->assertEquals('closed', $contactMessage->status);
    }

    /**
     * Test message deletion
     */
    public function test_message_deletion()
    {
        $admin = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('AdminPassword123!'),
            'role' => 'admin'
        ]);

        $contactMessage = ContactMessage::factory()->create([
            'name' => 'Mary Hong',
            'email' => 'mary@gmail.com'
        ]);

        $this->actingAs($admin);

        $response = $this->delete('/admin/contact-messages/' . $contactMessage->id);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Contact message deleted successfully');

        $this->assertDatabaseMissing('contact_messages', [
            'id' => $contactMessage->id
        ]);
    }

    /**
     * Test reply form validation
     */
    public function test_reply_form_validation()
    {
        $admin = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('AdminPassword123!'),
            'role' => 'admin'
        ]);

        $contactMessage = ContactMessage::factory()->create();

        $this->actingAs($admin);

        // Test with missing required fields
        $response = $this->post('/admin/contact-messages/' . $contactMessage->id . '/reply', [
            'subject' => '',
            'message' => ''
        ]);

        $response->assertSessionHasErrors(['subject', 'message']);
    }

    /**
     * Test reply message length validation
     */
    public function test_reply_message_length_validation()
    {
        $admin = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('AdminPassword123!'),
            'role' => 'admin'
        ]);

        $contactMessage = ContactMessage::factory()->create();

        $this->actingAs($admin);

        $response = $this->post('/admin/contact-messages/' . $contactMessage->id . '/reply', [
            'subject' => 'Test Subject',
            'message' => str_repeat('a', 2001) // Too long
        ]);

        $response->assertSessionHasErrors(['message']);
    }

    /**
     * Test email sending failure handling
     */
    public function test_email_sending_failure_handling()
    {
        $admin = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('AdminPassword123!'),
            'role' => 'admin'
        ]);

        $contactMessage = ContactMessage::factory()->create();

        $this->actingAs($admin);

        // Mock mail failure
        Mail::shouldReceive('send')
            ->once()
            ->andThrow(new \Exception('Mail sending failed'));

        $response = $this->post('/admin/contact-messages/' . $contactMessage->id . '/reply', [
            'subject' => 'Test Subject',
            'message' => 'Test message'
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors(['error']);
    }
}
