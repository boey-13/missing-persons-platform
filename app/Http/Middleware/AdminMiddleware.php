<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Inertia\Inertia;

class AdminMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->check() && auth()->user()->role === 'admin') {
            
            return $next($request);
        }

        // Log unauthorized admin access attempt
        if (auth()->check()) {
            \App\Models\SystemLog::log(
                'access_denied',
                'Non-admin user attempted to access admin area',
                [
                    'user_id' => auth()->id(),
                    'user_email' => auth()->user()->email,
                    'user_role' => auth()->user()->role,
                    'requested_url' => $request->url(),
                    'ip_address' => $request->ip()
                ]
            );
        } else {
            \App\Models\SystemLog::log(
                'access_denied',
                'Unauthenticated user attempted to access admin area',
                [
                    'requested_url' => $request->url(),
                    'ip_address' => $request->ip()
                ]
            );
        }

        // Instead of abort(403), show a friendly error page
        if ($request->expectsJson()) {
            return response()->json([
                'error' => 'Admin access required',
                'message' => 'You do not have permission to access this admin area.'
            ], 403);
        }

        // Return Inertia response as a proper Response object
        return Inertia::render('Admin/AccessDenied', [
            'userRole' => auth()->user() ? auth()->user()->role : 'guest',
            'requestedUrl' => $request->url(),
            'userName' => auth()->user() ? auth()->user()->name : null
        ])->toResponse($request);
    }
}

