<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Usuario;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Tymon\JWTAuth\Facades\JWTAuth;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:'.Usuario::class,
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = Usuario::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'student',
        ]);

        event(new Registered($user));

        return $this->withJwtCookie(
            redirect(route('dashboard', absolute: false)),
            JWTAuth::fromUser($user),
        );
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
