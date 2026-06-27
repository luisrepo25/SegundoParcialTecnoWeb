<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthenticateWithJwtCookie
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->cookie('jwt_token');
        Log::info('AuthenticateWithJwtCookie: Path: ' . $request->path() . ', Token present: ' . ($token ? 'YES' : 'NO'));

        if (! $request->user()) {
            if ($token) {
                try {
                    $user = JWTAuth::setToken($token)->authenticate();
                    Log::info('AuthenticateWithJwtCookie: authenticated user: ' . ($user ? $user->email : 'NONE'));

                    if ($user) {
                        Auth::guard('web')->setUser($user);
                        Auth::guard('api')->setUser($user);
                        Auth::shouldUse('web');
                        $request->setUserResolver(fn () => $user);
                        Log::info('AuthenticateWithJwtCookie: set user on web and api guards successfully.');
                        Log::info('Check web guard: ' . (Auth::guard('web')->check() ? 'YES' : 'NO'));
                        Log::info('Check api guard: ' . (Auth::guard('api')->check() ? 'YES' : 'NO'));
                        Log::info('Auth default guard: ' . auth()->getDefaultDriver());
                        Log::info('Auth check: ' . (auth()->check() ? 'YES' : 'NO'));
                    }
                } catch (\Throwable $e) {
                    Log::error('AuthenticateWithJwtCookie: authentication failed. Error: ' . $e->getMessage());
                    Cookie::queue(Cookie::forget('jwt_token'));
                }
            }
        } else {
            Log::info('AuthenticateWithJwtCookie: user already present in request: ' . $request->user()->email);
        }

        return $next($request);
    }
}