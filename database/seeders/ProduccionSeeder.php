<?php

namespace Database\Seeders;

// ╔══════════════════════════════════════════════════════════════════════════╗
// ║  SEEDER DE PRODUCCIÓN                                                    ║
// ║                                                                          ║
// ║  Crea únicamente el usuario provisional de primer acceso.               ║
// ║  CERO datos de negocio (clientes, préstamos, pagos).                    ║
// ║                                                                          ║
// ║  Usar en el servidor de producción:                                      ║
// ║    php artisan migrate --force                                            ║
// ║    php artisan db:seed --class=ProduccionSeeder --force                  ║
// ║                                                                          ║
// ║  DESPUÉS del primer acceso:                                              ║
// ║    1. Entrá con acceso@controlpresta.com / CambiarEsto2026!              ║
// ║    2. Creá los usuarios reales en /usuarios                              ║
// ║    3. Borrá este usuario provisional desde la misma pantalla             ║
// ╚══════════════════════════════════════════════════════════════════════════╝

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class ProduccionSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'acceso@controlpresta.com'],
            [
                'name'     => 'Acceso Inicial',
                'password' => Hash::make('CambiarEsto2026!'),
            ]
        );

        $this->command->info('✓ Usuario provisional creado: acceso@controlpresta.com / CambiarEsto2026!');
        $this->command->warn('  → Cambiá la contraseña o eliminá este usuario después del primer acceso.');
    }
}
