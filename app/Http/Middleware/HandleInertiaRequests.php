<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function share(Request $request): array
    {
        return [
            ...parent::share($request),

            // 你已有的
            'auth' => [
                'user' => $request->user() ? $request->user()->load('volunteerApplication') : null,
            ],

            // ✅ 新增：把 Laravel 的 session flash 共享给前端
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error'   => fn () => $request->session()->get('error'),
            ],

            // ✅ 新增：CSRF token
            'csrf_token' => fn () => csrf_token(),
        ];
    }
}
