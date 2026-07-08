<?php

namespace Database\Seeders;

// ╔══════════════════════════════════════════════════════════════════════════╗
// ║  ⚠️  SEEDER DE PRUEBA — SOLO DESARROLLO  ⚠️                           ║
// ║                                                                          ║
// ║  Borrar toda la base al correr:  php artisan migrate:fresh --seed        ║
// ║                                                                          ║
// ║  NUNCA correr en producción. El usuario de producción tiene datos        ║
// ║  reales del cuaderno que no se pueden recuperar una vez borrados.        ║
// ╚══════════════════════════════════════════════════════════════════════════╝

use App\Models\Cliente;
use App\Services\PrestamoService;
use Illuminate\Database\Seeder;

class ClientesPruebaSeeder extends Seeder
{
    public function run(): void
    {
        $s = app(PrestamoService::class);

        // ── Semanal (5%) ──────────────────────────────────────────────────────
        $this->kendallRojas($s);       // al-dia
        $this->greivinSalas($s);       // atrasado 7 días

        // ── Quincenal (15%) ──────────────────────────────────────────────────
        $this->yorlenyVargas($s);      // al-dia
        $this->marvinJimenez($s);      // atrasado 8 días
        $this->adrianaMora($s);        // al-dia, saldo en la mitad exacta

        // ── Mensual (20%) ────────────────────────────────────────────────────
        $this->luisCampos($s);         // al-dia
        $this->xiniaAlvarez($s);       // atrasada 8 días
        $this->randallChaves($s);      // atrasado profundo — motor genera 1 interés atrasado
        $this->florHerrera($s);        // al-dia, saldo por debajo de la mitad
        $this->oscarNunez($s);         // para saldar (multa congelada + interés atrasado manual)

        // ── Sin préstamo / Inactivo ──────────────────────────────────────────
        $this->damarisSoto();          // sin-prestamo (demo "Nuevo Préstamo")
        $this->gerardoUrena($s);       // inactivo con historial saldado (demo reactivar)
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Semanal (5%)
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Kendall Rojas — Semanal al día.
     * monto=80.000, saldo=80.000, inicio=2026-07-01, proximo=2026-07-15
     * interés = 80.000 × 5% = 4.000
     */
    private function kendallRojas(PrestamoService $s): void
    {
        $c = $this->cliente('Kendall', 'Rojas');
        $s->crearDesdeMigracion($c, [
            'monto'      => 80000,
            'frecuencia' => 'semanal',
            'inicio'     => '2026-07-01',
            'proximo'    => '2026-07-15',
        ]);
    }

    /**
     * Greivin Salas — Semanal atrasado.
     * monto=100.000, saldo=100.000, proximo=2026-07-01, atraso_desde=2026-07-01
     * dias_atraso=7, multa=7×3.000=21.000, interés=5.000
     * Motor: cursor=2026-07-08 < hoy=2026-07-08 → FALSE (estricto) → 0 intereses atrasados
     */
    private function greivinSalas(PrestamoService $s): void
    {
        $c = $this->cliente('Greivin', 'Salas');
        $p = $s->crearDesdeMigracion($c, [
            'monto'        => 100000,
            'frecuencia'   => 'semanal',
            'inicio'       => '2026-05-14',
            'proximo'      => '2026-07-01',
            'atraso_desde' => '2026-07-01',
        ]);
        $p->load('interesesAtrasados');
        $s->sincronizarAtraso($p);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Quincenal (15%)
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Yorleny Vargas — Quincenal al día.
     * monto=150.000, saldo=150.000, inicio=2026-06-30, proximo=2026-07-15
     * interés = 150.000 × 15% = 22.500
     */
    private function yorlenyVargas(PrestamoService $s): void
    {
        $c = $this->cliente('Yorleny', 'Vargas');
        $s->crearDesdeMigracion($c, [
            'monto'      => 150000,
            'frecuencia' => 'quincenal',
            'inicio'     => '2026-06-30',
            'proximo'    => '2026-07-15',
        ]);
    }

    /**
     * Marvin Jiménez — Quincenal atrasado.
     * monto=120.000, saldo=120.000, proximo=2026-06-30, atraso_desde=2026-06-30
     * dias_atraso=8, multa=8×3.000=24.000, interés=18.000
     * Motor: cursor=2026-07-15 < hoy=2026-07-08 → FALSE → 0 intereses atrasados
     */
    private function marvinJimenez(PrestamoService $s): void
    {
        $c = $this->cliente('Marvin', 'Jiménez');
        $p = $s->crearDesdeMigracion($c, [
            'monto'        => 120000,
            'frecuencia'   => 'quincenal',
            'inicio'       => '2026-05-30',
            'proximo'      => '2026-06-30',
            'atraso_desde' => '2026-06-30',
        ]);
        $p->load('interesesAtrasados');
        $s->sincronizarAtraso($p);
    }

    /**
     * Adriana Mora — Quincenal al día, saldo exactamente en la mitad.
     * monto=200.000, saldo=100.000, proximo=2026-07-15
     * saldo(100.000) > monto/2(100.000) es FALSO → base=100.000
     * interés = 100.000 × 15% = 15.000
     */
    private function adrianaMora(PrestamoService $s): void
    {
        $c = $this->cliente('Adriana', 'Mora');
        $s->crearDesdeMigracion($c, [
            'monto'          => 200000,
            'saldo'          => 100000,
            'frecuencia'     => 'quincenal',
            'inicio'         => '2026-04-15',
            'proximo'        => '2026-07-15',
            'interes_pagados'=> 90000,   // 3 períodos a 30.000 (base=200k)
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Mensual (20%)
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Luis Campos — Mensual al día.
     * monto=200.000, saldo=200.000, inicio=2026-07-08, proximo=2026-08-08
     * interés = 200.000 × 20% = 40.000
     */
    private function luisCampos(PrestamoService $s): void
    {
        $c = $this->cliente('Luis', 'Campos');
        $s->crearDesdeMigracion($c, [
            'monto'      => 200000,
            'frecuencia' => 'mensual',
            'inicio'     => '2026-07-08',
            'proximo'    => '2026-08-08',
        ]);
    }

    /**
     * Xinia Álvarez — Mensual atrasada.
     * monto=200.000, saldo=200.000, proximo=2026-06-30, atraso_desde=2026-06-30
     * dias_atraso=8, multa=8×5.000=40.000, interés=40.000
     * Motor: cursor=2026-07-30 < hoy=2026-07-08 → FALSE → 0 intereses atrasados
     */
    private function xiniaAlvarez(PrestamoService $s): void
    {
        $c = $this->cliente('Xinia', 'Álvarez');
        $p = $s->crearDesdeMigracion($c, [
            'monto'        => 200000,
            'frecuencia'   => 'mensual',
            'inicio'       => '2026-05-30',
            'proximo'      => '2026-06-30',
            'atraso_desde' => '2026-06-30',
        ]);
        $p->load('interesesAtrasados');
        $s->sincronizarAtraso($p);
    }

    /**
     * Randall Chaves — Mensual atrasado profundo.
     * monto=80.000, saldo=80.000, proximo=2026-06-05, atraso_desde=2026-06-05
     * dias_atraso=33, multa=33×2.000=66.000
     * Motor genera: 1 interés atrasado fecha=2026-07-05, monto=16.000
     *   (cursor=2026-07-05 < 2026-07-08 → crea; siguiente=2026-08-05 → para)
     * interes_periodo = 80.000×20% = 16.000
     */
    private function randallChaves(PrestamoService $s): void
    {
        $c = $this->cliente('Randall', 'Chaves');
        $p = $s->crearDesdeMigracion($c, [
            'monto'        => 80000,
            'frecuencia'   => 'mensual',
            'inicio'       => '2026-04-05',
            'proximo'      => '2026-06-05',
            'atraso_desde' => '2026-06-05',
        ]);
        $p->load('interesesAtrasados');
        $s->sincronizarAtraso($p);
    }

    /**
     * Flor Herrera — Mensual al día, saldo por debajo de la mitad.
     * monto=200.000, saldo=90.000, proximo=2026-08-08
     * saldo(90.000) > monto/2(100.000) es FALSO → base=100.000
     * interés = 100.000 × 20% = 20.000
     */
    private function florHerrera(PrestamoService $s): void
    {
        $c = $this->cliente('Flor', 'Herrera');
        $s->crearDesdeMigracion($c, [
            'monto'          => 200000,
            'saldo'          => 90000,
            'frecuencia'     => 'mensual',
            'inicio'         => '2026-03-08',
            'proximo'        => '2026-08-08',
            'interes_pagados'=> 80000,   // 4 períodos a 20.000 (base=100k)
        ]);
    }

    /**
     * Óscar Núñez — Mensual para saldar, migrado con atraso manual.
     * monto=100.000, saldo=60.000, proximo=2026-06-25, atraso_desde=2026-06-25
     * multa_acumulada=30.000 (congelada: no crece con días)
     *
     * NOTA sobre el interés atrasado manual:
     *   crearDesdeMigracion crea el registro con fecha=atraso_desde=2026-06-25,
     *   pero el cleanup de sincronizarAtraso borra registros con fecha=proximo=2026-06-25.
     *   Solución: crear el registro manualmente con fecha=2026-05-25 (período anterior),
     *   que el cleanup no toca.
     *
     * interés_periodo = saldo(60.000) > monto/2(50.000) → base=100.000 → 20.000
     * totalASaldar = 60.000 + 20.000(interes) + 20.000(atr) + 30.000(multa) = 130.000
     */
    private function oscarNunez(PrestamoService $s): void
    {
        $c = $this->cliente('Óscar', 'Núñez');
        $p = $s->crearDesdeMigracion($c, [
            'monto'           => 100000,
            'saldo'           => 60000,
            'frecuencia'      => 'mensual',
            'inicio'          => '2026-04-10',
            'proximo'         => '2026-06-25',
            'atraso_desde'    => '2026-06-25',
            'multa_acumulada' => 30000,
            'interes_pagados' => 20000,
        ]);
        // Interés atrasado manual del cuaderno: período de mayo (antes del proximo actual).
        // fecha=2026-05-25 para que el cleanup (que borra fecha=proximo=2026-06-25) no lo elimine.
        $p->interesesAtrasados()->create([
            'fecha'  => '2026-05-25',
            'monto'  => 20000,
            'pagado' => false,
        ]);
        $p->load('interesesAtrasados');
        $s->sincronizarAtraso($p);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Sin préstamo / Inactivo
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Damaris Soto — Sin préstamo activo.
     * Para demo del botón "Nuevo Préstamo" en la ficha.
     */
    private function damarisSoto(): void
    {
        Cliente::create([
            'nombre'    => 'Damaris',
            'apellidos' => 'Soto',
            'estado'    => 'sin-prestamo',
            'activo'    => true,
        ]);
    }

    /**
     * Gerardo Ureña — Inactivo con historial de préstamo saldado.
     * Para demo del flujo de reactivación (aparece en la lista de inactivos).
     */
    private function gerardoUrena(PrestamoService $s): void
    {
        $c = $this->cliente('Gerardo', 'Ureña');
        $p = $s->crearDesdeMigracion($c, [
            'monto'          => 150000,
            'saldo'          => 0,
            'frecuencia'     => 'mensual',
            'inicio'         => '2025-06-15',
            'proximo'        => '2025-07-15',
            'interes_pagados'=> 150000,
        ]);
        $p->update(['estado' => 'saldado']);
        $c->update(['activo' => false, 'estado' => 'sin-prestamo']);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Helper
    // ─────────────────────────────────────────────────────────────────────────

    private function cliente(string $nombre, string $apellidos): Cliente
    {
        return Cliente::create([
            'nombre'    => $nombre,
            'apellidos' => $apellidos,
            'activo'    => true,
            'estado'    => 'al-dia',   // crearDesdeMigracion lo actualiza
        ]);
    }
}
