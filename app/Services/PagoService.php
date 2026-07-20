<?php

namespace App\Services;

use App\Models\Pago;
use App\Models\Prestamo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Registra pagos (§5.9 del README).
 *
 * Camino único (Camino A): el interés del período siempre cierra el ciclo.
 *   proximo avanza a la fecha acordada, dias_atraso = 0, atraso_desde = null.
 *   La multa se congela en lo que quedó sin pagar (multa_acumulada = resto).
 *   multa_ya_pagada se resetea a 0 (listo para el próximo ciclo).
 *
 * El dueño escribe el interés que cobra; el JS avisa si es menor al calculado
 * pero no bloquea — el período se cierra igual (no existe Camino B).
 *
 * Multa e intereses atrasados sí admiten pago parcial: el operador decide cuánto.
 */
class PagoService
{
    public function __construct(private PrestamoService $prestamoService) {}

    // ─────────────────────────────────────────────────────────────────────────
    // Método principal
    // ─────────────────────────────────────────────────────────────────────────

    public function registrarPago(Prestamo $prestamo, array $datos): Pago
    {
        // ── 1. Refrescar dias_atraso en memoria para el cálculo de multa ─────
        // calcularDiasAtraso ya excluye el día de hoy (solo días completos),
        // por lo que el valor a cobrar es exactamente el mostrado al operador.
        // No se escribe en DB: Camino A lo zercea a 0 al cerrar el período.
        if ($prestamo->atraso_desde) {
            $prestamo->dias_atraso = $this->prestamoService->calcularDiasAtraso($prestamo->atraso_desde);
        }

        // ── 2. Calcular lo que se debe de cada concepto ───────────────────────
        $interesTotal      = $this->prestamoService->interesPeriodo($prestamo);
        $multaTotal        = $this->prestamoService->multaAcumulada($prestamo);
        $interesesAtrTotal = $this->prestamoService->interesesAtrasadosTotal($prestamo);

        // ── 3. Clampear: no se puede pagar más de lo que se debe ─────────────
        $interesPagado       = min((int) ($datos['pago_interes']       ?? $interesTotal),      $interesTotal);
        $multaPagada         = min((int) ($datos['pago_multa']         ?? $multaTotal),        $multaTotal);
        $interesesAtrPagados = min((int) ($datos['pago_intereses_atr'] ?? $interesesAtrTotal), $interesesAtrTotal);
        $abono               = min((int) ($datos['abono']              ?? 0),                  $prestamo->saldo);

        $montoTotal = $interesPagado + $multaPagada + $interesesAtrPagados + $abono;

        // ── 4. Registrar el pago en la tabla `pagos` ─────────────────────────
        $pago = $prestamo->pagos()->create([
            'fecha'                   => today(),
            'monto_total'             => $montoTotal,
            'interes'                 => $interesPagado,
            'abono'                   => $abono,
            'interes_atrasado_pagado' => $interesesAtrPagados,
            'multa_pagada'            => $multaPagada,
            'metodo'                  => $datos['metodo'],
            'recibido_por'            => $datos['recibido_por'] ?? null,
            'es_saldo'                => false,
        ]);

        // ── 5. Aplicar abono al saldo del capital ─────────────────────────────
        $nuevoSaldo = max(0, $prestamo->saldo - $abono);
        $saldado    = $nuevoSaldo === 0;

        // ── 6. Aplicar pagos a intereses atrasados (oldest-first) ────────────
        if ($interesesAtrPagados > 0) {
            $this->aplicarPagoInteresesAtrasados($prestamo, $interesesAtrPagados);
        }

        // ── 7. Camino A siempre: el período se cierra ─────────────────────────
        $this->aplicarCaminoA($prestamo, $nuevoSaldo, $saldado, $multaTotal, $multaPagada, $interesPagado, $datos['proximo_cobro'] ?? null);

        // ── 8. Actualizar estado del cliente ──────────────────────────────────
        $this->actualizarEstadoCliente($prestamo, $saldado);

        return $pago;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Camino A: el período siempre se cierra
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * proximo avanza, el ciclo de atraso termina.
     * multa_acumulada y dias_atraso quedan en 0; la multa es siempre dinámica (§5.5).
     */
    private function aplicarCaminoA(
        Prestamo $prestamo,
        int $nuevoSaldo,
        bool $saldado,
        int $multaTotal,
        int $multaPagada,
        int $interesPagado,
        ?string $proximoCobro = null,
    ): void {
        $nuevoProximo = $saldado ? $prestamo->proximo
            : ($proximoCobro ? Carbon::parse($proximoCobro) : $this->avanzarProximo($prestamo));

        $prestamo->update([
            'saldo'           => $nuevoSaldo,
            'interes_pagados' => $prestamo->interes_pagados + $interesPagado,
            'multa_acumulada' => 0,
            'multa_ya_pagada' => 0,
            'dias_atraso'     => 0,
            'atraso_desde'    => null,
            'vencido'         => false,
            'proximo'         => $nuevoProximo,
            'estado'          => $saldado ? 'saldado' : 'activo',
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Aplicar pago a intereses atrasados (oldest-first)
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Distribuye $montoTotal entre los intereses atrasados pendientes,
     * del más antiguo al más reciente (§5.6 + §5.9).
     */
    private function aplicarPagoInteresesAtrasados(Prestamo $prestamo, int $montoTotal): void
    {
        $pendientes = $prestamo->interesesAtrasados
            ->where('pagado', false)
            ->sortBy('fecha');

        $restante = $montoTotal;

        foreach ($pendientes as $ia) {
            if ($restante <= 0) {
                break;
            }

            $pendienteDeEsteRegistro = $ia->monto - $ia->monto_pagado;
            $abonarAhora             = min($restante, $pendienteDeEsteRegistro);
            $nuevoPagado             = $ia->monto_pagado + $abonarAhora;

            $ia->update([
                'monto_pagado' => $nuevoPagado,
                'pagado'       => $nuevoPagado >= $ia->monto,
            ]);

            $restante -= $abonarAhora;
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Estado del cliente
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Recalcula el estado del cliente tras el pago.
     *
     * "al-dia" solo si no queda multa ni intereses atrasados pendientes.
     * Si el préstamo quedó saldado, verifica si tiene otro activo.
     */
    private function actualizarEstadoCliente(Prestamo $prestamo, bool $saldado): void
    {
        if ($saldado) {
            $tieneOtroActivo = $prestamo->cliente
                ->prestamos()
                ->where('estado', 'activo')
                ->exists();

            $prestamo->cliente->update([
                'estado' => $tieneOtroActivo ? 'al-dia' : 'sin-prestamo',
            ]);
            return;
        }

        $prestamo->refresh();
        $prestamo->load('interesesAtrasados');

        $quedaAlgo = $this->prestamoService->interesesAtrasadosTotal($prestamo) > 0
            || $this->prestamoService->multaAcumulada($prestamo) > 0;

        $prestamo->cliente->update([
            'estado' => $quedaAlgo ? 'atrasado' : 'al-dia',
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Saldar cuenta completa (§5.7)
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Liquida la cuenta completa del cliente en un solo movimiento (§5.7).
     *
     * Los montos vienen del formulario (editados por el operador si hubo ajuste).
     * Cada concepto se clampea al máximo real para no cobrar de más.
     *
     * Nota: interes_pendiente existe en la DB pero ya no se escribe aquí.
     */
    public function saldarCuenta(Prestamo $prestamo, array $datos): Pago
    {
        $saldo        = (int) $prestamo->saldo;
        $multaMax     = $this->prestamoService->multaAcumulada($prestamo);
        $atrasadosMax = $this->prestamoService->interesesAtrasadosTotal($prestamo);
        $interesMax   = $this->prestamoService->interesPeriodo($prestamo);

        $interesPagado    = min((int) ($datos['pago_interes']       ?? $interesMax),   $interesMax);
        $multaPagada      = min((int) ($datos['pago_multa']         ?? $multaMax),     $multaMax);
        $atrasadosPagados = min((int) ($datos['pago_intereses_atr'] ?? $atrasadosMax), $atrasadosMax);

        $montoTotal = $saldo + $interesPagado + $multaPagada + $atrasadosPagados;

        $pago = $prestamo->pagos()->create([
            'fecha'                   => today(),
            'monto_total'             => $montoTotal,
            'interes'                 => $interesPagado,
            'abono'                   => $saldo,
            'interes_atrasado_pagado' => $atrasadosPagados,
            'multa_pagada'            => $multaPagada,
            'metodo'                  => $datos['metodo'],
            'recibido_por'            => $datos['recibido_por'] ?? null,
            'es_saldo'                => true,
        ]);

        $prestamo->interesesAtrasados()
            ->where('pagado', false)
            ->update(['pagado' => true, 'monto_pagado' => \DB::raw('monto')]);

        $prestamo->update([
            'saldo'           => 0,
            'estado'          => 'saldado',
            'multa_acumulada' => 0,
            'multa_ya_pagada' => 0,
            'dias_atraso'     => 0,
            'atraso_desde'    => null,
            'vencido'         => false,
            'interes_pagados' => $prestamo->interes_pagados + $interesPagado,
        ]);

        $tieneOtroActivo = $prestamo->cliente
            ->prestamos()
            ->where('estado', 'activo')
            ->exists();

        $prestamo->cliente->update([
            'estado' => $tieneOtroActivo ? 'al-dia' : 'sin-prestamo',
        ]);

        return $pago;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Avance de proximo
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Calcula la próxima fecha de cobro según la frecuencia (§5.2).
     * Parte de $prestamo->proximo. Usada en aplicarCaminoA() como fallback
     * cuando el formulario no trae proximo_cobro.
     */
    public function avanzarProximo(Prestamo $prestamo): Carbon
    {
        $actual = $prestamo->proximo instanceof Carbon
            ? $prestamo->proximo->copy()
            : Carbon::parse($prestamo->proximo);

        return $this->prestamoService->avanzarFecha($actual, $prestamo->frecuencia);
    }

    /**
     * Sugiere la próxima fecha de cobro contando DESDE HOY (para el formulario de pago).
     * Garantiza que la fecha sugerida sea siempre >= hoy, aunque proximo esté vencido.
     */
    public function sugerirProximo(Prestamo $prestamo): Carbon
    {
        $hoy = today();

        return match ($prestamo->frecuencia) {
            'mensual'   => $hoy->copy()->addMonthNoOverflow(),
            'semanal'   => $hoy->copy()->addDays(7),
            'quincenal' => $this->proximoQuincenalDesdeHoy($hoy),
            default     => $hoy->copy()->addMonthNoOverflow(),
        };
    }

    private function proximoQuincenalDesdeHoy(Carbon $hoy): Carbon
    {
        if ($hoy->day < 15) {
            return $hoy->copy()->setDay(15);
        }

        if ($hoy->day <= 30) {
            return $hoy->copy()->setDay(min(30, $hoy->daysInMonth));
        }

        return $hoy->copy()->addMonthNoOverflow()->setDay(15);
    }
}
