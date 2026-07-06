<?php

namespace App\Services;

use App\Models\Cliente;
use App\Models\Prestamo;
use Illuminate\Support\Carbon;

class PrestamoService
{
    private const TASAS = [
        'mensual' => 0.20,
        'quincenal' => 0.15,
        'semanal' => 0.05,
    ];

    private const TARIFAS_MULTA = [
        50000 => 2000,
        150000 => 3000,
    ];

    private const TARIFA_MULTA_DEFAULT = 5000;

    public function tasa(Prestamo $prestamo): float
    {
        return self::TASAS[$prestamo->frecuencia] ?? 0;
    }

    public function interesPeriodo(Prestamo $prestamo): int
    {
        if ($prestamo->monto <= 0 || $prestamo->saldo <= 0) {
            return 0;
        }

        $mitad = $prestamo->monto / 2;
        $base = $prestamo->saldo > $mitad ? $prestamo->monto : $mitad;

        return (int) round($base * $this->tasa($prestamo));
    }

    public function multaPorDia(int $monto): int
    {
        foreach (self::TARIFAS_MULTA as $tope => $tarifa) {
            if ($monto <= $tope) {
                return $tarifa;
            }
        }

        return self::TARIFA_MULTA_DEFAULT;
    }

    public function multaAcumulada(Prestamo $prestamo): int
    {
        if ($prestamo->multa_acumulada > 0) {
            return $prestamo->multa_acumulada;
        }

        if ($prestamo->dias_atraso <= 0) {
            return 0;
        }

        return $this->multaPorDia($prestamo->monto) * $prestamo->dias_atraso;
    }

    public function interesesAtrasadosTotal(Prestamo $prestamo): int
    {
        return (int) $prestamo->interesesAtrasados
            ->where('pagado', false)
            ->sum('monto');
    }

    public function totalASaldar(Prestamo $prestamo): int
    {
        return $prestamo->saldo + $this->interesesAtrasadosTotal($prestamo) + $this->multaAcumulada($prestamo);
    }

    public function abonado(Prestamo $prestamo): int
    {
        return max(0, $prestamo->monto - $prestamo->saldo);
    }

    /**
     * Días de multa por atraso (sección 5.5 del README).
     *
     * $atrasoDesde es la fecha de la cuota/cobro que NO se pagó (el día de esa cuota
     * todavía NO cuenta como multa). La multa empieza el día SIGUIENTE a esa fecha.
     * Ej.: debía pagar el 30 y hoy es el 5 del mes siguiente → 5 días de multa
     * (días 1,2,3,4,5 = del 31 al 5).
     *
     * `diffInDays($atrasoDesde, hoy)` ya da ese número directamente, sin sumar +1 a mano:
     * la diferencia entre el día de la cuota (30) y el día siguiente (31) ya es 1 día,
     * así que la diferencia entre el día de la cuota y "hoy" equivale exactamente a
     * "días transcurridos desde el día siguiente a la cuota hasta hoy".
     *
     * IMPORTANTE para el interés atrasado (sección 5.6): a diferencia de la multa, el
     * interés atrasado SÍ cuenta desde ese mismo día de la cuota (no desde el día
     * siguiente) — por eso el registro de InteresAtrasado se guarda con
     * `fecha = atraso_desde` en crearDesdeMigracion(), no con esta misma fecha+1.
     */
    public function calcularDiasAtraso(string|Carbon $atrasoDesde): int
    {
        $fecha = $atrasoDesde instanceof Carbon ? $atrasoDesde : Carbon::parse($atrasoDesde);

        return max(0, $fecha->startOfDay()->diffInDays(now()->startOfDay()));
    }

    /**
     * Actualiza el préstamo migrado desde el formulario de edición del cliente (sección 10 del README).
     *
     * ADVERTENCIA: este método borra y recrea todos los InteresAtrasado no pagados.
     * Está diseñado para corregir datos de migración manual (cuaderno). Una vez que el módulo
     * de Pagos empiece a generar registros de interés por período, no debe usarse libremente
     * (destruiría el historial de períodos vencidos). Revisitar cuando se construya Pagos.
     */
    public function actualizarDesdeMigracion(Prestamo $prestamo, array $datos): void
    {
        $atrasoDesde = $datos['atraso_desde'] ?? null;
        $diasAtraso = $atrasoDesde ? $this->calcularDiasAtraso($atrasoDesde) : 0;
        $multaAcumulada = $datos['multa_acumulada'] ?? 0;
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

        // Borra los intereses atrasados no pagados y los recrea con el nuevo total.
        // Ver ADVERTENCIA en el docblock: solo correcto para la fase de migración manual.
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
     * Crea el préstamo de un cliente migrado a mano desde el cuaderno (sección 10 del README),
     * con sus datos de atraso si los tiene, y actualiza el estado del cliente.
     */
    public function crearDesdeMigracion(Cliente $cliente, array $datos): Prestamo
    {
        $atrasoDesde = $datos['atraso_desde'] ?? null;
        // dias_atraso (multa) se deriva de atraso_desde con la regla "día siguiente"
        // documentada en calcularDiasAtraso(); NO es la misma fecha que se usa para
        // el interés atrasado más abajo.
        $diasAtraso = $atrasoDesde ? $this->calcularDiasAtraso($atrasoDesde) : 0;
        $multaAcumulada = $datos['multa_acumulada'] ?? 0;
        $interesesAtrasados = $datos['intereses_atrasados'] ?? 0;

        $atrasado = $diasAtraso > 0 || $multaAcumulada > 0 || $interesesAtrasados > 0;

        $prestamo = $cliente->prestamos()->create([
            'monto' => $datos['monto'],
            'saldo' => $datos['saldo'] ?? $datos['monto'],
            'frecuencia' => $datos['frecuencia'],
            'interes_pagados' => $datos['interes_pagados'] ?? 0,
            'multa_acumulada' => $multaAcumulada,
            'dias_atraso' => $diasAtraso,
            'atraso_desde' => $atrasoDesde,
            'inicio' => $datos['inicio'],
            'proximo' => $datos['proximo'],
            'vencido' => $atrasado,
            'estado' => 'activo',
        ]);

        if ($interesesAtrasados > 0) {
            // El interés atrasado se referencia con la fecha de la cuota que no se pagó
            // (atraso_desde), NO con el día siguiente: para el interés, ese mismo día
            // ya cuenta como período vencido sin pagar (sección 5.6 del README). Si no
            // se indicó atraso_desde, se usa el próximo cobro como mejor fecha disponible.
            $prestamo->interesesAtrasados()->create([
                'fecha' => $atrasoDesde ?? $datos['proximo'],
                'monto' => $interesesAtrasados,
                'pagado' => false,
            ]);
        }

        $cliente->update(['estado' => $atrasado ? 'atrasado' : 'al-dia']);

        return $prestamo;
    }
}
