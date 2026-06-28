<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

class VerifyResetCodeController extends Controller
{
    public function create(): Response|RedirectResponse
    {
        $email = session('reset_email', '');

        return Inertia::render('Auth/VerifyResetCode', [
            'email' => $email,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => 'required|email',
            'code'  => 'required|string|size:6',
        ], [
            'code.size'     => 'El código debe tener exactamente 6 dígitos.',
            'code.required' => 'Ingresá el código recibido por correo.',
        ]);

        $record = DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->first();

        if (!$record || !Hash::check($request->code, $record->token)) {
            return back()->withErrors(['code' => 'El código es incorrecto.']);
        }

        if (now()->diffInMinutes($record->created_at) > 15) {
            DB::table('password_reset_tokens')->where('email', $request->email)->delete();
            return back()->withErrors(['code' => 'El código expiró. Solicitá uno nuevo.'])
                ->with('reset_email', $request->email);
        }

        // Guardar email verificado en sesión para el siguiente paso
        session(['password_reset_verified' => $request->email]);

        return redirect()->route('password.reset');
    }
}
