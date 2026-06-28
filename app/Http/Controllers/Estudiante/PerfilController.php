<?php

namespace App\Http\Controllers\Estudiante;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Tymon\JWTAuth\Facades\JWTAuth;

class PerfilController extends Controller
{
    // ── Ver perfil ────────────────────────────────────────────────────────────
    public function index()
    {
        $userId = auth()->id();

        $usuario = DB::table('usuarios')->where('id_usuario', $userId)->first();
        $est     = DB::table('estudiantes')->where('id_usuario', $userId)->first();
        $carrera = $est?->id_carrera_actual
            ? DB::table('carreras')->where('id_carrera', $est->id_carrera_actual)->first()
            : null;

        return Inertia::render('Estudiante/Perfil', [
            'perfil' => [
                'nombre'                   => $usuario?->nombre,
                'apellido'                 => $usuario?->apellido,
                'email'                    => $usuario?->email,
                'dni'                      => $usuario?->dni,
                'telefono'                 => $usuario?->telefono,
                'legajo'                   => $est?->legajo,
                'fecha_inscripcion_inicial' => $est?->fecha_inscripcion_inicial,
                'tutor_nombre'             => $est?->tutor_nombre,
                'tutor_telefono'           => $est?->tutor_telefono,
                'observaciones'            => $est?->observaciones,
                'carrera_nombre'           => $carrera?->nombre,
                'carrera_tipo'             => $carrera?->tipo,
            ],
        ]);
    }

    // ── Actualizar datos personales ───────────────────────────────────────────
    public function update(Request $request)
    {
        $request->validate([
            'nombre'         => 'required|string|max:100',
            'apellido'       => 'required|string|max:100',
            'telefono'       => 'nullable|string|max:20',
            'tutor_nombre'   => 'nullable|string|max:100',
            'tutor_telefono' => 'nullable|string|max:20',
            'observaciones'  => 'nullable|string|max:500',
        ]);

        $userId = auth()->id();

        DB::table('usuarios')->where('id_usuario', $userId)->update([
            'nombre'   => $request->nombre,
            'apellido' => $request->apellido,
            'telefono' => $request->telefono,
        ]);

        $est = DB::table('estudiantes')->where('id_usuario', $userId)->first();
        if ($est) {
            DB::table('estudiantes')->where('id_estudiante', $est->id_estudiante)->update([
                'tutor_nombre'   => $request->tutor_nombre,
                'tutor_telefono' => $request->tutor_telefono,
                'observaciones'  => $request->observaciones,
            ]);
        }

        return back()->with('success', 'Perfil actualizado correctamente.');
    }

    // ── Cambiar contraseña ────────────────────────────────────────────────────
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

        // Invalidar el token JWT y limpiar el cookie (igual que el logout normal del sistema)
        $token = $request->cookie('jwt_token');
        if ($token) {
            try {
                JWTAuth::setToken($token)->invalidate();
            } catch (\Throwable) {}
        }

        return redirect()->route('login')
            ->withCookie(Cookie::forget('jwt_token'))
            ->with('status', '¡Contraseña actualizada! Inicia sesión con tu nueva contraseña.');
    }
}
