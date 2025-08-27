<?php

namespace App\Http\Requests\Auth;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Attempt to authenticate the request's credentials.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        // Check if user exists and is locked
        $user = \App\Models\User::where('email', $this->email)->first();
        
        if ($user && $user->isAccountLocked()) {
            throw ValidationException::withMessages([
                'account_locked' => 'Your account has been locked for 5 minutes due to too many failed login attempts. Please try again later or reset your password.',
            ]);
        }

        if (! Auth::attempt($this->only('email', 'password'), $this->boolean('remember'))) {
            // Increment failed login attempts if user exists
            if ($user) {
                $user->incrementFailedLoginAttempts();
                
                // Check if account should be locked
                if ($user->isAccountLocked()) {
                    throw ValidationException::withMessages([
                        'account_locked' => 'Your account has been locked for 5 minutes due to too many failed login attempts. Please try again later or reset your password.',
                    ]);
                }
            }

            RateLimiter::hit($this->throttleKey(), 300); // 5 minutes = 300 seconds

            throw ValidationException::withMessages([
                'email' => trans('auth.failed'),
            ]);
        }

        // Reset failed login attempts on successful login
        if ($user) {
            $user->resetFailedLoginAttempts();
        }

        RateLimiter::clear($this->throttleKey());
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 3)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'account_locked' => 'Your account has been locked for 5 minutes due to too many failed login attempts. Please try again later or reset your password.',
        ]);
    }

    /**
     * Get the rate limiting throttle key for the request.
     */
    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('email')).'|'.$this->ip());
    }
}
