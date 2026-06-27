<?php

namespace Database\Seeders;

use App\Models\Usuario;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DirectorSeeder extends Seeder
{
    public function run(): void
    {
        $email = 'hebertsb08@gmail.com';

        $usuario = Usuario::where('email', $email)->first();

        if ($usuario) {
            $usuario->update([
                'nombre'        => 'Hebert',
                'apellido'      => 'Suarez Burgos',
                'password_hash' => Hash::make('director123'),
                'dni'           => '4534546',
                'telefono'      => '75345444',
                'direccion'     => 'Santa Cruz, Bolivia',
                'id_rol'        => 2,
                'activo'        => true,
                'bloqueado'     => false,
            ]);

            $this->command->info('Usuario director actualizado.');
        } else {
            $usuario = Usuario::create([
                'nombre'        => 'Hebert',
                'apellido'      => 'Suarez Burgos',
                'email'         => $email,
                'password_hash' => Hash::make('director123'),
                'dni'           => '4534546',
                'telefono'      => '75345444',
                'direccion'     => 'Santa Cruz, Bolivia',
                'id_rol'        => 2,
                'activo'        => true,
                'bloqueado'     => false,
            ]);

            $this->command->info('Usuario director creado.');
        }

        // personal_administrativo vincula director al instituto
        $existe = DB::table('personal_administrativo')
            ->where('id_usuario', $usuario->id_usuario)
            ->exists();

        if (!$existe) {
            DB::table('personal_administrativo')->insert([
                'id_usuario'      => $usuario->id_usuario,
                'legajo_personal' => 'ADM-DIR-001',
                'cargo'           => 'Director',
                'fecha_ingreso'   => '2026-01-01',
                'oficina'         => 'Dirección General',
                'sueldo_base'     => 2000.00,
                'observaciones'   => null,
            ]);

            $this->command->info('Registro personal_administrativo creado.');
        } else {
            $this->command->warn('personal_administrativo ya existe para este usuario.');
        }
    }
}
