<?php

namespace App\Services;

use App\Models\Pago;
use App\Models\Prestamo;
use Illuminate\Support\Carbon;

class PagoService
{
    public function __construct(private PrestamoService $prestamoService) {}

    /**
     * Registra un pago.
     *
     * Hay dos flujos según si el cobro ya venció o no:
     *
     * A) Cobro vencido (today >= proximo):
     *    - Se cobra interés del período + multa (si aplica) + intereses atrasados (si aplica).
     *    - El abono al capital es opcional.
     *    - Se marcan todos los InteresAtrasado no pagados como pagado=true.
     *    - Se avanza proximo al siguiente período (salvo si el préstamo queda saldado).
     *    - Se limpian dias_atraso, atraso_desde, multa_acumulada, vencido.
     *
     * B) Cobro adelantado (today < proximo):
     *    - NO se cobra interés, multa ni intereses atrasados.
     *    - Solo se aplica el abono al capital (opcional).
     *    - proximo NO avanza; el cliente aún debe el interés en su próxima fecha.
     *    - No se tocan interes_pagados, multa_acumulada, dias_atraso ni atraso_desde.
     *
     * En ambos flujos: si el abono lleva el saldo a ₡0, el préstamo queda con estado='saldado'.
     * El abono siempre se clampea a min(abono, saldo) para evitar saldo negativo.
     */
    public function registrarPago(Prestamo $prestamo, array $datos): Pago
    {
        $cobrarInteres = today()->greaterThanOrEqualTo($prestamo->proximo);

        $interes      = $cobrarInteres ? $this->prestamoService->interesPeriodo($prestamo) : 0;
        $multa        = $cobrarInteres ? $this->prestamoService->multaAcumulada($prestamo) : 0;
        $interesesAtr = $cobrarInteres ? $this->prestamoService->interesesAtrasadosTotal($prestamo) : 0;

        $abono      = min((int) ($datos['abono'] ?? 0), $prestamo->saldo);
        $montoTotal = $interes + $multa + $interesesAtr + $abono;

        $pago = $prestamo->pagos()->create([
            'fecha'                  => today(),
            'monto_total'            => $montoTotal,
            'interes'                => $interes,
            'abono'                  => $abono,
            'interes_atrasado_pagado' => $interesesAtr,
            'multa_pagada'           => $multa,
            'metodo'                 => $datos['metodo'],
            'recibido_por'           => $datos['recibido_por'] ?? null,
            'es_saldo'               => false,
        ]);

        $nuevoSaldo = max(0, $prestamo->saldo - $abono);
        $saldado    = $nuevoSaldo === 0;

        if ($cobrarInteres) {
            // Flujo A: interés vencido — marcar atrasados, limpiar atraso, avanzar fecha
            $prestamo->interesesAtrasados()->where('pagado', false)->update(['pagado' => true]);

            $prestamo->update([
                'saldo'           => $nuevoSaldo,
                'interes_pagados' => $prestamo->interes_pagados + $interes,
                'multa_acumulada' => 0,
                'dias_atraso'     => 0,
                'atraso_desde'    => null,
                'vencido'         => false,
                // No avanzar proximo si el préstamo queda saldado
                'proximo'         => $saldado ? $prestamo->proximo : $this->avanzarProximo($prestamo),
                'estado'          => $saldado ? 'saldado' : 'activo',
            ]);
        } else {
            // Flujo B: abono adelantado — solo bajar saldo, no tocar fechas ni interés
            $prestamo->update([
                'saldo'  => $nuevoSaldo,
                'estado' => $saldado ? 'saldado' : 'activo',
            ]);
        }

        $prestamo->cliente->update(['estado' => 'al-dia']);

        return $pago;
    }

    private function avanzarProximo(Prestamo $prestamo): Carbon
    {
        $actual = $prestamo->proximo instanceof Carbon
            ? $prestamo->proximo->copy()
            : Carbon::parse($prestamo->proximo);

        return match ($prestamo->frecuencia) {
            'mensual'   => $actual->addMonthNoOverflow(),
            'semanal'   => $actual->addDays(7),
            'quincenal' => $this->proximoQuincenal($actual),
            default     => $actual->addMonthNoOverflow(),
        };
    }

    /**
     * Avance quincenal: alterna 15 ↔ 30 del mes.
     *
     * Del 15 → al 30 (o último día si el mes tiene menos de 30).
     * Del 30 (o fin de mes) → al 15 del mes siguiente.
     *
     * Ejemplos: 15-oct → 30-oct; 30-oct → 15-nov; 15-feb → 28-feb; 28-feb → 15-mar.
     */
    private function proximoQuincenal(Carbon $fecha): Carbon
    {
        if ($fecha->day <= 15) {
            return $fecha->copy()->setDay(min(30, $fecha->daysInMonth));
        }

        return $fecha->copy()->addMonthNoOverflow()->setDay(15);
    }
}
