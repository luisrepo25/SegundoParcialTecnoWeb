<?php

namespace App\Http\Controllers\Propietario\CU1Usuarios;

use App\Http\Controllers\Controller;
use App\Models\Estudiante;
use App\Models\PersonalAdministrativo;
use App\Models\Profesor;
use App\Models\Rol;
use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class UsuarioController extends Controller
{
    // Roles que cada rol puede crear
    private function rolesPermitidos(): array
    {
        return match (auth()->user()->role) {
            'admin'      => [1, 2, 3, 4, 5],
            'director'   => [2, 3, 4, 5],
            'secretary'  => [3, 4, 5],
            default      => [],
        };
    }

    public function index(Request $request)
    {
        $query = Usuario::with('rol')
            ->orderBy('id_rol')
            ->orderBy('apellido');

        if ($request->filled('rol')) {
            $query->where('id_rol', $request->rol);
        }

        if ($request->filled('buscar')) {
            $buscar = $request->buscar;
            $query->where(function ($q) use ($buscar) {
                $q->where('nombre', 'ilike', "%$buscar%")
                  ->orWhere('apellido', 'ilike', "%$buscar%")
                  ->orWhere('email', 'ilike', "%$buscar%")
                  ->orWhere('dni', 'ilike', "%$buscar%");
            });
        }

        $usuarios = $query->paginate(15)->withQueryString();

        $roles = Rol::where('activo', true)->get();

        return Inertia::render('Propietario/CU1Usuarios/Index', [
            'usuarios'        => $usuarios,
            'roles'           => $roles,
            'rolesPermitidos' => $this->rolesPermitidos(),
            'filtros'         => $request->only(['rol', 'buscar']),
        ]);
    }

    public function store(Request $request)
    {
        $permitidos = $this->rolesPermitidos();

        $request->validate([
            'nombre'      => 'required|string|max:100',
            'apellido'    => 'required|string|max:100',
            'email'       => 'required|email|unique:usuarios,email',
            'dni'         => 'required|string|max:20|unique:usuarios,dni',
            'password'    => 'required|string|min:6|confirmed',
            'id_rol'      => ['required', 'integer', Rule::in($permitidos)],
            'telefono'    => 'nullable|string|max:20',
            'direccion'   => 'nullable|string',
            // Campos de profesor
            'especialidad'       => 'required_if:id_rol,4|nullable|string|max:100',
            'titulo_maximo'      => 'nullable|string|max:150',
            'fecha_contratacion' => 'required_if:id_rol,4|nullable|date',
            // Campos de personal administrativo (roles 1,2,3)
            'cargo'         => 'nullable|string|max:100',
            'fecha_ingreso' => 'nullable|date',
        ]);

        if (!in_array($request->id_rol, $permitidos)) {
            abort(403, 'No tiene permiso para crear usuarios con ese rol.');
        }

        $usuario = Usuario::create([
            'nombre'        => $request->nombre,
            'apellido'      => $request->apellido,
            'email'         => $request->email,
            'dni'           => $request->dni,
            'password_hash' => Hash::make($request->password),
            'id_rol'        => $request->id_rol,
            'telefono'      => $request->telefono,
            'direccion'     => $request->direccion,
            'activo'        => true,
            'bloqueado'     => false,
        ]);

        $this->crearPerfilRol($usuario, $request);

        return redirect()->back()->with('success', 'Usuario creado correctamente.');
    }

    public function update(Request $request, int $id)
    {
        $usuario = Usuario::findOrFail($id);

        $request->validate([
            'nombre'    => 'required|string|max:100',
            'apellido'  => 'required|string|max:100',
            'email'     => ['required', 'email', Rule::unique('usuarios', 'email')->ignore($id, 'id_usuario')],
            'dni'       => ['required', 'string', 'max:20', Rule::unique('usuarios', 'dni')->ignore($id, 'id_usuario')],
            'telefono'  => 'nullable|string|max:20',
            'direccion' => 'nullable|string',
        ]);

        $usuario->update([
            'nombre'    => $request->nombre,
            'apellido'  => $request->apellido,
            'email'     => $request->email,
            'dni'       => $request->dni,
            'telefono'  => $request->telefono,
            'direccion' => $request->direccion,
        ]);

        return redirect()->back()->with('success', 'Usuario actualizado correctamente.');
    }

    public function toggleActivo(int $id)
    {
        $usuario = Usuario::findOrFail($id);

        if ($usuario->id_usuario === auth()->id()) {
            return redirect()->back()->with('error', 'No puede desactivarse a sí mismo.');
        }

        $usuario->update(['activo' => !$usuario->activo]);

        $estado = $usuario->activo ? 'activado' : 'desactivado';
        return redirect()->back()->with('success', "Usuario $estado correctamente.");
    }

    public function cambiarPassword(Request $request, int $id)
    {
        $request->validate([
            'password' => 'required|string|min:6|confirmed',
        ]);

        $usuario = Usuario::findOrFail($id);
        $usuario->update(['password_hash' => Hash::make($request->password)]);

        return redirect()->back()->with('success', 'Contraseña actualizada correctamente.');
    }

    private function crearPerfilRol(Usuario $usuario, Request $request): void
    {
        $idRol = $usuario->id_rol;

        if (in_array($idRol, [1, 2, 3])) {
            $cargo = match ($idRol) {
                1 => 'Propietario',
                2 => 'Director',
                3 => $request->cargo ?? 'Secretaria',
            };

            PersonalAdministrativo::create([
                'id_usuario'      => $usuario->id_usuario,
                'legajo_personal' => $this->generarLegajo('ADM', $usuario->id_usuario),
                'cargo'           => $cargo,
                'fecha_ingreso'   => $request->fecha_ingreso ?? now()->toDateString(),
            ]);
        }

        if ($idRol === 4) {
            Profesor::create([
                'id_usuario'         => $usuario->id_usuario,
                'legajo_profesor'    => $this->generarLegajo('PROF', $usuario->id_usuario),
                'especialidad'       => $request->especialidad,
                'titulo_maximo'      => $request->titulo_maximo,
                'fecha_contratacion' => $request->fecha_contratacion ?? now()->toDateString(),
            ]);
        }

        if ($idRol === 5) {
            Estudiante::create([
                'id_usuario'               => $usuario->id_usuario,
                'legajo'                   => $this->generarLegajo('EST', $usuario->id_usuario),
                'fecha_inscripcion_inicial' => now()->toDateString(),
            ]);
        }
    }

    private function generarLegajo(string $prefijo, int $id): string
    {
        return $prefijo . '-' . str_pad($id, 5, '0', STR_PAD_LEFT);
    }
}
