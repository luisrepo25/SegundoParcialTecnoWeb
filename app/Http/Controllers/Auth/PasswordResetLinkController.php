<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\CodigoRecuperacion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;
use Inertia\Response;

class PasswordResetLinkController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Auth/ForgotPassword', [
            'status' => session('status'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate(['email' => 'required|email']);

        $usuario = DB::table('usuarios')->where('email', $request->email)->first();

        if ($usuario) {
            // Throttle manual: 60 s entre códigos
            $existing = DB::table('password_reset_tokens')
                ->where('email', $request->email)->first();

            if ($existing && now()->diffInSeconds($existing->created_at) < 60) {
                return back()->withErrors([
                    'email' => 'Esperá un minuto antes de solicitar otro código.',
                ]);
            }

            $codigo = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

            DB::table('password_reset_tokens')->updateOrInsert(
                ['email' => $request->email],
                ['token' => Hash::make($codigo), 'created_at' => now()]
            );

            Mail::to($request->email)->send(
                new CodigoRecuperacion($codigo, $usuario->nombre)
            );
        }

        // Siempre redirigir igual para no revelar si el correo existe
        return redirect()->route('password.verify.form')
            ->with('reset_email', $request->email);
    }
}
