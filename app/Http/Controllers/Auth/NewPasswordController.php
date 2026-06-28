<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response;

class NewPasswordController extends Controller
{
    public function create(Request $request): Response|RedirectResponse
    {
        $email = session('password_reset_verified', '');

        if (!$email) {
            return redirect()->route('password.request');
        }

        return Inertia::render('Auth/ResetPassword', [
            'email' => $email,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $email = session('password_reset_verified', '');

        if (!$email) {
            return redirect()->route('password.request');
        }

        $request->validate([
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ], [
            'password.confirmed' => 'Las contraseñas no coinciden.',
            'password.min'       => 'La contraseña debe tener al menos 8 caracteres.',
        ]);

        DB::table('usuarios')
            ->where('email', $email)
            ->update(['password_hash' => Hash::make($request->password)]);

        DB::table('password_reset_tokens')->where('email', $email)->delete();
        session()->forget('password_reset_verified');

        return redirect()->route('login')
            ->with('status', 'Contraseña actualizada. Ya podés iniciar sesión.');
    }
}
