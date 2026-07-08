<?php

namespace App\Services;

use App\Models\Pago;
use App\Models\Prestamo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Registra pagos con soporte completo de pagos parciales (§5.9 del README).
 *
 * Dos caminos principales según si el interés del período queda completo o no:
 *
 *   Camino A — Interés completo (atraso se cierra):
 *     proximo avanza, dias_atraso = 0, interes_pendiente = 0.
 *     La multa se congela en lo que quedó sin pagar (multa_acumulada = resto, ya no crece).
 *     multa_ya_pagada se resetea a 0 (listo para el próximo ciclo de atraso).
 *
 *   Camino B — Interés parcial (atraso continúa):
 *     proximo NO avanza. interes_pendiente guarda el resto.
 *     multa_ya_pagada acumula lo pagado hoy (crédito para mañana, §5.9 Opción B).
 *     dias_atraso y multa_acumulada no se tocan; el motor sigue corriendo.
 *
 * En ambos caminos:
 *   - El abono baja el saldo del capital.
 *   - Los intereses atrasados se abonan del más antiguo al más reciente.
 *   - Cada pago queda registrado en `pagos` con su desglose exacto.
 */
class PagoService
{
    public function __construct(private PrestamoService $prestamoService) {}

    // ─────────────────────────────────────────────────────────────────────────
    // Método principal
    // ─────────────────────────────────────────────────────────────────────────

    public function registrarPago(Prestamo $prestamo, array $datos): Pago
    {
        // ── 1. Recalcular dias_atraso desde atraso_desde ──────────────────────
        // dias_atraso es un snapshot (guardado al migrar); la fuente de verdad es
        // atraso_desde (§7 del README, nota 1). Actualizarlo antes de calcular multa.
        if ($prestamo->atraso_desde && $prestamo->interes_pendiente === 0) {
            // Solo recalcular si estamos en un ciclo de atraso activo sin interés
            // parcial previo (si hay interes_pendiente > 0, proximo no avanzó y el
            // ciclo aún no cerró, por lo que dias_atraso ya es correcto).
            $diasActuales = $this->prestamoService->calcularDiasAtraso($prestamo->atraso_desde);
            if ($diasActuales !== $prestamo->dias_atraso) {
                $prestamo->update(['dias_atraso' => $diasActuales]);
                $prestamo->dias_atraso = $diasActuales; // actualizar instancia en memoria
            }
        }

        // ── 2. Determinar si aplica cobro de interés ──────────────────────────
        // cobrarInteres = true si ya venció la fecha O si quedó interés pendiente
        // de un pago parcial anterior (en cuyo caso proximo no avanzó, así que
        // today() >= proximo ya es verdadero de todas formas).
        $cobrarInteres = today()->greaterThanOrEqualTo($prestamo->proximo)
            || $prestamo->interes_pendiente > 0;

        // ── 3. Calcular lo que se debe de cada concepto ───────────────────────
        // El interés del período solo aplica cuando cobrarInteres = true.
        // Multa e intereses atrasados son deudas independientes de proximo:
        // pueden existir aunque proximo esté en el futuro (caso borde §5.9).
        $interesTotal      = $cobrarInteres ? $this->prestamoService->interesPeriodo($prestamo) : 0;
        $multaTotal        = $this->prestamoService->multaAcumulada($prestamo);
        $interesesAtrTotal = $this->prestamoService->interesesAtrasadosTotal($prestamo);

        // ── 4. Clampear: no se puede pagar más de lo que se debe ─────────────
        // El formulario admite escritura libre; el service garantiza consistencia.
        $interesPagado       = min((int) ($datos['pago_interes']       ?? $interesTotal),      $interesTotal);
        $multaPagada         = min((int) ($datos['pago_multa']         ?? $multaTotal),        $multaTotal);
        $interesesAtrPagados = min((int) ($datos['pago_intereses_atr'] ?? $interesesAtrTotal), $interesesAtrTotal);
        $abono               = min((int) ($datos['abono']              ?? 0),                  $prestamo->saldo);

        $montoTotal = $interesPagado + $multaPagada + $interesesAtrPagados + $abono;

        // ── 5. Registrar el pago en la tabla `pagos` ─────────────────────────
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

        // ── 6. Aplicar abono al saldo del capital ─────────────────────────────
        $nuevoSaldo = max(0, $prestamo->saldo - $abono);
        $saldado    = $nuevoSaldo === 0;

        // ── 7. Aplicar pagos parciales a intereses atrasados ─────────────────
        // Se aplica siempre que haya algo pagado, independientemente de proximo.
        // La multa y los intereses atrasados son deudas separadas del interés del
        // período: pueden cobrarse aunque proximo todavía no haya llegado (§5.9).
        if ($interesesAtrPagados > 0) {
            $this->aplicarPagoInteresesAtrasados($prestamo, $interesesAtrPagados);
        }

        // ── 8. Bifurcación principal: Camino A / B / sin-interés ─────────────
        $interesCompleto = $cobrarInteres && ($interesPagado >= $interesTotal);

        if ($interesCompleto) {
            // Camino A: interés del período pagado completo → atraso se cierra.
            // Pasa la fecha del formulario; si no viene, aplicarCaminoA usa avanzarProximo().
            $this->aplicarCaminoA($prestamo, $nuevoSaldo, $saldado, $multaTotal, $multaPagada, $interesPagado, $datos['proximo_cobro'] ?? null);
        } elseif ($cobrarInteres) {
            // Camino B: interés del período pagado parcial → proximo no avanza.
            $this->aplicarCaminoB($prestamo, $nuevoSaldo, $saldado, $interesTotal, $interesPagado, $multaPagada);
        } else {
            // Sin interés del período: proximo en el futuro, pero puede haber
            // multa y/o intereses atrasados que sí se cobran (caso borde §5.9).
            // multa_ya_pagada acumula el crédito de multa igual que en Camino B
            // para que mañana no se cobre dos veces lo que ya pagó.
            $prestamo->update([
                'saldo'           => $nuevoSaldo,
                'multa_ya_pagada' => $prestamo->multa_ya_pagada + $multaPagada,
                'estado'          => $saldado ? 'saldado' : 'activo',
            ]);
        }

        // ── 9. Actualizar estado del cliente ──────────────────────────────────
        $this->actualizarEstadoCliente($prestamo, $saldado);

        return $pago;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Camino A: interés completo — el atraso se cierra
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * El cliente pagó el interés completo: proximo avanza, el ciclo de atraso termina.
     *
     * La multa se congela en lo que quedó sin pagar:
     *   - multaTotal ya es la multa NETA (multaAcumulada() descuenta multa_ya_pagada).
     *   - multa_restante = lo que debía hoy − lo que pagó hoy.
     *   - Si multa_restante > 0: se guarda como multa_acumulada (congelada, ya NO crece).
     *   - Si multa_restante = 0: multa_acumulada = 0 (limpio para el próximo ciclo).
     *   - multa_ya_pagada se resetea a 0: el crédito del ciclo anterior queda saldado.
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
        $multaRestante = max(0, $multaTotal - $multaPagada);

        // Fecha del próximo cobro: usar la del formulario si viene válida,
        // si no, calcular automáticamente. proximo NUNCA queda null.
        $nuevoProximo = $saldado ? $prestamo->proximo
            : ($proximoCobro ? Carbon::parse($proximoCobro) : $this->avanzarProximo($prestamo));

        $prestamo->update([
            'saldo'              => $nuevoSaldo,
            'interes_pagados'    => $prestamo->interes_pagados + $interesPagado,
            'interes_pendiente'  => 0,
            'multa_acumulada'    => $multaRestante,
            'multa_ya_pagada'    => 0,
            'dias_atraso'        => 0,
            'atraso_desde'       => null,
            'vencido'            => false,
            'proximo'            => $nuevoProximo,
            'estado'             => $saldado ? 'saldado' : 'activo',
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Camino B: interés parcial — el atraso continúa
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * El cliente NO pagó el interés completo: proximo no avanza, el atraso sigue.
     *
     * interes_pendiente guarda lo que falta para cerrar el período.
     * multa_ya_pagada acumula lo que pagó de multa hoy (Opción B, §5.9):
     *   mañana, multaAcumulada() restará este acumulado de la multa bruta,
     *   así no se le cobra dos veces lo que ya pagó.
     * dias_atraso, multa_acumulada y proximo NO se tocan; el motor sigue corriendo.
     */
    private function aplicarCaminoB(
        Prestamo $prestamo,
        int $nuevoSaldo,
        bool $saldado,
        int $interesTotal,
        int $interesPagado,
        int $multaPagada,
    ): void {
        $prestamo->update([
            'saldo'             => $nuevoSaldo,
            'interes_pagados'   => $prestamo->interes_pagados + $interesPagado,
            'interes_pendiente' => $interesTotal - $interesPagado,  // lo que falta del período
            'multa_ya_pagada'   => $prestamo->multa_ya_pagada + $multaPagada, // crédito acumulado
            // dias_atraso, atraso_desde, multa_acumulada, proximo → sin cambios
            'estado'            => $saldado ? 'saldado' : 'activo',
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Aplicar pago a intereses atrasados (oldest-first)
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Distribuye $montoTotal entre los intereses atrasados pendientes,
     * del más antiguo al más reciente (§5.6 + §5.9).
     *
     * Para cada registro: actualiza monto_pagado y marca pagado = true
     * cuando monto_pagado >= monto (interés atrasado completamente saldado).
     */
    private function aplicarPagoInteresesAtrasados(Prestamo $prestamo, int $montoTotal): void
    {
        $pendientes = $prestamo->interesesAtrasados
            ->where('pagado', false)
            ->sortBy('fecha'); // más antiguo primero

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
     * "al-dia" solo si no queda NADA pendiente:
     *   - interes_pendiente = 0   (ningún interés de período sin cerrar)
     *   - interesesAtrasadosTotal = 0   (ningún período atrasado sin pagar)
     *   - multaAcumulada = 0   (ninguna multa sin pagar)
     *
     * Si el préstamo quedó saldado, también verifica si tiene otro activo.
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

        // Recargar el préstamo para leer los valores ya guardados en DB.
        // (Los métodos del service leen desde el modelo; si no recargamos,
        //  podrían leer valores desactualizados de la instancia en memoria.)
        $prestamo->refresh();
        $prestamo->load('interesesAtrasados');

        $quedaAlgo = $prestamo->interes_pendiente > 0
            || $this->prestamoService->interesesAtrasadosTotal($prestamo) > 0
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
     * Registra un pago con es_saldo = true que cubre:
     *   - abono = saldo completo (cancela toda la deuda de capital)
     *   - interes = interés del período si ya venció (editado en el formulario)
     *   - interes_atrasado_pagado = total intereses atrasados pendientes
     *   - multa_pagada = multa acumulada neta
     *
     * Efectos: prestamo.estado = saldado, saldo = 0, intereses_atrasados todos pagados,
     * cliente.estado = sin-prestamo (si no queda otro préstamo activo).
     */
    public function saldarCuenta(Prestamo $prestamo, array $datos): Pago
    {
        $saldo        = (int) $prestamo->saldo;
        $multaMax     = $this->prestamoService->multaAcumulada($prestamo);
        $atrasadosMax = $this->prestamoService->interesesAtrasadosTotal($prestamo);

        // El dueño decide cuánto cobrar de interés al saldar (puede ser 0 o el período completo).
        // Max = lo que computó el servicio para este período, independientemente de si ya venció.
        $interesMax = $this->prestamoService->interesPeriodo($prestamo);

        // Clampear al máximo de cada concepto para no registrar más de lo que se debe.
        $interesPagado    = min((int) ($datos['pago_interes']       ?? $interesMax),    $interesMax);
        $multaPagada      = min((int) ($datos['pago_multa']         ?? $multaMax),      $multaMax);
        $atrasadosPagados = min((int) ($datos['pago_intereses_atr'] ?? $atrasadosMax),  $atrasadosMax);

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

        // Marcar todos los intereses atrasados pendientes como pagados
        $prestamo->interesesAtrasados()
            ->where('pagado', false)
            ->update(['pagado' => true, 'monto_pagado' => \DB::raw('monto')]);

        // Cerrar el préstamo
        $prestamo->update([
            'saldo'             => 0,
            'estado'            => 'saldado',
            'multa_acumulada'   => 0,
            'multa_ya_pagada'   => 0,
            'dias_atraso'       => 0,
            'atraso_desde'      => null,
            'vencido'           => false,
            'interes_pendiente' => 0,
            'interes_pagados'   => $prestamo->interes_pagados + $interesPagado,
        ]);

        // Estado del cliente
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
     * Calcula la próxima fecha de cobro según la frecuencia del préstamo (§5.2).
     * Parte de $prestamo->proximo (fecha actual). Usado en aplicarCaminoA() para
     * el avance real tras un pago exitoso.
     * Delega a PrestamoService::avanzarFecha() — lógica centralizada allá.
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
     *
     * Reglas por frecuencia:
     *   mensual   → hoy + 1 mes
     *   semanal   → hoy + 7 días
     *   quincenal → próxima fecha 15 o fin-de-mes desde hoy:
     *                 hoy.day < 15        → el 15 de este mes
     *                 hoy.day entre 15-30 → el min(30, último día) de este mes
     *                 hoy.day > 30        → el 15 del mes siguiente
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

        // día 31: la fecha "30" ya pasó este mes, ir al 15 del siguiente
        return $hoy->copy()->addMonthNoOverflow()->setDay(15);
    }
}
