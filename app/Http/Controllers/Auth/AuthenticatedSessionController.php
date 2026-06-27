<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Login', [
            'canResetPassword' => Route::has('password.request'),
            'status' => session('status'),
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $token = $request->authenticate();
        $user = JWTAuth::setToken($token)->authenticate();

        return $this->withJwtCookie(
            redirect($this->redirectToRoleDashboard($user?->role)),
            $token,
        );
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $token = $request->cookie('jwt_token');

        if ($token) {
            try {
                JWTAuth::setToken($token)->invalidate();
            } catch (\Throwable) {
                // If the token is already invalid, the cookie is still cleared below.
            }
        }

        return redirect('/')->withCookie(Cookie::forget('jwt_token'));
    }

    private function redirectToRoleDashboard(?string $role): string
    {
        return match ($role) {
            'admin' => route('dashboard.admin', absolute: false),
            'director' => route('dashboard.director', absolute: false),
            'secretary' => route('dashboard.secretary', absolute: false),
            'teacher' => route('dashboard.teacher', absolute: false),
            default => route('dashboard.student', absolute: false),
        };
    }

    private function withJwtCookie(RedirectResponse $response, string $token): RedirectResponse
    {
        return $response->withCookie(
            cookie(
                'jwt_token',
                $token,
                60 * 24,
                '/',
                null,
                app()->isProduction(),
                true,
                false,
                'Strict',
            )
        );
    }
}
