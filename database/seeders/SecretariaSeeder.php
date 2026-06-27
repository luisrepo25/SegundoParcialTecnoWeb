<?php

namespace Database\Seeders;

use App\Models\Usuario;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SecretariaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Verificamos si ya existe para no duplicarlo si corres el seeder varias veces
        $email = 'secretaria@institutosanpablo.edu.bo';

        if (!Usuario::where('email', $email)->exists()) {
            Usuario::create([
                'nombre' => 'Ana',
                'apellido' => 'Gomez (Secretaria)',
                'email' => $email,
                'dni' => '12345678',
                'telefono' => '77712345',
                'direccion' => 'Av. Principal #123',
                // Usamos Bcrypt para que Laravel pueda desencriptarla correctamente
                'password_hash' => Hash::make('password123'),
                // id_rol 3 corresponde a 'secretary' en tu modelo
                'id_rol' => 3, 
                'activo' => true,
                'bloqueado' => false,
            ]);

            $this->command->info('Usuario de secretaría creado exitosamente.');
        } else {
            $this->command->warn('El usuario de secretaría ya existe.');
        }
    }
}
