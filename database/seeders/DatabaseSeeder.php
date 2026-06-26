<?php

namespace Database\Seeders;

use App\Models\Cliente;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'dueno@controlpresta.com'],
            ['name' => 'Dueño', 'password' => 'password']
        );

        User::firstOrCreate(
            ['email' => 'esposa@controlpresta.com'],
            ['name' => 'Esposa', 'password' => 'password']
        );

        $this->clienteAlDia();
        $this->clienteAtrasado();
        $this->clienteSinPrestamo();
    }

    private function clienteAlDia(): void
    {
        $cliente = Cliente::firstOrCreate(
            ['cedula' => '1-1111-1111'],
            [
                'nombre' => 'María',
                'apellidos' => 'Jiménez Vargas',
                'telefono' => '8888-1111',
                'direccion' => 'San José, Costa Rica',
                'trabajo' => 'Soda La Esquina',
                'estado' => 'al-dia',
                'activo' => true,
            ]
        );

        $cliente->prestamos()->firstOrCreate(
            ['estado' => 'activo'],
            [
                'monto' => 400000,
                'saldo' => 200000,
                'frecuencia' => 'mensual',
                'interes_pagados' => 80000,
                'inicio' => now()->subMonths(2),
                'proximo' => now()->addDays(10),
                'vencido' => false,
            ]
        );
    }

    private function clienteAtrasado(): void
    {
        $cliente = Cliente::firstOrCreate(
            ['cedula' => '2-2222-2222'],
            [
                'nombre' => 'Carlos',
                'apellidos' => 'Rojas Mora',
                'telefono' => '8888-2222',
                'direccion' => 'Heredia, Costa Rica',
                'trabajo' => 'Taller mecánico',
                'estado' => 'atrasado',
                'activo' => true,
            ]
        );

        $prestamo = $cliente->prestamos()->firstOrCreate(
            ['estado' => 'activo'],
            [
                'monto' => 100000,
                'saldo' => 80000,
                'frecuencia' => 'quincenal',
                'dias_atraso' => 5,
                'inicio' => now()->subMonth(),
                'proximo' => now()->subDays(5),
                'vencido' => true,
            ]
        );

        $prestamo->interesesAtrasados()->firstOrCreate(
            ['fecha' => now()->subDays(20)->toDateString()],
            ['monto' => 15000, 'pagado' => false]
        );

        $prestamo->interesesAtrasados()->firstOrCreate(
            ['fecha' => now()->subDays(5)->toDateString()],
            ['monto' => 15000, 'pagado' => false]
        );
    }

    private function clienteSinPrestamo(): void
    {
        Cliente::firstOrCreate(
            ['cedula' => '3-3333-3333'],
            [
                'nombre' => 'Ana',
                'apellidos' => 'Castro Solano',
                'telefono' => '8888-3333',
                'estado' => 'al-dia',
                'activo' => true,
            ]
        );
    }
}
