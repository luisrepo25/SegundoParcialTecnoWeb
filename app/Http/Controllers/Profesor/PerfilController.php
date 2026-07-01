<?php

namespace App\Http\Controllers\Profesor;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Tymon\JWTAuth\Facades\JWTAuth;

class PerfilController extends Controller
{
    public function index()
    {
        $userId  = auth()->id();
        $usuario = DB::table('usuarios')->where('id_usuario', $userId)->first();

        $profesor = DB::table('profesores')->where('id_usuario', $userId)->first();

        return Inertia::render('Profesor/Perfil', [
            'perfil' => [
                'nombre'       => $usuario?->nombre,
                'apellido'     => $usuario?->apellido,
                'email'        => $usuario?->email,
                'dni'          => $usuario?->dni,
                'telefono'     => $usuario?->telefono,
                'direccion'    => $usuario?->direccion,
                'especialidad' => $profesor?->especialidad ?? null,
                'archivo_cv'  => $profesor?->archivo_cv  ?? null,
            ],
        ]);
    }

    public function update(Request $request)
    {
        $userId = auth()->id();

        $request->validate([
            'nombre'    => ['required','string','max:100','regex:/^[\pL\s\'\-]+$/u'],
            'apellido'  => ['required','string','max:100','regex:/^[\pL\s\'\-]+$/u'],
            'email'     => ['required','email:rfc','max:150', Rule::unique('usuarios', 'email')->ignore($userId, 'id_usuario')],
            'telefono'  => ['nullable','string','max:20','regex:/^[0-9+\-\s()]*$/'],
            'direccion' => 'nullable|string|max:255',
        ], [
            'nombre.regex'   => 'El nombre no debe contener números ni símbolos.',
            'apellido.regex' => 'El apellido no debe contener números ni símbolos.',
            'email.email'    => 'Ingrese un email válido (sin espacios ni @ dobles).',
            'email.unique'   => 'Este correo ya está en uso por otra cuenta.',
            'telefono.regex' => 'El teléfono solo debe contener números y símbolos (+, -).',
        ]);

        DB::table('usuarios')->where('id_usuario', $userId)->update([
            'nombre'    => $request->nombre,
            'apellido'  => $request->apellido,
            'email'     => $request->email,
            'telefono'  => $request->telefono,
            'direccion' => $request->direccion,
        ]);

        return back()->with('success', 'Perfil actualizado correctamente.');
    }

    public function cambiarPassword(Request $request)
    {
        $request->validate([
            'password_actual' => 'required|string',
            'password_nuevo'  => 'required|string|min:8|confirmed',
        ], [
            'password_nuevo.confirmed' => 'Las contraseñas nuevas no coinciden.',
            'password_nuevo.min'       => 'La contraseña debe tener al menos 8 caracteres.',
        ]);

        $userId  = auth()->id();
        $usuario = DB::table('usuarios')->where('id_usuario', $userId)->first();

        if (!Hash::check($request->password_actual, $usuario->password_hash)) {
            return back()->withErrors(['password_actual' => 'La contraseña actual no es correcta.']);
        }

        DB::table('usuarios')->where('id_usuario', $userId)->update([
            'password_hash' => Hash::make($request->password_nuevo),
        ]);

        $token = $request->cookie('jwt_token');
        if ($token) {
            try { JWTAuth::setToken($token)->invalidate(); } catch (\Throwable) {}
        }

        return redirect()->route('login')
            ->withCookie(Cookie::forget('jwt_token'))
            ->with('status', '¡Contraseña actualizada! Iniciá sesión con tu nueva contraseña.');
    }
}
