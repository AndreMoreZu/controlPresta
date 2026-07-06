<?php

namespace Database\Seeders;

use App\Models\Cliente;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        // ── Usuarios ──────────────────────────────────────────────────────────
        User::firstOrCreate(
            ['email' => 'dueno@controlpresta.com'],
            ['name' => 'Dueño', 'password' => 'password']
        );

        User::firstOrCreate(
            ['email' => 'esposa@controlpresta.com'],
            ['name' => 'Esposa', 'password' => 'password']
        );

        // ── Clientes de prueba (cubren todos los casos del módulo de Pagos) ──
        $this->alDiaHoy();
        $this->atrasadoPocosDias();
        $this->atrasadoVariosPeriodos();
        $this->casiSaldado();
        $this->sinPrestamo();
    }

    /**
     * Caso 1: pago normal el mismo día de cobro.
     *
     * Préstamo mensual ₡200.000, saldo ₡200.000.
     * proximo = hoy → el sistema muestra el interés del período y el botón "Registrar pago" activo.
     * Interés esperado: ₡200.000 × 20% = ₡40.000.
     */
    private function alDiaHoy(): void
    {
        $cliente = Cliente::create([
            'nombre'    => 'Ana',
            'apellidos' => 'Jiménez Torres',
            'cedula'    => '1-1111-1111',
            'telefono'  => '8888-1111',
            'direccion' => 'San José, Curridabat',
            'trabajo'   => 'Soda La Esquina',
            'estado'    => 'al-dia',
            'activo'    => true,
        ]);

        $cliente->prestamos()->create([
            'monto'           => 200000,
            'saldo'           => 200000,
            'frecuencia'      => 'mensual',
            'interes_pagados' => 0,
            'multa_acumulada' => 0,
            'dias_atraso'     => 0,
            'atraso_desde'    => null,
            'inicio'          => now()->subMonthNoOverflow(),
            'proximo'         => now()->startOfDay(),
            'vencido'         => false,
            'estado'          => 'activo',
        ]);
    }

    /**
     * Caso 2: cliente atrasado con pocos días — multa pequeña + 1 interés atrasado.
     *
     * Préstamo quincenal ₡100.000, saldo ₡100.000.
     * atraso_desde = hace 5 días.
     * Multa dinámica (multa_acumulada=0): 5 días × ₡3.000/día = ₡15.000.
     * Interés atrasado: ₡100.000 × 15% = ₡15.000 (1 período sin pagar).
     * Interés del período actual: ₡100.000 × 15% = ₡15.000.
     * Total mínimo esperado: ₡15.000 + ₡15.000 + ₡15.000 = ₡45.000.
     */
    private function atrasadoPocosDias(): void
    {
        $atrasoDesde = now()->subDays(5)->startOfDay();

        $cliente = Cliente::create([
            'nombre'    => 'Carlos',
            'apellidos' => 'Rojas Mora',
            'cedula'    => '2-2222-2222',
            'telefono'  => '8888-2222',
            'direccion' => 'Heredia, San Francisco',
            'trabajo'   => 'Taller mecánico',
            'estado'    => 'atrasado',
            'activo'    => true,
        ]);

        $prestamo = $cliente->prestamos()->create([
            'monto'           => 100000,
            'saldo'           => 100000,
            'frecuencia'      => 'quincenal',
            'interes_pagados' => 0,
            'multa_acumulada' => 0,       // dinámico: 5 × ₡3.000 = ₡15.000
            'dias_atraso'     => 5,
            'atraso_desde'    => $atrasoDesde,
            'inicio'          => now()->subDays(20)->startOfDay(),
            'proximo'         => $atrasoDesde,
            'vencido'         => true,
            'estado'          => 'activo',
        ]);

        // Interés del período que no se pagó
        $prestamo->interesesAtrasados()->create([
            'fecha'  => $atrasoDesde,
            'monto'  => 15000,
            'pagado' => false,
        ]);
    }

    /**
     * Caso 3: cliente muy atrasado — varios períodos sin pagar.
     *
     * Préstamo mensual ₡300.000, saldo ₡300.000.
     * atraso_desde = hace 40 días (más de 1 período mensual).
     * Multa dinámica: 40 días × ₡5.000/día = ₡200.000.
     * Intereses atrasados: 2 períodos × ₡60.000 = ₡120.000.
     * Interés del período actual: ₡300.000 × 20% = ₡60.000.
     * Total mínimo esperado: ₡60.000 + ₡200.000 + ₡120.000 = ₡380.000.
     */
    private function atrasadoVariosPeriodos(): void
    {
        $atrasoDesde = now()->subDays(40)->startOfDay();

        $cliente = Cliente::create([
            'nombre'    => 'Luis',
            'apellidos' => 'Herrera Campos',
            'cedula'    => '3-3333-3333',
            'telefono'  => '8888-3333',
            'direccion' => 'Alajuela, La Trinidad',
            'trabajo'   => 'Vendedor ambulante',
            'estado'    => 'atrasado',
            'activo'    => true,
        ]);

        $prestamo = $cliente->prestamos()->create([
            'monto'           => 300000,
            'saldo'           => 300000,
            'frecuencia'      => 'mensual',
            'interes_pagados' => 0,
            'multa_acumulada' => 0,       // dinámico: 40 × ₡5.000 = ₡200.000
            'dias_atraso'     => 40,
            'atraso_desde'    => $atrasoDesde,
            'inicio'          => now()->subDays(45)->startOfDay(),
            'proximo'         => $atrasoDesde,
            'vencido'         => true,
            'estado'          => 'activo',
        ]);

        // Período 1: la cuota original que no pagó
        $prestamo->interesesAtrasados()->create([
            'fecha'  => $atrasoDesde,
            'monto'  => 60000,
            'pagado' => false,
        ]);

        // Período 2: un segundo período que también venció sin pagar
        $prestamo->interesesAtrasados()->create([
            'fecha'  => now()->subDays(10)->startOfDay(),
            'monto'  => 60000,
            'pagado' => false,
        ]);
    }

    /**
     * Caso 4: saldo casi en cero — probar que el abono lleva el saldo a ₡0.
     *
     * Préstamo semanal ₡100.000, saldo ₡20.000 (ya abonó ₡80.000 del capital).
     * proximo = hoy.
     * Interés del período: base = monto/2 = ₡50.000 (porque saldo <= mitad) → ₡50.000 × 5% = ₡2.500.
     * Al pagar con abono = ₡20.000 → saldo queda en ₡0 → se habilita "Nuevo préstamo".
     */
    private function casiSaldado(): void
    {
        $cliente = Cliente::create([
            'nombre'    => 'María',
            'apellidos' => 'Solano Díaz',
            'cedula'    => '4-4444-4444',
            'telefono'  => '8888-4444',
            'direccion' => 'Cartago, El Guarco',
            'trabajo'   => 'Pulpería Don Chema',
            'estado'    => 'al-dia',
            'activo'    => true,
        ]);

        $cliente->prestamos()->create([
            'monto'           => 100000,
            'saldo'           => 20000,
            'frecuencia'      => 'semanal',
            'interes_pagados' => 40000,   // pagos previos de interés (aprox. 10 semanas)
            'multa_acumulada' => 0,
            'dias_atraso'     => 0,
            'atraso_desde'    => null,
            'inicio'          => now()->subWeeks(10)->startOfDay(),
            'proximo'         => now()->startOfDay(),
            'vencido'         => false,
            'estado'          => 'activo',
        ]);
    }

    /**
     * Caso 5: cliente sin préstamo activo — ficha vacía.
     *
     * Para verificar que la ficha carga correctamente sin préstamo
     * y que el botón "Registrar pago" queda deshabilitado.
     */
    private function sinPrestamo(): void
    {
        Cliente::create([
            'nombre'    => 'Roberto',
            'apellidos' => 'Castro Fonseca',
            'cedula'    => '5-5555-5555',
            'telefono'  => '8888-5555',
            'direccion' => 'Limón, Puerto Viejo',
            'trabajo'   => null,
            'estado'    => 'al-dia',
            'activo'    => true,
        ]);
    }
}
