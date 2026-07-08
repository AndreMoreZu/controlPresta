<?php

namespace App\Services;

use App\Models\Cliente;
use App\Models\Prestamo;
use Illuminate\Support\Carbon;

class PrestamoService
{
    // ── Constantes de negocio (§5.2) ─────────────────────────────────────────
    // Una sola definición; nunca escribir estas tasas en otro lugar del código.
    private const TASAS = [
        'mensual'   => 0.20,
        'quincenal' => 0.15,
        'semanal'   => 0.05,
    ];

    // Tarifa de multa por día según el monto del préstamo (§5.5).
    // Clave = tope inclusivo; por encima del último tope se usa TARIFA_MULTA_DEFAULT.
    private const TARIFAS_MULTA = [
        50000  => 2000,
        150000 => 3000,
    ];

    private const TARIFA_MULTA_DEFAULT = 5000;

    // ── Tasa e interés del período ────────────────────────────────────────────

    public function tasa(Prestamo $prestamo): float
    {
        return self::TASAS[$prestamo->frecuencia] ?? 0;
    }

    /**
     * Interés a cobrar en el período actual (§5.3).
     *
     * Si hay un interés parcialmente pagado del período anterior (interes_pendiente > 0),
     * devuelve ese monto restante en vez de recalcular: el cliente todavía debe la diferencia
     * del período que no cerró, y proximo no ha avanzado.
     *
     * Fórmula normal: base = monto si saldo > monto/2, o monto/2 si saldo <= monto/2.
     * El recálculo a la mitad ocurre una sola vez y queda fijo de ahí en adelante.
     */
    public function interesPeriodo(Prestamo $prestamo): int
    {
        // Hay interés del período anterior pendiente → devolver solo lo que falta.
        // proximo no avanzó; este es el monto que cierra ese período.
        if ($prestamo->interes_pendiente > 0) {
            return (int) $prestamo->interes_pendiente;
        }

        if ($prestamo->monto <= 0 || $prestamo->saldo <= 0) {
            return 0;
        }

        $mitad = $prestamo->monto / 2;
        $base  = $prestamo->saldo > $mitad ? $prestamo->monto : $mitad;

        return (int) round($base * $this->tasa($prestamo));
    }

    // ── Multa por atraso ──────────────────────────────────────────────────────

    /** Tarifa diaria de multa según el monto original del préstamo (§5.5). */
    public function multaPorDia(int $monto): int
    {
        foreach (self::TARIFAS_MULTA as $tope => $tarifa) {
            if ($monto <= $tope) {
                return $tarifa;
            }
        }

        return self::TARIFA_MULTA_DEFAULT;
    }

    /**
     * Multa neta que debe el cliente hoy (§5.5 + §5.9 Opción B).
     *
     * La multa_bruta se calcula de dos formas:
     *   - multa_acumulada > 0: monto congelado (ingresado al migrar, o resto tras
     *     un pago parcial con interés completo). No crece con el motor.
     *   - multa_acumulada = 0: dinámica: dias_atraso × tarifa. Crece cada día.
     *
     * multa_ya_pagada es el crédito acumulado de pagos de multa en el ciclo
     * de atraso actual. Se resta de la bruta para no cobrar dos veces lo ya pagado.
     *
     * Ejemplo: dia 6, bruta = ₡18.000, multa_ya_pagada = ₡10.000 → neta = ₡8.000.
     */
    public function multaAcumulada(Prestamo $prestamo): int
    {
        // Calcular la multa bruta (congelada o dinámica)
        if ($prestamo->multa_acumulada > 0) {
            // Congelada: ingresada manualmente al migrar el préstamo,
            // o resto de una multa tras pagar el interés completo.
            $bruta = (int) $prestamo->multa_acumulada;
        } elseif ($prestamo->dias_atraso > 0) {
            // Dinámica: se acumula un día a la vez con el motor de atraso.
            $bruta = $this->multaPorDia($prestamo->monto) * $prestamo->dias_atraso;
        } else {
            return 0;
        }

        // Restar crédito acumulado de pagos parciales de multa.
        // max(0, ...) evita valores negativos si hubo algún desajuste.
        return max(0, $bruta - (int) $prestamo->multa_ya_pagada);
    }

    // ── Intereses atrasados ───────────────────────────────────────────────────

    /**
     * Total de intereses atrasados que aún debe el cliente (§5.6 + §5.9).
     *
     * Descuenta los pagos parciales registrados en cada registro (monto_pagado).
     * Solo suma registros con pagado = false; los totalmente pagados ya no cuentan.
     */
    public function interesesAtrasadosTotal(Prestamo $prestamo): int
    {
        return (int) $prestamo->interesesAtrasados
            ->where('pagado', false)
            ->sum(fn($ia) => $ia->monto - $ia->monto_pagado);
    }

    // ── Totales auxiliares ────────────────────────────────────────────────────

    /**
     * Monto total para saldar la cuenta completa hoy (§5.7).
     * No incluye el interés del período (que va aparte); solo deuda + atrasos + multa.
     */
    public function totalASaldar(Prestamo $prestamo): int
    {
        return $prestamo->saldo
            + $this->interesesAtrasadosTotal($prestamo)
            + $this->multaAcumulada($prestamo);
    }

    /** Cuánto ha abonado el cliente al capital (monto original − saldo actual). */
    public function abonado(Prestamo $prestamo): int
    {
        return max(0, $prestamo->monto - $prestamo->saldo);
    }

    // ── Cálculo de días de atraso ─────────────────────────────────────────────

    /**
     * Días de multa por atraso desde atraso_desde (§5.5).
     *
     * La multa arranca el día SIGUIENTE a la fecha de cobro vencida.
     * diffInDays(atraso_desde, hoy) da exactamente ese conteo:
     *   ej. cobro el 30 → hoy es el 5 del mes siguiente → 5 días.
     *
     * (Ver docblock en la versión anterior para la explicación del +1 implícito.)
     */
    public function calcularDiasAtraso(string|Carbon $atrasoDesde): int
    {
        $fecha = $atrasoDesde instanceof Carbon
            ? $atrasoDesde
            : Carbon::parse($atrasoDesde);

        return max(0, $fecha->startOfDay()->diffInDays(now()->startOfDay()));
    }

    // ── Operaciones de migración manual (§8 del README) ──────────────────────

    /**
     * Actualiza el préstamo migrado desde el formulario de edición del cliente.
     *
     * ADVERTENCIA: borra y recrea todos los InteresAtrasado no pagados.
     * Solo correcto para la fase de migración manual (cuaderno). Una vez que
     * el motor de Pagos empiece a generar registros período a período, no usar.
     */
    public function actualizarDesdeMigracion(Prestamo $prestamo, array $datos): void
    {
        $atrasoDesde        = $datos['atraso_desde'] ?? null;
        $diasAtraso         = $atrasoDesde ? $this->calcularDiasAtraso($atrasoDesde) : 0;
        $multaAcumulada     = $datos['multa_acumulada'] ?? 0;
        $interesesAtrasados = $datos['intereses_atrasados'] ?? 0;

        $atrasado = $diasAtraso > 0 || $multaAcumulada > 0 || $interesesAtrasados > 0;

        $prestamo->update([
            'monto'            => $datos['monto'],
            'saldo'            => $datos['saldo'] ?? $datos['monto'],
            'frecuencia'       => $datos['frecuencia'],
            'interes_pagados'  => $datos['interes_pagados'] ?? 0,
            'multa_acumulada'  => $multaAcumulada,
            'dias_atraso'      => $diasAtraso,
            'atraso_desde'     => $atrasoDesde,
            'inicio'           => $datos['inicio'],
            'proximo'          => $datos['proximo'],
            'vencido'          => $atrasado,
        ]);

        $prestamo->interesesAtrasados()->where('pagado', false)->delete();

        if ($interesesAtrasados > 0) {
            $prestamo->interesesAtrasados()->create([
                'fecha'  => $atrasoDesde ?? $datos['proximo'],
                'monto'  => $interesesAtrasados,
                'pagado' => false,
            ]);
        }

        $prestamo->cliente->update(['estado' => $atrasado ? 'atrasado' : 'al-dia']);
    }

    /**
     * Crea el préstamo de un cliente migrado a mano desde el cuaderno (§8).
     * Ver ADVERTENCIA en actualizarDesdeMigracion().
     */
    public function crearDesdeMigracion(Cliente $cliente, array $datos): Prestamo
    {
        $atrasoDesde        = $datos['atraso_desde'] ?? null;
        $diasAtraso         = $atrasoDesde ? $this->calcularDiasAtraso($atrasoDesde) : 0;
        $multaAcumulada     = $datos['multa_acumulada'] ?? 0;
        $interesesAtrasados = $datos['intereses_atrasados'] ?? 0;

        $atrasado = $diasAtraso > 0 || $multaAcumulada > 0 || $interesesAtrasados > 0;

        $prestamo = $cliente->prestamos()->create([
            'monto'           => $datos['monto'],
            'saldo'           => $datos['saldo'] ?? $datos['monto'],
            'frecuencia'      => $datos['frecuencia'],
            'interes_pagados' => $datos['interes_pagados'] ?? 0,
            'multa_acumulada' => $multaAcumulada,
            'dias_atraso'     => $diasAtraso,
            'atraso_desde'    => $atrasoDesde,
            'inicio'          => $datos['inicio'],
            'proximo'         => $datos['proximo'],
            'vencido'         => $atrasado,
            'estado'          => 'activo',
        ]);

        if ($interesesAtrasados > 0) {
            // La fecha del interés atrasado es la del cobro que no se pagó (§5.6).
            $prestamo->interesesAtrasados()->create([
                'fecha'  => $atrasoDesde ?? $datos['proximo'],
                'monto'  => $interesesAtrasados,
                'pagado' => false,
            ]);
        }

        $cliente->update(['estado' => $atrasado ? 'atrasado' : 'al-dia']);

        return $prestamo;
    }
}
