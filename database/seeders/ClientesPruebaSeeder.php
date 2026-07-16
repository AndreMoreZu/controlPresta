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
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class ClientesPruebaSeeder extends Seeder
{
    public function run(): void
    {
        $s   = app(PrestamoService::class);
        $hoy = Carbon::today();

        $this->kendallRojas($s, $hoy);   // 1. semanal  — cobra HOY (al día)
        $this->greivinSalas($s, $hoy);   // 2. semanal  — atrasado 7 días, sin intereses atrasados
        $this->yorlenyVargas($s, $hoy);  // 3. quincenal — al día
        $this->adrianaMora($s, $hoy);    // 4. mensual   — al día, saldo en la mitad exacta (recálculo)
        $this->randallChaves($s, $hoy);  // 5. mensual   — atrasado 39 días, 1 interés atrasado
        $this->damarisSoto();             // 6. sin préstamo
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Semanal (5%)
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Kendall Rojas — Semanal, cobra HOY (al día).
     * proximo = hoy → proximo.lessThan(hoy) = FALSE → motor no toca, dias_atraso = 0.
     * interés = 80.000 × 5% = 4.000
     */
    private function kendallRojas(PrestamoService $s, Carbon $hoy): void
    {
        $c = $this->cliente('Kendall', 'Rojas');
        $s->crearDesdeMigracion($c, [
            'monto'      => 80000,
            'frecuencia' => 'semanal',
            'inicio'     => $hoy->copy()->subDays(7)->toDateString(),
            'proximo'    => $hoy->toDateString(),
        ]);
    }

    /**
     * Greivin Salas — Semanal atrasado 7 días.
     * proximo = hoy-7, atraso_desde = hoy-7
     * dias_atraso = diffDays(hoy-7, hoy) = 7
     * multa = 7 × 3.000 = 21.000  (100k → tramo 3.000/día)
     * interés = 100.000 × 5% = 5.000
     *
     * Motor: cursor = proximo + 7 = hoy → hoy.lessThan(hoy) = FALSE → 0 intereses atrasados.
     */
    private function greivinSalas(PrestamoService $s, Carbon $hoy): void
    {
        $atrasoDesde = $hoy->copy()->subDays(7)->toDateString();

        $c = $this->cliente('Greivin', 'Salas');
        $p = $s->crearDesdeMigracion($c, [
            'monto'        => 100000,
            'frecuencia'   => 'semanal',
            'inicio'       => $hoy->copy()->subDays(35)->toDateString(),
            'proximo'      => $atrasoDesde,
            'atraso_desde' => $atrasoDesde,
        ]);
        $p->load('interesesAtrasados');
        $s->sincronizarAtraso($p);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Quincenal (10%)
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Yorleny Vargas — Quincenal al día (paga los 15 y los 30).
     * proximo = próxima fecha 15 o 30 después de hoy → siempre >= hoy.
     * interés = 150.000 × 10% = 15.000
     */
    private function yorlenyVargas(PrestamoService $s, Carbon $hoy): void
    {
        $c = $this->cliente('Yorleny', 'Vargas');
        $s->crearDesdeMigracion($c, [
            'monto'      => 150000,
            'frecuencia' => 'quincenal',
            'inicio'     => $hoy->copy()->subDays(10)->toDateString(),
            'proximo'    => $this->proximoQuincenal($hoy)->toDateString(),
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Mensual (20%)
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Adriana Mora — Mensual al día, saldo exactamente en la mitad (prueba recálculo).
     * proximo = próxima fecha 15 después de hoy → siempre >= hoy.
     *
     * saldo(100.000) > monto/2(100.000) = FALSE → base = 100.000 (mitad)
     * interés = 100.000 × 20% = 20.000  (NOT 40.000 — recálculo a la mitad ya activó)
     */
    private function adrianaMora(PrestamoService $s, Carbon $hoy): void
    {
        $c = $this->cliente('Adriana', 'Mora');
        $s->crearDesdeMigracion($c, [
            'monto'      => 200000,
            'saldo'      => 100000,
            'frecuencia' => 'mensual',
            'inicio'     => $hoy->copy()->subMonths(3)->toDateString(),
            'proximo'    => $this->proximoMensual15($hoy)->toDateString(),
        ]);
    }

    /**
     * Randall Chaves — Mensual atrasado profundo (39 días).
     * proximo = hoy-39, atraso_desde = hoy-39
     * dias_atraso = diffDays(hoy-39, hoy) = 39
     * multa = 39 × 2.000 = 78.000  (80k → tramo 2.000/día)
     * interés del período = 80.000 × 20% = 16.000
     *
     * Motor: cursor = proximo + 1mes = hoy-39+~30 = hoy-~9 días → < hoy → crea 1 interés atrasado
     *   siguiente cursor = hoy+~21 días → FALSE → para. Siempre exactamente 1 registro.
     */
    private function randallChaves(PrestamoService $s, Carbon $hoy): void
    {
        $atrasoDesde = $hoy->copy()->subDays(39)->toDateString();

        $c = $this->cliente('Randall', 'Chaves');
        $p = $s->crearDesdeMigracion($c, [
            'monto'        => 80000,
            'frecuencia'   => 'mensual',
            'inicio'       => $hoy->copy()->subDays(69)->toDateString(),
            'proximo'      => $atrasoDesde,
            'atraso_desde' => $atrasoDesde,
        ]);
        $p->load('interesesAtrasados');
        $s->sincronizarAtraso($p);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Sin préstamo
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Damaris Soto — Sin préstamo activo.
     * Para demo del botón "Nuevo préstamo" y de "Saldar" en cliente al día.
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

    // ─────────────────────────────────────────────────────────────────────────
    // Helpers de fecha
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Próxima fecha de cobro quincenal (15 o 30) a partir de hoy.
     * day < 15 → este mes el 15
     * 15 <= day < 30 → este mes el 30 (o último día del mes si < 30)
     * day >= 30 → próximo mes el 15
     */
    private function proximoQuincenal(Carbon $hoy): Carbon
    {
        if ($hoy->day < 15) {
            return $hoy->copy()->setDay(15);
        }
        if ($hoy->day < 30) {
            return $hoy->copy()->setDay(min(30, $hoy->daysInMonth));
        }
        return $hoy->copy()->addMonthNoOverflow()->setDay(15);
    }

    /**
     * Próxima fecha de cobro mensual en el día 15.
     * day <= 15 → este mes el 15 (incluye el caso "hoy es el 15 = cobro hoy")
     * day > 15  → próximo mes el 15
     */
    private function proximoMensual15(Carbon $hoy): Carbon
    {
        if ($hoy->day <= 15) {
            return $hoy->copy()->setDay(15);
        }
        return $hoy->copy()->addMonthNoOverflow()->setDay(15);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Helper de cliente
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
