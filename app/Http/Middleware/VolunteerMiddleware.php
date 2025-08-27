<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Inertia\Inertia;

class VolunteerMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!auth()->check()) {
            return redirect()->route('login')->with('error', 'Please log in to access volunteer features.');
        }

        $user = auth()->user();
        
        // Allow access for admins and approved volunteers
        if ($user->isAdmin() || $user->isApprovedVolunteer()) {
            return $next($request);
        }

        // If user has no volunteer application, show access denied page
        if (!$user->hasVolunteerApplication()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'Volunteer access required',
                    'message' => 'You need to become a volunteer first to access this feature.'
                ], 403);
            }

            return Inertia::render('Volunteer/AccessDenied', [
                'userRole' => $user->role,
                'requestedUrl' => $request->url(),
                'userName' => $user->name,
                'volunteerStatus' => 'No Application'
            ])->toResponse($request);
        }

        // If user has pending/rejected application, show access denied page with details
        $volunteerStatus = $user->volunteerApplication->status;
        
        if ($request->expectsJson()) {
            return response()->json([
                'error' => 'Volunteer access required',
                'message' => 'You need to be an approved volunteer to access this feature.'
            ], 403);
        }

        return Inertia::render('Volunteer/AccessDenied', [
            'userRole' => $user->role,
            'requestedUrl' => $request->url(),
            'userName' => $user->name,
            'volunteerStatus' => $volunteerStatus
        ])->toResponse($request);
    }
}
