<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use App\Models\Usuario;

class ForcePasswordChangeController extends Controller
{
    public function show()
    {
        return Inertia::render('Auth/ForcePasswordChange');
    }

    public function update(Request $request)
    {
        $request->validate([
            'password' => [
                'required',
                'string',
                'min:6',
                'confirmed',
                'regex:/[@$!%*#?&.]/', // Al menos un carácter especial
            ],
        ], [
            'password.regex' => 'La contraseña debe contener al menos un carácter especial (@, $, !, %, *, #, ?, &, .).',
        ]);

        $user = Auth::user();

        if (Hash::check($request->password, $user->password_hash)) {
            return back()->withErrors(['password' => 'La nueva contraseña no puede ser igual a tu número de carnet (DNI).']);
        }

        // El modelo Usuario en este sistema usa id_usuario como primary key
        $usuario = Usuario::where('id_usuario', $user->id_usuario)->first();
        $usuario->password_hash = Hash::make($request->password);
        $usuario->save();

        session()->forget('needs_password_change');

        $destino = match ($user->role) {
            'propietario' => route('dashboard.propietario'),
            'director'    => route('dashboard.director'),
            'secretaria'  => route('secretaria.dashboard'),
            'profesor'    => route('dashboard.profesor'),
            default       => route('dashboard.estudiante'),
        };

        return redirect($destino)->with('status', '¡Contraseña actualizada! Bienvenido al sistema.');
    }
}
