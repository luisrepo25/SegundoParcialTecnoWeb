<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthenticateWithJwtCookie
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->cookie('jwt_token');

        if (! $request->user() && $token) {
            try {
                $user = JWTAuth::setToken($token)->authenticate();

                if ($user) {
                    Auth::guard('web')->setUser($user);
                    Auth::shouldUse('web');
                    $request->setUserResolver(fn () => $user);
                }
            } catch (\Throwable) {
                Cookie::queue(Cookie::forget('jwt_token'));
            }
        }

        return $next($request);
    }
}
