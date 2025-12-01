<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\User;

class AdminMiddleware
{
    public function handle($request, Closure $next)
    {
        $token = $request->bearerToken();
        if (!$token) {
            return response()->json(['message' => 'Token required'], 401);
        }

        $user = User::where('api_token', hash('sha256', $token))->first();
        if (!$user) {
            return response()->json(['message' => 'Invalid token'], 401);
        }

        if ($user->role !== 'admin') {
            return response()->json(['message' => 'Admin only'], 403);
        }

        // simpan data user ke request biar bisa dipakai di controller
        $request->merge(['auth_user' => $user]);

        return $next($request);
    }
}
