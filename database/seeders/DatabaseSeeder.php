<?php

namespace Database\Seeders;

use App\Models\Usuario;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        Usuario::updateOrCreate(
            ['email' => 'suarezburgoshebert@gmail.com'],
            [
                'nombre'        => 'hebert',
                'apellido'      => 'Suarez Burgos',
                'password_hash' => Hash::make('propietario123'),
                'dni'           => '10000001',
                'id_rol'        => 1,
                'activo'        => true,
                'bloqueado'     => false,
            ]
        );
    }
}
