<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminAuthenticate
{
    public function handle($request, Closure $next)
    {
        if (!Auth::guard('admin')->check()) {
            return response()->json(['message' => 'Unauthorized', 'success' => false], 401);
        }

        return $next($request);
    }
}
