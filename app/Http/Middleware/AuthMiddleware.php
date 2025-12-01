<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\User;

class AuthMiddleware
{
    public function handle($request, Closure $next)
    {
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json(['message' => 'Token not provided'], 401);
        }

        $user = User::where('api_token', hash('sha256', $token))->first();

        if (!$user) {
            return response()->json(['message' => 'Invalid token'], 401);
        }

        // Simpan user di request
        //$request->merge(['auth_user' => $user]);

        $request->setUserResolver(function () use ($user) {
            return $user;
        });
        
        // Jika route hanya boleh diakses user biasa, tapi role admin juga boleh → langsung lanjut
        return $next($request);
    }
}
