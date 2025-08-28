<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\PointsService;
use App\Mail\WelcomeEmail;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response;


class RegisteredUserController extends Controller
{
    protected $pointsService;

    public function __construct(PointsService $pointsService)
    {
        $this->pointsService = $pointsService;
    }

    /**
     * Display the registration view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:' . User::class],
            'phone' => ['nullable', 'string', 'max:20'], 
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone, 
            'password' => Hash::make($request->password),
            'role' => 'user', 
        ]);

        // Award registration points
        $this->pointsService->awardRegistrationPoints($user);

        // Send welcome notification
        \App\Services\NotificationService::welcomeNewUser($user);

        // Send welcome email
        try {
            Mail::to($user->email)->send(new WelcomeEmail($user));
        } catch (\Exception $e) {
            // Log the error but don't fail the registration
            \Log::error('Failed to send welcome email: ' . $e->getMessage());
        }

        // Log user registration
        \App\Models\SystemLog::log(
            'user_registered',
            "New user registered: {$user->email}",
            ['user_id' => $user->id, 'email' => $user->email, 'ip' => $request->ip()]
        );

        // Don't auto-login, let user login manually
        return redirect('/login')->with('success', 'Registration successful! Please log in with your new account.');

    }
}
