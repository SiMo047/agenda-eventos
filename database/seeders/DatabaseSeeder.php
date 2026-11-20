<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Datos de prueba: un usuario de cada rol (contraseña 12345678) y contenido de ejemplo.
     */
    public function run(): void
    {
        foreach ([
            ['Admin', 'admin@example.com', 'Administrador'],
            ['Creador', 'creador@example.com', 'CreadorEventos'],
            ['Asistente', 'asistente@example.com', 'Asistente'],
        ] as [$nombre, $email, $rol]) {
            User::create([
                'name' => $nombre,
                'apellidos' => 'Demo',
                'email' => $email,
                'email_verified_at' => now(),
                'password' => Hash::make('12345678'),
                'rol' => $rol,
            ]);
        }

        $this->call([
            UserSeeder::class,
            CategoriaSeeder::class,
            EmpresaSeeder::class,
            EventoSeeder::class,
            ExperienciaSeeder::class,
        ]);
    }
}
