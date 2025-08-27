<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Mail\WelcomeEmail;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

class TestWelcomeEmail extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:welcome-email {email}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test the welcome email functionality';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email');
        
        // Create a test user
        $user = new User([
            'name' => 'Test User',
            'email' => $email,
        ]);

        try {
            Mail::to($email)->send(new WelcomeEmail($user));
            $this->info('Welcome email sent successfully to ' . $email);
        } catch (\Exception $e) {
            $this->error('Failed to send welcome email: ' . $e->getMessage());
        }
    }
}
