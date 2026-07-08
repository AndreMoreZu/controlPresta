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

        // ── §5.2 / §5.3: Frecuencias ─────────────────────────────────────────
        $this->caso1_mensualAlDia($s);
        $this->caso2_quincenalAlDia($s);
        $this->caso3_semanalAlDia($s);

        // ── §5.3: Recálculo a la mitad del monto ─────────────────────────────
        $this->caso4_encimaMitad($s);
        $this->caso5_enMitad($s);
        $this->caso6_debajoMitad($s);

        // ── §5.5: Multa por día + tarifera correcta ───────────────────────────
        $this->caso7_atrasadoChico($s);
        $this->caso8_atrasadoMediano($s);
        $this->caso9_atrasadoGrande($s);

        // ── §5.5: Día de cobro NO cuenta ─────────────────────────────────────
        $this->caso10_venceHoy($s);

        // ── §5.6: Motor genera intereses atrasados por período ────────────────
        $this->caso11_dosPeriodosVencidos($s);

        // ── §5.7: Total a saldar ──────────────────────────────────────────────
        $this->caso12_paraSaldar($s);

        // ── §5.8: Estados ─────────────────────────────────────────────────────
        $this->caso13_sinPrestamo();
        $this->caso14_inactivo($s);

        // ── §5.9: Pago parcial / Camino B ─────────────────────────────────────
        $this->caso15_interesPendiente($s);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // §5.2 / §5.3 — Frecuencias básicas
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Caso 1 — Mensual al día.
     * interés = ₡200.000 × 20% = ₡40.000
     */
    private function caso1_mensualAlDia(PrestamoService $s): void
    {
        $c = $this->cliente('Caso1 Mensual', 'AlDia');
        $s->crearDesdeMigracion($c, [
            'monto'     => 200000,
            'frecuencia'=> 'mensual',
            'inicio'    => today()->subMonth(),
            'proximo'   => today()->addMonth(),
        ]);
    }

    /**
     * Caso 2 — Quincenal al día.
     * interés = ₡100.000 × 15% = ₡15.000
     */
    private function caso2_quincenalAlDia(PrestamoService $s): void
    {
        $c = $this->cliente('Caso2 Quincenal', 'AlDia');
        $s->crearDesdeMigracion($c, [
            'monto'     => 100000,
            'frecuencia'=> 'quincenal',
            'inicio'    => today()->subDays(16),
            'proximo'   => today()->addDays(7),
        ]);
    }

    /**
     * Caso 3 — Semanal al día.
     * interés = ₡50.000 × 5% = ₡2.500
     */
    private function caso3_semanalAlDia(PrestamoService $s): void
    {
        $c = $this->cliente('Caso3 Semanal', 'AlDia');
        $s->crearDesdeMigracion($c, [
            'monto'     => 50000,
            'frecuencia'=> 'semanal',
            'inicio'    => today()->subWeek(),
            'proximo'   => today()->addDays(3),
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // §5.3 — Recálculo a la mitad
    // base = monto si saldo > monto/2; base = monto/2 si saldo ≤ monto/2
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Caso 4 — Saldo por encima de la mitad.
     * monto=₡400.000, saldo=₡201.000 → 201.000 > 200.000 → base=₡400.000
     * interés = ₡400.000 × 20% = ₡80.000
     */
    private function caso4_encimaMitad(PrestamoService $s): void
    {
        $c = $this->cliente('Caso4 Encima', 'Mitad');
        $s->crearDesdeMigracion($c, [
            'monto'     => 400000,
            'saldo'     => 201000,
            'frecuencia'=> 'mensual',
            'inicio'    => today()->subMonths(2),
            'proximo'   => today()->addMonth(),
        ]);
    }

    /**
     * Caso 5 — Saldo exactamente en la mitad.
     * monto=₡400.000, saldo=₡200.000 → 200.000 > 200.000 es FALSO → base=₡200.000
     * interés = ₡200.000 × 20% = ₡40.000
     */
    private function caso5_enMitad(PrestamoService $s): void
    {
        $c = $this->cliente('Caso5 En', 'Mitad');
        $s->crearDesdeMigracion($c, [
            'monto'     => 400000,
            'saldo'     => 200000,
            'frecuencia'=> 'mensual',
            'inicio'    => today()->subMonths(3),
            'proximo'   => today()->addMonth(),
        ]);
    }

    /**
     * Caso 6 — Saldo por debajo de la mitad.
     * monto=₡400.000, saldo=₡100.000 → 100.000 > 200.000 es FALSO → base=₡200.000
     * interés = ₡200.000 × 20% = ₡40.000  (igual que caso 5 — verificar que ambos dan lo mismo)
     */
    private function caso6_debajoMitad(PrestamoService $s): void
    {
        $c = $this->cliente('Caso6 Debajo', 'Mitad');
        $s->crearDesdeMigracion($c, [
            'monto'     => 400000,
            'saldo'     => 100000,
            'frecuencia'=> 'mensual',
            'inicio'    => today()->subMonths(5),
            'proximo'   => today()->addMonth(),
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // §5.5 — Multa por día / tarifa por tramo
    //
    // NOTA sobre días:
    //   calcularDiasAtraso usa diffInDays(atraso_desde, hoy).
    //   Si proximo = hoy-5, dias = 5 y multa = 5 × tarifa.
    //   El "día de cobro no cuenta" está implícito: el día 0 (cobro)
    //   no genera multa; la multa arranca el día 1 (diffInDays=1).
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Caso 7 — Atrasado chico, tarifa ₡2.000/día (monto ≤ ₡50.000).
     * proximo = hace 5 días → dias_atraso=5 → multa = 5 × ₡2.000 = ₡10.000
     * interés período = ₡50.000 × 20% = ₡10.000
     * totalASaldar = ₡50.000 + ₡0 (intereses_atr) + ₡10.000 (multa) = ₡60.000
     */
    private function caso7_atrasadoChico(PrestamoService $s): void
    {
        $c = $this->cliente('Caso7 Atrasado', 'Chico');
        $proximo = today()->subDays(5)->toDateString();
        $p = $s->crearDesdeMigracion($c, [
            'monto'       => 50000,
            'frecuencia'  => 'mensual',
            'inicio'      => today()->subMonths(2),
            'proximo'     => $proximo,
            'atraso_desde'=> $proximo,
        ]);
        $p->load('interesesAtrasados');
        $s->sincronizarAtraso($p);
    }

    /**
     * Caso 8 — Atrasado mediano, tarifa ₡3.000/día (monto = ₡150.000 ≤ ₡150.000).
     * proximo = hace 3 días → dias_atraso=3 → multa = 3 × ₡3.000 = ₡9.000
     * interés período = ₡150.000 × 20% = ₡30.000
     * totalASaldar = ₡150.000 + ₡0 + ₡9.000 = ₡159.000
     */
    private function caso8_atrasadoMediano(PrestamoService $s): void
    {
        $c = $this->cliente('Caso8 Atrasado', 'Mediano');
        $proximo = today()->subDays(3)->toDateString();
        $p = $s->crearDesdeMigracion($c, [
            'monto'       => 150000,
            'frecuencia'  => 'mensual',
            'inicio'      => today()->subMonths(2),
            'proximo'     => $proximo,
            'atraso_desde'=> $proximo,
        ]);
        $p->load('interesesAtrasados');
        $s->sincronizarAtraso($p);
    }

    /**
     * Caso 9 — Atrasado grande, tarifa ₡5.000/día (monto > ₡150.000).
     * proximo = hace 4 días → dias_atraso=4 → multa = 4 × ₡5.000 = ₡20.000
     * interés período = ₡300.000 × 20% = ₡60.000
     * totalASaldar = ₡300.000 + ₡0 + ₡20.000 = ₡320.000
     */
    private function caso9_atrasadoGrande(PrestamoService $s): void
    {
        $c = $this->cliente('Caso9 Atrasado', 'Grande');
        $proximo = today()->subDays(4)->toDateString();
        $p = $s->crearDesdeMigracion($c, [
            'monto'       => 300000,
            'frecuencia'  => 'mensual',
            'inicio'      => today()->subMonths(2),
            'proximo'     => $proximo,
            'atraso_desde'=> $proximo,
        ]);
        $p->load('interesesAtrasados');
        $s->sincronizarAtraso($p);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // §5.5 — Día de cobro no cuenta
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Caso 10 — Proximo = HOY exactamente.
     * El motor usa lessThan(today()) [estricto]: proximo = hoy → NO atrasado.
     * dias_atraso esperado = 0, multa = ₡0, estado = al-dia.
     * cobrarInteres = true (hoy ≥ proximo → interés visible en el formulario).
     * interés período = ₡80.000 × 20% = ₡16.000
     */
    private function caso10_venceHoy(PrestamoService $s): void
    {
        $c = $this->cliente('Caso10 Vence', 'Hoy');
        $s->crearDesdeMigracion($c, [
            'monto'     => 80000,
            'frecuencia'=> 'mensual',
            'inicio'    => today()->subMonth(),
            'proximo'   => today()->toDateString(),   // HOY — no debe quedar atrasado
        ]);
        // NO llamar sincronizarAtraso: proximo = hoy → guard sale inmediato.
        // Verificar que estado = 'al-dia' y dias_atraso = 0.
    }

    // ─────────────────────────────────────────────────────────────────────────
    // §5.6 — Motor genera intereses atrasados por período vencido
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Caso 11 — Dos períodos vencidos (cliente mensual, proximo hace ~2 meses).
     *
     * proximo = hace 2 meses. El MOTOR (no el seeder) genera los registros de
     * interés atrasado para los períodos intermedios. Se verifica idempotencia:
     * correr el motor N veces no duplica registros en intereses_atrasados.
     *
     * Períodos esperados DESPUÉS del motor:
     *   - proximo (actual) = hace 2 meses → interesPeriodo = ₡40.000 (cobrado al pagar)
     *   - intereses_atrasados(hace ~1 mes) = ₡40.000 (generado por el motor)
     *   Total: 1 registro en intereses_atrasados.
     *
     * multa dinámica: ~61 días × ₡3.000/día ≈ ₡183.000 (varía con la fecha real).
     *
     * PRUEBA DE IDEMPOTENCIA:
     *   SELECT fecha, COUNT(*) FROM intereses_atrasados
     *   WHERE prestamo_id = (id de este caso) GROUP BY fecha;
     *   → ninguna fecha debe tener COUNT > 1.
     */
    private function caso11_dosPeriodosVencidos(PrestamoService $s): void
    {
        $c = $this->cliente('Caso11 Dos Periodos', 'Vencidos');
        $proximo = today()->subMonths(2)->toDateString();
        $p = $s->crearDesdeMigracion($c, [
            'monto'       => 200000,
            'frecuencia'  => 'mensual',
            'inicio'      => today()->subMonths(3),
            'proximo'     => $proximo,
            'atraso_desde'=> $proximo,
            // intereses_atrasados = 0 → el MOTOR los genera solo
        ]);
        $p->load('interesesAtrasados');
        $s->sincronizarAtraso($p);   // 1ª vez: crea el registro intermedio

        // 2ª y 3ª llamada: prueban idempotencia dentro del mismo seed
        $p->refresh()->load('interesesAtrasados');
        $s->sincronizarAtraso($p);

        $p->refresh()->load('interesesAtrasados');
        $s->sincronizarAtraso($p);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // §5.7 — Total a saldar = saldo + intereses_atrasados + multa
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Caso 12 — Cliente para saldar.
     * monto=₡300.000, saldo=₡200.000, multa_acumulada=₡30.000 (congelada del cuaderno).
     * proximo = hace 2 meses → motor genera interés atrasado para hace ~1 mes:
     *   saldo(200.000) > monto/2(150.000) → base=₡300.000 → ₡300.000×20% = ₡60.000
     *
     * totalASaldar = ₡200.000 + ₡60.000 (intereses_atr) + ₡30.000 (multa) = ₡290.000
     * (interesPeriodo = ₡60.000 va APARTE — no lo incluye totalASaldar)
     */
    private function caso12_paraSaldar(PrestamoService $s): void
    {
        $c = $this->cliente('Caso12 Para', 'Saldar');
        $proximo = today()->subMonths(2)->toDateString();
        $p = $s->crearDesdeMigracion($c, [
            'monto'           => 300000,
            'saldo'           => 200000,
            'frecuencia'      => 'mensual',
            'inicio'          => today()->subMonths(4),
            'proximo'         => $proximo,
            'atraso_desde'    => $proximo,
            'multa_acumulada' => 30000,   // congelada: no crece con el tiempo
            'interes_pagados' => 60000,
        ]);
        $p->load('interesesAtrasados');
        $s->sincronizarAtraso($p);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // §5.8 — Estados
    // ─────────────────────────────────────────────────────────────────────────

    /** Caso 13 — Cliente sin préstamo activo (estado sin-prestamo). */
    private function caso13_sinPrestamo(): void
    {
        Cliente::create([
            'nombre'    => 'Caso13 Sin',
            'apellidos' => 'Prestamo',
            'estado'    => 'sin-prestamo',
            'activo'    => true,
        ]);
    }

    /**
     * Caso 14 — Cliente inactivo con historial de préstamo saldado.
     * Verificar que la ficha de inactivos muestra el historial correctamente.
     */
    private function caso14_inactivo(PrestamoService $s): void
    {
        $c = $this->cliente('Caso14 Inactivo', 'Historial');
        $p = $s->crearDesdeMigracion($c, [
            'monto'          => 150000,
            'saldo'          => 0,      // lo dejamos en 0 para simular saldado
            'frecuencia'     => 'mensual',
            'inicio'         => today()->subYear(),
            'proximo'        => today()->subMonths(9),
            'interes_pagados'=> 180000,
        ]);
        // Marcar préstamo como saldado manualmente (crearDesdeMigracion solo crea activos)
        $p->update(['estado' => 'saldado']);
        // Desactivar el cliente
        $c->update(['activo' => false, 'estado' => 'sin-prestamo']);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // §5.9 — Pago parcial / Camino B
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Caso 15 — Interés pendiente (Camino B aplicado previamente).
     * El cliente pagó ₡10.000 de un interés de ₡20.000 → Camino B:
     *   proximo NO avanzó, interes_pendiente=₡10.000 quedó guardado.
     *
     * interesPeriodo() retorna interes_pendiente = ₡10.000 (no recalcula).
     * multa: dias_atraso=5 × ₡3.000 (monto≤₡150.000) = ₡15.000
     * totalASaldar = ₡100.000 + ₡0 (intereses_atr) + ₡15.000 (multa) = ₡115.000
     */
    private function caso15_interesPendiente(PrestamoService $s): void
    {
        $c = $this->cliente('Caso15 Interes', 'Pendiente');
        $proximo = today()->subDays(5)->toDateString();
        $p = $s->crearDesdeMigracion($c, [
            'monto'          => 100000,
            'frecuencia'     => 'mensual',
            'inicio'         => today()->subDays(35),
            'proximo'        => $proximo,
            'atraso_desde'   => $proximo,
            'interes_pagados'=> 10000,    // ya pagó la primera mitad del interés
        ]);
        // Simular que Camino B ya corrió: quedó interes_pendiente = ₡10.000
        $p->update(['interes_pendiente' => 10000]);
        $p->load('interesesAtrasados');
        $s->sincronizarAtraso($p);
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
